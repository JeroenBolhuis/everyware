<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreshDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fresh-databases {--seed : Seed the databases after refreshing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the main and personal databases together';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Wiping the personal database...');
        $this->wipePersonalDatabase();

        $this->info('Refreshing the main database...');
        $this->call('migrate:fresh', ['--force' => true]);

        if ($this->option('seed')) {
            $this->info('Seeding both databases...');
            $this->call('db:seed', ['--force' => true]);
        }

        return self::SUCCESS;
    }

    private function wipePersonalDatabase(): void
    {
        $connection = DB::connection('personal');
        $driver = $connection->getDriverName();
        $tables = $this->personalTableNames($driver);

        if ($tables === []) {
            return;
        }

        if ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($tables as $table) {
            $connection->statement('DROP TABLE IF EXISTS '.$this->wrapIdentifier($table, $driver).($driver === 'pgsql' ? ' CASCADE' : ''));
        }

        if ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * @return array<int, string>
     */
    private function personalTableNames(string $driver): array
    {
        $connection = DB::connection('personal');

        if ($driver === 'sqlite') {
            return collect($connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            ))->map(fn (object $row): string => $row->name)->all();
        }

        if ($driver === 'pgsql') {
            return collect($connection->select(
                'SELECT tablename FROM pg_tables WHERE schemaname = current_schema() ORDER BY tablename'
            ))->map(fn (object $row): string => $row->tablename)->all();
        }

        return collect($connection->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'))
            ->map(fn (object $row): string => array_values((array) $row)[0])
            ->all();
    }

    private function wrapIdentifier(string $value, string $driver): string
    {
        return in_array($driver, ['pgsql', 'sqlite'], true)
            ? '"'.str_replace('"', '""', $value).'"'
            : '`'.str_replace('`', '``', $value).'`';
    }
}
