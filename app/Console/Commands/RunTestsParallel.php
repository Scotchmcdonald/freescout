<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunTestsParallel extends Command
{
    protected $signature = 'tests:parallel 
        {--detect : Run full problem detection}
        {--detect-quick : Run quick hang detection only}
        {--detect-fast : Fast batched detection (batch=20, uses binary search)}
        {--parallel-only : Run only parallel-safe tests}
        {--sequential-only : Run only sequential tests}  
        {--isolated-only : Run only isolated tests}
        {--filter= : Filter tests by pattern}
        {--processes= : Number of parallel processes}
        {--timeout=30 : Timeout per test in seconds}
        {--batch-size=1 : Detection batch size (higher=faster, uses binary search)}
        {--coverage : Generate coverage report}';

    protected $description = 'Run tests using intelligent parallel execution based on test roster';

    public function handle(): int
    {
        $cmd = ['php', base_path('scripts/parallel_test_runner.php')];
        
        if ($this->option('detect')) {
            $cmd[] = '--detect';
        }
        if ($this->option('detect-quick')) {
            $cmd[] = '--detect-quick';
        }
        if ($this->option('detect-fast')) {
            $cmd[] = '--detect-fast';
        }
        if ($this->option('parallel-only')) {
            $cmd[] = '--parallel-only';
        }
        if ($this->option('sequential-only')) {
            $cmd[] = '--sequential-only';
        }
        if ($this->option('isolated-only')) {
            $cmd[] = '--isolated-only';
        }
        if ($this->option('filter')) {
            $cmd[] = '--filter=' . $this->option('filter');
        }
        if ($this->option('processes')) {
            $cmd[] = '--processes=' . $this->option('processes');
        }
        if ($this->option('timeout')) {
            $cmd[] = '--timeout=' . $this->option('timeout');
        }
        if ($this->option('batch-size') && $this->option('batch-size') != 1) {
            $cmd[] = '--batch-size=' . $this->option('batch-size');
        }
        if ($this->option('coverage')) {
            $cmd[] = '--coverage';
        }
        if ($this->output->isVerbose()) {
            $cmd[] = '-v';
        }
        
        $process = new Process($cmd, base_path());
        $process->setTimeout(null);
        $process->setTty(Process::isTtySupported());
        
        return $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }
}
