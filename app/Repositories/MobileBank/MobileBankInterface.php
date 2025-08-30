<?php

namespace App\Repositories\MobileBank;

use App\Models\Backend\MobileBank;

interface MobileBankInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function filter($request);
}
