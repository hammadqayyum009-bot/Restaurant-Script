<?php

namespace Tests\Feature\Billing;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Mirrors NumberingConcurrencySqliteTest, but against real MySQL/MariaDB —
 * the engine where DocumentNumberer's lockForUpdate() actually does the
 * serialising (SQLite's version of this test is what proves the unique-index
 * fallback works; this one proves the row-lock path works on the engine this
 * app actually ships on for production/cPanel).
 *
 * Per the approved Batch 2 plan: this MUST be run once against real
 * MySQL/MariaDB before the billing module is considered done — a skip here
 * is a placeholder, not a pass.
 *
 * Configure via env vars before running:
 *   BILLING_MYSQL_TEST_HOST, BILLING_MYSQL_TEST_PORT (default 3306),
 *   BILLING_MYSQL_TEST_DATABASE, BILLING_MYSQL_TEST_USERNAME,
 *   BILLING_MYSQL_TEST_PASSWORD (default empty)
 * The named database must already exist and be safe to drop/recreate tables
 * in — this test truncates the tables it uses.
 */
class NumberingConcurrencyMysqlTest extends TestCase
{
    protected function basePath(): string
    {
        return dirname(__DIR__, 3);
    }

    protected function mysqlConfig(): ?array
    {
        $host = getenv('BILLING_MYSQL_TEST_HOST');

        if (! $host) {
            return null;
        }

        return [
            'host' => $host,
            'port' => getenv('BILLING_MYSQL_TEST_PORT') ?: '3306',
            'database' => getenv('BILLING_MYSQL_TEST_DATABASE') ?: 'billing_concurrency_test',
            'username' => getenv('BILLING_MYSQL_TEST_USERNAME') ?: 'root',
            'password' => getenv('BILLING_MYSQL_TEST_PASSWORD') ?: '',
        ];
    }

    public function test_twenty_concurrent_allocations_produce_twenty_distinct_numbers_on_mysql(): void
    {
        $config = $this->mysqlConfig();

        if ($config === null) {
            $this->markTestSkipped(
                'No MySQL/MariaDB available (set BILLING_MYSQL_TEST_HOST etc). '.
                'Per the Batch 2 plan, this test must be run against real MySQL/MariaDB '.
                'before the billing module is considered done — this skip is a placeholder, not a pass.'
            );
        }

        try {
            new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_TIMEOUT => 3],
            );
        } catch (PDOException $e) {
            $this->markTestSkipped('Could not connect to the configured MySQL server: '.$e->getMessage());
        }

        $workerCount = 20;
        $base = $this->basePath();
        $php = (new PhpExecutableFinder)->find() ?: 'php';

        $env = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $config['host'],
            'DB_PORT' => $config['port'],
            'DB_DATABASE' => $config['database'],
            'DB_USERNAME' => $config['username'],
            'DB_PASSWORD' => $config['password'],
            'APP_ENV' => 'testing',
        ];

        Config::set('database.connections.concurrency_mysql', [
            'driver' => 'mysql',
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $migrate = new Process([$php, 'artisan', 'migrate:fresh', '--force'], $base, $env);
        $migrate->setTimeout(60);
        $migrate->run();
        $this->assertTrue($migrate->isSuccessful(), 'Migration failed: '.$migrate->getErrorOutput());

        $conn = DB::connection('concurrency_mysql');

        $userId = $conn->table('users')->insertGetId([
            'name' => 'Concurrency Admin',
            'email' => 'concurrency-mysql@example.com',
            'password' => bcrypt('password'),
            'is_admin' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $documentIds = [];

        for ($i = 0; $i < $workerCount; $i++) {
            $orderId = $conn->table('orders')->insertGetId([
                'order_number' => 'CONCM-'.$i.'-'.uniqid(),
                'customer_name' => 'Customer '.$i,
                'phone' => '0500000000',
                'address' => 'Test address',
                'order_type' => 'delivery',
                'subtotal' => 100,
                'delivery_fee' => 0,
                'tax' => 0,
                'total' => 100,
                'payment_method' => 'cash',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $conn->table('order_items')->insert([
                'order_id' => $orderId,
                'name' => 'Item',
                'price' => 100,
                'quantity' => 1,
                'line_total' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $documentIds[] = $conn->table('billing_documents')->insertGetId([
                'document_type' => 'simplified_tax_invoice',
                'series_year' => (int) now()->year,
                'status' => 'draft',
                'order_id' => $orderId,
                'order_number' => 'CONCM-'.$i,
                'currency' => 'AED',
                'currency_exponent' => 2,
                'prices_include_vat' => 1,
                'vat_rate_bp' => 1500,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $worker = __DIR__.'/Support/concurrency_worker.php';
        $processes = [];

        foreach ($documentIds as $documentId) {
            $process = new Process([$php, $worker, (string) $documentId, (string) $userId], $base, $env);
            $process->start();
            $processes[] = $process;
        }

        $outputs = [];
        foreach ($processes as $process) {
            $process->wait();
            $outputs[] = trim($process->getOutput());
        }

        $failures = array_filter($outputs, fn ($line) => ! str_starts_with($line, 'OK '));
        $this->assertSame([], array_values($failures), 'Every worker should succeed with only 20 workers and 3 retry attempts each.');

        $numbers = array_map(fn ($line) => substr($line, 3), $outputs);
        $this->assertCount($workerCount, array_unique($numbers));

        $issuedNumbers = $conn->table('billing_documents')
            ->where('document_type', 'simplified_tax_invoice')
            ->whereNotNull('number')
            ->pluck('number');

        $this->assertSame($workerCount, $issuedNumbers->count());
        $this->assertSame($issuedNumbers->count(), $issuedNumbers->unique()->count());
        $this->assertSame(range(1, $workerCount), $issuedNumbers->sort()->values()->all());

        DB::purge('concurrency_mysql');
    }
}
