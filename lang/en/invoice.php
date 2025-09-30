<?php

use App\Enums\InvoiceStatus;

return [
    'id' => 'ID',
    InvoiceStatus::PAID => 'PAID',
    InvoiceStatus::UNPAID => 'UNPAID',
    InvoiceStatus::PROCESSING => 'PROCESSING',
    'paid_out' => 'Paid Out',
    'invoice' => 'Invoice',
    'status_updated' => 'Invoice Status Updated successfully',
    'status_update' => 'Status Update',
    'paid_invoice' => 'Paid invoice',

    'invoice_generated_successfully' => 'Invoice Generated successfully',
    'invoice_generate_menually' => 'Invoice Generate',
    'generate' => 'Generate',
    'invoice_description' => 'After clicking the Generate button, the invoice will be generated depending on the payment period of the merchant.',

];
