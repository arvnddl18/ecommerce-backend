<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('stripe:listen {--forward-to= : Destination URL for webhook forwarding}')]
#[Description('Forward live Stripe webhooks to the local application endpoint using the Stripe CLI.')]
class StripeListenCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $destination = $this->option('forward-to') ?? 'http://localhost:8000/api/v1/webhooks/stripe';

        $this->info('Starting Stripe CLI webhook listener...');
        $this->line("Forwarding destination: <comment>{$destination}</comment>");
        $this->line('Ensure your development server is running (`php artisan serve`).');
        $this->newLine();

        $process = new Process(['stripe', 'listen', '--forward-to', $destination]);
        $process->setTimeout(null);

        try {
            $process->run(function ($type, $buffer): void {
                $this->output->write($buffer);
            });
        } catch (\Throwable $e) {
            $this->error('Failed to run Stripe CLI: '.$e->getMessage());
            $this->line('Please ensure the Stripe CLI is installed and in your PATH.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
