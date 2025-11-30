<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ConsolidationService;
use Illuminate\Console\Command;

class AutoLockConsolidationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consolidations:auto-lock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically lock consolidations that have reached their cutoff time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for consolidations to auto-lock...');

        // Get system user for logging
        $systemUser = User::where('email', 'system@baraka.sanaa.co')->first();
        
        if (!$systemUser) {
            // Fallback to first super admin
            $systemUser = User::whereHas('role', function ($query) {
                $query->where('slug', 'super-admin');
            })->first();
        }

        if (!$systemUser) {
            $this->error('No system user found. Cannot proceed with auto-lock.');
            return self::FAILURE;
        }

        $locked = ConsolidationService::autoLockExpiredConsolidations($systemUser);

        $this->info("Successfully locked {$locked} consolidation(s).");

        return self::SUCCESS;
    }
}
