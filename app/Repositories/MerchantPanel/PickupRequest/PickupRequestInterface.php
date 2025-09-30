<?php

namespace App\Repositories\MerchantPanel\PickupRequest;

interface PickupRequestInterface
{
    public function getRegular();

    public function getExpress();

    public function regularStore($request);

    public function expressStore($request);
}
