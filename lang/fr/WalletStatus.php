<?php

use App\Enums\Wallet\WalletStatus;

return [
    WalletStatus::PENDING => 'En attente',
    WalletStatus::APPROVED => 'Confirmé',
    WalletStatus::REJECTED => 'Rejeté',
];
