<?php

namespace Tests\Feature\Security;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Mirrors LastAdminDeletionConcurrencySqliteTest, but against real
 * MySQL/MariaDB — the engine where AdminUserDeleter's lockForUpdate()
 * actually does the serialising (SQLite's version of this test proves
 * nothing crashes/deadlocks/loses an update; this one proves the row-lock
 * path itself is what's doing the work, on the engine this app actually
 * ships on for production/cPanel).
 *
 * Per the same standard already set for the billing numbering engine's
 * MySQL test: this SHOULD be run once against real MySQL/MariaDB before this
 * item is considered done — a skip here is a placeholder, not a pass.
 *
 * Configure via env vars before running (same ones the numbering MySQL test
 * uses):
 *   BILLING_MYSQL_TEST_HOST, BILLING_MYSQL_TEST_PORT (default 3306),
 *   BILLING_MYSQL_TEST_DATABASE, BILLING_MYSQL_TEST_USERNAME,
 *   BILLING_MYSQL_TEST_PASSWORD (default empty)
 * The named database must already exist and be safe to drop/recreate tables
 * in — this test runs a real migration against it.
 */
class LastAdminDeletionConcurrencyMysqlTest extends TestCase
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

    public function test_two_concurrent_deletes_of_two_different_admins_never_both_succeed_on_mysql(): void
    {
        $config = $this->mysqlConfig();

        if ($config === null) {
            $this->markTestSkipped(
                'No MySQL/MariaDB available (set BILLING_MYSQL_TEST_HOST etc). '.
                'This test SHOULD be run against real MySQL/MariaDB before this item is '.
                'considered done — this skip is a placeholder, not a pass.'
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

        // Deliberately bare PHPUnit\Framework\TestCase, same reasoning as
        // NumberingConcurrencyMysqlTest: this orchestrates child worker
        // processes rather than running inside the app's own
        // RefreshDatabase/sqlite test setup.
        $app = require $base.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        restore_error_handler();
        restore_exception_handler();

        Config::set('database.connections.admin_delete_concurrency_mysql', [
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

        $conn = DB::connection('admin_delete_concurrency_mysql');

        $adminAId = $conn->table('users')->insertGetId([
            'name' => 'Admin A', 'email' => 'admin-a-mysql@example.com', 'password' => bcrypt('password'),
            'is_admin' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminBId = $conn->table('users')->insertGetId([
            'name' => 'Admin B', 'email' => 'admin-b-mysql@example.com', 'password' => bcrypt('password'),
            'is_admin' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $worker = __DIR__.'/Support/admin_delete_worker.php';
        $processA = new Process([$php, $worker, (string) $adminAId], $base, $env);
        $processB = new Process([$php, $worker, (string) $adminBId], $base, $env);
        $processA->start();
        $processB->start();
        $processA->wait();
        $processB->wait();

        $outputA = trim($processA->getOutput());
        $outputB = trim($processB->getOutput());

        $this->assertTrue(str_starts_with($outputA, 'OK '), 'Worker A should not fail/throw: '.$outputA);
        $this->assertTrue(str_starts_with($outputB, 'OK '), 'Worker B should not fail/throw: '.$outputB);

        $results = [$outputA, $outputB];
        $this->assertContains('OK deleted', $results, 'Exactly one worker should have deleted its target.');
        $this->assertContains('OK refused', $results, 'Exactly one worker should have been refused by the last-admin guard.');
        $this->assertNotSame($outputA, $outputB, 'One must succeed and the other must be refused — never both the same.');

        $remainingAdmins = $conn->table('users')->where('is_admin', 1)->count();
        $this->assertSame(1, $remainingAdmins, 'Exactly one admin must remain — never zero, never two.');

        DB::purge('admin_delete_concurrency_mysql');
    }
}
