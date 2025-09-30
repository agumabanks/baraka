<?php

namespace App\Enums;

interface PayoutSetup
{
    const STRIPE = 1; //

    const SSL_COMMERZ = 2; //

    const PAYPAL = 3; //

    const PAYONEER = 4;

    const BKASH = 5; //

    const VISA = 6;

    const SKRILL = 7; //

    const AAMARPAY = 8; //

    const RAZORPAY = 9; //

    const PAYSTACK = 10; //

    const OFFLINE = 11; //

    const PAYMOB = 12;
}
