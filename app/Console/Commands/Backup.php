<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Database backup via mysqldump into storage/app/backups. Media lives in
 * object storage (R2/S3/B2/Spaces) which is durable + independently versioned,
 * so it is intentionally not dumped here.
 */
class Backup extends Command
{
    protected $signature = 'maal:backup {--keep=14 : Number of recent backups to retain}';

    protected $description = 'Create a compressed database backup and prune old ones.';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->warn('Backup command targets MySQL; current connection is '.config('database.default').'. Skipping.');

            return self::SUCCESS;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $db = config('database.connections.mysql');
        $file = $dir.'/maal-'.now()->format('Y-m-d_His').'.sql.gz';

        $command = sprintf(
            'mysqldump --single-transaction --quick --no-tablespaces -h%s -P%s -u%s %s %s | gzip > %s',
            escapeshellarg((string) $db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg((string) $db['username']),
            $db['password'] ? '-p'.escapeshellarg((string) $db['password']) : '',
            escapeshellarg((string) $db['database']),
            escapeshellarg($file),
        );

        $this->info('Backing up database…');
        $result = Process::timeout(1800)->run(['bash', '-c', $command]);

        if (! $result->successful()) {
            $this->error('Backup failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $this->prune($dir, (int) $this->option('keep'));
        $this->info('Backup written to '.$file);

        return self::SUCCESS;
    }

    protected function prune(string $dir, int $keep): void
    {
        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        $files->slice($keep)->each(fn ($f) => File::delete($f->getPathname()));
    }
}
