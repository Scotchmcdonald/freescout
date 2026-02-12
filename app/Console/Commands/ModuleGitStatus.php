<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleGitStatus extends Command
{
    protected $signature = 'modules:git-status 
                            {--fetch : Fetch from remote before checking status}
                            {--short : Show condensed output}';

    protected $description = 'Check git status of app and all modules';

    public function handle(): int
    {
        $modulesPath = base_path('Modules');

        if (!File::isDirectory($modulesPath)) {
            $this->error('Modules directory not found');
            return 1;
        }

        $modules = collect(File::directories($modulesPath))->sort();

        // Include app repo as first item
        $paths = collect([base_path()])->merge($modules);

        $this->info("Checking git status for app + {$modules->count()} modules...\n");

        $results = [];

        foreach ($paths as $repoPath) {
            $isApp = $repoPath === base_path();
            $moduleName = $isApp ? 'App (freescout)' : basename($repoPath);
            $gitDir = $repoPath . '/.git';

            if (!File::isDirectory($gitDir)) {
                $results[] = [
                    'module' => $moduleName,
                    'status' => 'No git repo',
                    'branch' => '-',
                    'remote' => '-',
                    'changes' => '-',
                ];
                continue;
            }

            // Fetch if requested
            if ($this->option('fetch')) {
                exec("cd {$repoPath} && git fetch --quiet 2>/dev/null");
            }

            // Get current branch
            $branch = trim((string) shell_exec("cd {$repoPath} && git branch --show-current 2>/dev/null"));

            // Get remote URL
            $remote = trim((string) shell_exec("cd {$repoPath} && git remote get-url origin 2>/dev/null"));
            $remote = preg_replace('/https:\/\/[^@]+@/', 'https://', $remote) ?? ''; // Strip tokens
            $remote = str_replace('https://github.com/', '', $remote);
            $remote = str_replace('.git', '', $remote);

            // Get status
            $statusOutput = (string) shell_exec("cd {$repoPath} && git status --porcelain 2>/dev/null");
            $changes = substr_count($statusOutput, "\n");
            if (empty(trim($statusOutput))) {
                $changes = 0;
            }

            // Check ahead/behind
            $aheadBehind = trim((string) shell_exec("cd {$repoPath} && git rev-list --left-right --count HEAD...@{upstream} 2>/dev/null"));
            $ahead = 0;
            $behind = 0;
            if (preg_match('/(\d+)\s+(\d+)/', $aheadBehind, $matches)) {
                $ahead = (int) $matches[1];
                $behind = (int) $matches[2];
            }

            // Build status string
            $statusParts = [];
            if ($changes > 0) {
                $statusParts[] = "<fg=yellow>{$changes} changed</>";
            }
            if ($ahead > 0) {
                $statusParts[] = "<fg=cyan>↑{$ahead}</>";
            }
            if ($behind > 0) {
                $statusParts[] = "<fg=magenta>↓{$behind}</>";
            }
            if (empty($statusParts)) {
                $statusParts[] = '<fg=green>✓ Clean</>';
            }

            $results[] = [
                'module' => $moduleName,
                'status' => implode(' ', $statusParts),
                'branch' => $branch ?: '-',
                'remote' => $remote ?: '-',
                'changes' => $changes,
                'ahead' => $ahead,
                'behind' => $behind,
            ];
        }

        if ($this->option('short')) {
            $this->table(
                ['Module', 'Branch', 'Status'],
                collect($results)->map(fn($r) => [
                    $r['module'],
                    $r['branch'],
                    $r['status'],
                ])
            );
        } else {
            $this->table(
                ['Module', 'Branch', 'Remote', 'Status'],
                collect($results)->map(fn($r) => [
                    $r['module'],
                    $r['branch'],
                    $r['remote'],
                    $r['status'],
                ])
            );
        }

        // Summary
        $this->newLine();
        $totalChanges = (int) array_sum(array_column($results, 'changes'));
        $totalAhead = (int) array_sum(array_column($results, 'ahead'));
        $totalBehind = (int) array_sum(array_column($results, 'behind'));
        $noRepo = collect($results)->where('branch', '-')->count();

        if ($totalChanges > 0 || $totalAhead > 0 || $totalBehind > 0 || $noRepo > 0) {
            $this->warn("Summary: {$totalChanges} uncommitted changes, {$totalAhead} commits ahead, {$totalBehind} commits behind, {$noRepo} without repos");
        } else {
            $this->info('All modules are clean and up to date!');
        }

        return 0;
    }
}
