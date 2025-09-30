<?php

namespace App\Repositories\Accident;

interface AccidentInterface
{
    public function all();

    public function get($id);

    public function store($request);

    public function update($id, $request);

    public function delete($id);
}
