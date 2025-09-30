<?php

namespace App\Repositories\MobileBank;

use App\Models\Backend\MobileBank;

class MobileBankRepository implements MobileBankInterface
{
    public function all()
    {
        return MobileBank::paginate();
    }

    public function find(int $id)
    {
        return MobileBank::findOrFail($id);
    }

    public function create(array $data): MobileBank
    {
        return MobileBank::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $mobileBank = $this->find($id);
        if ($mobileBank) {
            return $mobileBank->update($data);
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $mobileBank = $this->find($id);
        if ($mobileBank) {
            return $mobileBank->delete();
        }

        return false;
    }

    public function filter($request)
    {
        $query = MobileBank::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        return $query->paginate();
    }
}
