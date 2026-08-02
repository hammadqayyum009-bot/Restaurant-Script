<?php

namespace Tests\Feature\Billing;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * SQLite is the engine that actually needs the unique index — lockForUpdate()
 * is a no-op there (see App\Services\Billing\DocumentNumberer's class doc),
 * so this proves the unique-index-plus-retry safety net catches a real,
 * OS-level race rather than the single-process interleaving a mock could
 * fake. 20 real child processes, one shared on-disk SQLite file, all racing
 * to issue against the very same (document_type, series_year) series.
 *
 * Extends Tests\TestCase (not RefreshDatabase) purely to get a booted app for
 * the Config/DB facades used to seed and verify — every bit of billing code
 * actually under test runs inside the child processes, against their own
 * throwaway on-disk SQLite file, never this process's own :memory: database.
 */
class NumberingConcurrencySqliteTest extends TestCase
{
    protected string $dbFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbFile = tempnam(sys_get_temp_dir(), 'billing_concurrency_').'.sqlite';
        touch($this->dbFile);
    }

    protected function tearDown(): void
    {
        DB::purge('concurrency_sqlite');
        @unlink($this->dbFile);
        parent::tearDown();
    }

    protected function basePath(): string
    {
        return dirname(__DIR__, 3);
    }

    public function test_twenty_concurrent_allocations_produce_twenty_distinct_numbers_on_sqlite(): void
    {
        $workerCount = 20;
        $base = $this->basePath();
        $php = (new PhpExecutableFinder)->find() ?: 'php';

        $env = [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->dbFile,
            'APP_ENV' => 'testing',
        ];

        // 1. Migrate the throwaway file in its own process, so this test
        // never touches the PHPUnit run's own :memory: database.
        $migrate = new Process([$php, 'artisan', 'migrate', '--force'], $base, $env);
        $migrate->setTimeout(60);
        $migrate->run();
        $this->assertTrue($migrate->isSuccessful(), 'Migration failed: '.$migrate->getErrorOutput());

        // 2. Seed an admin and N drafts, all targeting the same
        // (simplified_tax_invoice, this year) series, via a direct
        // connection to the same file from this process.
        Config::set('database.connections.concurrency_sqlite', [
            'driver' => 'sqlite',
            'database' => $this->dbFile,
            'foreign_key_constraints' => true,
        ]);
        $conn = DB::connection('concurrency_sqlite');

        $userId = $conn->table('users')->insertGetId([
            'name' => 'Concurrency Admin',
            'email' => 'concurrency@example.com',
            'password' => bcrypt('password'),
            'is_admin' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $documentIds = [];

        for ($i = 0; $i < $workerCount; $i++) {
            $orderId = $conn->table('orders')->insertGetId([
                'order_number' => 'CONC-'.$i.'-'.uniqid(),
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
                'order_number' => 'CONC-'.$i,
                'currency' => 'AED',
                'currency_exponent' => 2,
                'prices_include_vat' => 1,
                'vat_rate_bp' => 1500,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::purge('concurrency_sqlite');

        // 3. Launch all workers essentially simultaneously and let the OS
        // schedule them — this is real process concurrency, not a loop.
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

        $this->assertCount($workerCount, array_unique($numbers), 'All 20 issued documents must have distinct document numbers — the unique index must have rejected any real collision, and the retry loop resolved it.');

        // Verify from the database directly too, not just worker stdout.
        Config::set('database.connections.concurrency_sqlite', [
            'driver' => 'sqlite',
            'database' => $this->dbFile,
            'foreign_key_constraints' => true,
        ]);
        $issuedNumbers = DB::connection('concurrency_sqlite')
            ->table('billing_documents')
            ->where('document_type', 'simplified_tax_invoice')
            ->whereNotNull('number')
            ->pluck('number');

        $this->assertSame($workerCount, $issuedNumbers->count());
        $this->assertSame($issuedNumbers->count(), $issuedNumbers->unique()->count(), 'No duplicate numbers exist in the database.');
        $this->assertSame(range(1, $workerCount), $issuedNumbers->sort()->values()->all(), 'The series is gapless from 1 to 20.');
    }
}
