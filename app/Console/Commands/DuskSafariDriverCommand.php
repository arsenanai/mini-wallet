<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DuskSafariDriverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:safari-driver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the SafariDriver process.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting SafariDriver...');

        $process = new Process(['/usr/bin/safaridriver', '-p', '4444'], base_path());

        $process->setTimeout(null)->run(function ($type, $line) {
            $this->output->write($line);
        });

        if (! $process->isSuccessful()) {
            $this->error('SafariDriver failed to start.');

            return Command::FAILURE;
        }

        $this->info('SafariDriver stopped.');

        return Command::SUCCESS;
    }
}
