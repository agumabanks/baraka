<?php

namespace App\Console\Commands;

use App\Services\Shared\InvoiceQueryService;
use Illuminate\Console\Command;

/**
 * Mark Overdue Invoices Command
 * 
 * Automatically marks invoices as OVERDUE when they pass their due date.
 * Should be scheduled to run daily.
 */
class MarkOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invoices:mark-overdue
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Mark invoices as overdue when they pass their due date';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceQueryService $invoiceService): int
    {
        $this->info('Starting overdue invoice marking process...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
            
            $count = \App\Models\Invoice::where('status', 'PENDING')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count();
            
            $this->info("Would mark {$count} invoices as overdue");
            return Command::SUCCESS;
        }

        $count = $invoiceService->markOverdueInvoices();

        $this->info("Marked {$count} invoices as overdue");

        return Command::SUCCESS;
    }
}
