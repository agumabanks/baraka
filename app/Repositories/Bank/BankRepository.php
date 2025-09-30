<?php

namespace App\Repositories\Bank;

use App\Models\Backend\Bank;

class BankRepository implements BankInterface
{
    public function all()
    {
        return Bank::paginate();
    }

    public function find(int $id)
    {
        return Bank::findOrFail($id);
    }

    public function create(array $data): Bank
    {
        return Bank::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $bank = $this->find($id);
        if ($bank) {
            return $bank->update($data);
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $bank = $this->find($id);
        if ($bank) {
            return $bank->delete();
        }

        return false;
    }

    public function filter($request)
    {
        $query = Bank::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        return $query->paginate();
    }
}
