<?php

namespace App\Services;

use App\Models\UpdateLog;
use App\Models\User;
use App\Notifications\UpdateApplied;
use App\Notifications\UpdateFailed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class UpdateService
{
    protected array $output = [];

    public function run(): void
    {
        if (! config('app.license_enforced')) {
            return;
        }

        $licenseService = app(LicenseService::class);
        $updateInfo = $licenseService->checkForUpdates();

        if (! ($updateInfo['ok'] ?? false) || ! ($updateInfo['update_available'] ?? false)) {
            return;
        }

        $fromVersion = config('app.version');
        $toVersion = $updateInfo['latest_version'];
        $gitTag = $updateInfo['git_tag'] ?? $toVersion;

        $log = UpdateLog::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'started',
            'started_at' => now(),
        ]);

        $backupPath = null;
        $dbBackupPath = null;

        try {
            [$backupPath, $dbBackupPath] = $this->createBackups();

            Artisan::call('down', ['--retry' => 60]);

            $this->runStep(['git', 'fetch', '--tags']);
            $this->runStep(['git', 'checkout', $gitTag]);
            $this->runStep(['composer', 'install', '--no-dev', '--optimize-autoloader']);
            $this->runStep(['php', 'artisan', 'migrate', '--force']);

            Artisan::call('config:clear');

            if (! $this->healthCheckPasses()) {
                throw new \RuntimeException('Health check failed after applying update.');
            }

            Artisan::call('up');

            $log->update([
                'status' => 'success',
                'output' => implode("\n", $this->output),
                'finished_at' => now(),
            ]);

            $this->notifySuperadmins(new UpdateApplied($fromVersion, $toVersion));
        } catch (\Throwable $e) {
            $this->output[] = 'ERROR: ' . $e->getMessage();

            $this->restoreBackups($backupPath, $dbBackupPath);

            Artisan::call('up');

            $log->update([
                'status' => 'rolled_back',
                'output' => implode("\n", $this->output),
                'finished_at' => now(),
            ]);

            $this->notifySuperadmins(new UpdateFailed($fromVersion, $toVersion, $e->getMessage()));
        }
    }

    protected function runStep(array $command): void
    {
        $process = new Process($command, base_path());
        $process->setTimeout(600);
        $process->run();

        $this->output[] = '$ ' . implode(' ', $command);
        $this->output[] = $process->getOutput();
        $this->output[] = $process->getErrorOutput();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Command failed: ' . implode(' ', $command));
        }
    }

    protected function createBackups(): array
    {
        $timestamp = now()->format('Y_m_d_His');
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $zipPath = "{$backupDir}/pre-update-{$timestamp}.zip";
        $this->zipDirectory(base_path(), $zipPath, [
            base_path('vendor'),
            base_path('node_modules'),
            base_path('storage/framework/cache'),
            base_path('.git'),
            $backupDir,
        ]);

        $dbBackupPath = "{$backupDir}/pre-update-{$timestamp}.sql";
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        $dumpProcess = new Process([
            'mysqldump',
            '-h', $dbConfig['host'] ?? '127.0.0.1',
            '-P', (string) ($dbConfig['port'] ?? 3306),
            '-u', $dbConfig['username'] ?? '',
            '--password=' . ($dbConfig['password'] ?? ''),
            $dbConfig['database'] ?? '',
        ]);
        $dumpProcess->setTimeout(600);
        $dumpProcess->run();

        if (! $dumpProcess->isSuccessful()) {
            throw new \RuntimeException('Database backup failed — aborting update without a DB backup.');
        }

        file_put_contents($dbBackupPath, $dumpProcess->getOutput());

        return [$zipPath, $dbBackupPath];
    }

    protected function zipDirectory(string $source, string $destination, array $excludes): void
    {
        $zip = new \ZipArchive();
        $zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getRealPath();

            foreach ($excludes as $exclude) {
                if (str_starts_with($path, $exclude)) {
                    continue 2;
                }
            }

            $relativePath = substr($path, strlen($source) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($path, $relativePath);
            }
        }

        $zip->close();
    }

    protected function restoreBackups(?string $backupPath, ?string $dbBackupPath): void
    {
        if ($backupPath && file_exists($backupPath)) {
            $zip = new \ZipArchive();
            if ($zip->open($backupPath) === true) {
                $zip->extractTo(base_path());
                $zip->close();
            }
        }

        if ($dbBackupPath && file_exists($dbBackupPath)) {
            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}");

            $restoreProcess = Process::fromShellCommandline(
                sprintf(
                    'mysql -h %s -P %s -u %s --password=%s %s < %s',
                    escapeshellarg($dbConfig['host'] ?? '127.0.0.1'),
                    escapeshellarg((string) ($dbConfig['port'] ?? 3306)),
                    escapeshellarg($dbConfig['username'] ?? ''),
                    escapeshellarg($dbConfig['password'] ?? ''),
                    escapeshellarg($dbConfig['database'] ?? ''),
                    escapeshellarg($dbBackupPath)
                )
            );
            $restoreProcess->setTimeout(600);
            $restoreProcess->run();

            $this->output[] = 'Restored DB backup: ' . $restoreProcess->getOutput() . $restoreProcess->getErrorOutput();
        }
    }

    protected function healthCheckPasses(): bool
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return false;
        }

        return config('app.version') !== null;
    }

    protected function notifySuperadmins($notification): void
    {
        User::role('superadmin')->get()->each(
            fn ($user) => $user->notify($notification)
        );
    }
}
