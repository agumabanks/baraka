<?php

namespace App\Repositories\Bank;

use App\Models\Backend\Bank;

interface BankInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data): Bank;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function filter($request);
}
