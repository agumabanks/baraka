<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add customs document and inspection fields (column by column check)
        $columnsToAdd = [
            'customs_required_documents' => ['type' => 'json', 'nullable' => true],
            'customs_documents' => ['type' => 'json', 'nullable' => true],
            'customs_document_notes' => ['type' => 'text', 'nullable' => true],
            'customs_documents_requested_at' => ['type' => 'timestamp', 'nullable' => true],
            'customs_documents_submitted_at' => ['type' => 'timestamp', 'nullable' => true],
            'customs_inspection_result' => ['type' => 'string', 'nullable' => true],
            'customs_inspection_notes' => ['type' => 'text', 'nullable' => true],
            'customs_inspection_findings' => ['type' => 'json', 'nullable' => true],
            'customs_inspection_at' => ['type' => 'timestamp', 'nullable' => true],
            'customs_inspector_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'customs_hs_code' => ['type' => 'string', 'nullable' => true],
            'customs_duty_amount' => ['type' => 'decimal', 'nullable' => true, 'precision' => 12, 'scale' => 2],
            'customs_tax_amount' => ['type' => 'decimal', 'nullable' => true, 'precision' => 12, 'scale' => 2],
            'customs_total_due' => ['type' => 'decimal', 'nullable' => true, 'precision' => 12, 'scale' => 2],
            'customs_duty_currency' => ['type' => 'string', 'nullable' => true, 'length' => 3],
            'customs_duty_assessed_at' => ['type' => 'timestamp', 'nullable' => true],
            'customs_duty_paid' => ['type' => 'decimal', 'nullable' => true, 'precision' => 12, 'scale' => 2],
            'customs_duty_payment_method' => ['type' => 'string', 'nullable' => true],
            'customs_duty_payment_reference' => ['type' => 'string', 'nullable' => true],
            'customs_duty_paid_at' => ['type' => 'timestamp', 'nullable' => true],
            'customs_clearance_number' => ['type' => 'string', 'nullable' => true],
            'customs_cleared_by' => ['type' => 'unsignedBigInteger', 'nullable' => true],
            'customs_metadata' => ['type' => 'json', 'nullable' => true],
        ];

        Schema::table('shipments', function (Blueprint $table) use ($columnsToAdd) {
            foreach ($columnsToAdd as $column => $config) {
                if (!Schema::hasColumn('shipments', $column)) {
                    $col = match($config['type']) {
                        'json' => $table->json($column),
                        'text' => $table->text($column),
                        'timestamp' => $table->timestamp($column),
                        'unsignedBigInteger' => $table->unsignedBigInteger($column),
                        'decimal' => $table->decimal($column, $config['precision'] ?? 12, $config['scale'] ?? 2),
                        default => isset($config['length']) ? $table->string($column, $config['length']) : $table->string($column),
                    };
                    if ($config['nullable'] ?? false) {
                        $col->nullable();
                    }
                }
            }
        });

        // Try to add indexes (ignore if already exist)
        try {
            Schema::table('shipments', function (Blueprint $table) {
                $table->index('credit_hold', 'shipments_credit_hold_idx');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        try {
            Schema::table('shipments', function (Blueprint $table) {
                $table->index('customs_status', 'shipments_customs_status_idx');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['credit_hold']);
            $table->dropIndex(['customs_status']);
            
            $columns = [
                'credit_hold', 'credit_hold_reason', 'credit_hold_at', 
                'credit_hold_released_at', 'credit_hold_released_by', 'credit_hold_release_notes',
                'customs_status', 'customs_hold_reason', 'customs_hold_at',
                'customs_required_documents', 'customs_documents', 'customs_document_notes',
                'customs_documents_requested_at', 'customs_documents_submitted_at',
                'customs_inspection_result', 'customs_inspection_notes', 'customs_inspection_findings',
                'customs_inspection_at', 'customs_inspector_id',
                'customs_hs_code', 'customs_duty_amount', 'customs_tax_amount', 
                'customs_total_due', 'customs_duty_currency', 'customs_duty_assessed_at',
                'customs_duty_paid', 'customs_duty_payment_method', 'customs_duty_payment_reference',
                'customs_duty_paid_at', 'customs_clearance_number', 'customs_cleared_at',
                'customs_cleared_by', 'customs_metadata',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
