<?php

namespace App\Enums;

interface InvoiceStatus
{
    const UNPAID = 0;

    const PROCESSING = 2;

    const PAID = 3;
}
