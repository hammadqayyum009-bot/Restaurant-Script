<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Two real OS processes, sharing one on-disk SQLite file, both racing to
 * delete a *different* admin at the same moment — exactly the race
 * AdminUserDeleter guards against (see its class doc and the full-project
 * audit, "last-admin-delete race (TOCTOU)"). Mirrors
 * NumberingConcurrencySqliteTest's shape: real process concurrency proves no
 * crash, deadlock, or lost update happens against the engine that actually
 * needs it (SQLite serializes at the whole-file level regardless of
 * lockForUpdate() being a no-op there — see
 * LastAdminDeletionConcurrencyMysqlTest for the proof that the row lock
 * itself is what does the work on the engine this app ships on).
 *
 * Extends Tests\TestCase (not RefreshDatabase) purely to get a booted app for
 * the Config/DB facades used to seed and verify — every bit of application
 * code actually under test runs inside the child processes, against their
 * own throwaway on-disk SQLite file, never this process's own :memory:
 * database.
 */
class LastAdminDeletionConcurrencySqliteTest extends TestCase
{
    protected string $dbFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbFile = tempnam(sys_get_temp_dir(), 'admin_delete_concurrency_').'.sqlite';
        touch($this->dbFile);
    }

    protected function tearDown(): void
    {
        DB::purge('admin_delete_concurrency_sqlite');
        @unlink($this->dbFile);
        parent::tearDown();
    }

    protected function basePath(): string
    {
        return dirname(__DIR__, 3);
    }

    public function test_two_concurrent_deletes_of_two_different_admins_never_both_succeed(): void
    {
        $base = $this->basePath();
        $php = (new PhpExecutableFinder)->find() ?: 'php';

        $env = [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->dbFile,
            'APP_ENV' => 'testing',
        ];

        $migrate = new Process([$php, 'artisan', 'migrate', '--force'], $base, $env);
        $migrate->setTimeout(60);
        $migrate->run();
        $this->assertTrue($migrate->isSuccessful(), 'Migration failed: '.$migrate->getErrorOutput());

        Config::set('database.connections.admin_delete_concurrency_sqlite', [
            'driver' => 'sqlite',
            'database' => $this->dbFile,
            'foreign_key_constraints' => true,
        ]);
        $conn = DB::connection('admin_delete_concurrency_sqlite');

        $adminAId = $conn->table('users')->insertGetId([
            'name' => 'Admin A', 'email' => 'admin-a@example.com', 'password' => bcrypt('password'),
            'is_admin' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminBId = $conn->table('users')->insertGetId([
            'name' => 'Admin B', 'email' => 'admin-b@example.com', 'password' => bcrypt('password'),
            'is_admin' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::purge('admin_delete_concurrency_sqlite');

        // Real process concurrency, not a loop: both start before either waits.
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

        Config::set('database.connections.admin_delete_concurrency_sqlite', [
            'driver' => 'sqlite',
            'database' => $this->dbFile,
            'foreign_key_constraints' => true,
        ]);
        $remainingAdmins = DB::connection('admin_delete_concurrency_sqlite')
            ->table('users')->where('is_admin', 1)->count();

        $this->assertSame(1, $remainingAdmins, 'Exactly one admin must remain — never zero, never two.');
    }
}
