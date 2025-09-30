<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddressBook;
use Illuminate\Http\Request;

class AddressBookController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AddressBook::class);
        $items = AddressBook::query()->latest('id')->paginate(15);

        return view('backend.admin.address_book.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', AddressBook::class);

        return view('backend.admin.address_book.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AddressBook::class);
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|in:shipper,consignee,payer',
            'name' => 'required|string',
            'phone_e164' => 'required|string',
            'email' => 'nullable|email',
            'country' => 'required|string|size:2',
            'city' => 'required|string',
            'address_line' => 'required|string',
            'tax_id' => 'nullable|string',
        ]);
        $ab = AddressBook::create($data);

        return redirect()->route('admin.address-book.show', $ab);
    }

    public function show(AddressBook $address_book)
    {
        $this->authorize('view', $address_book);

        return view('backend.admin.address_book.show', ['address' => $address_book]);
    }

    public function edit(AddressBook $address_book)
    {
        $this->authorize('update', $address_book);

        return view('backend.admin.address_book.edit', ['address' => $address_book]);
    }

    public function update(Request $request, AddressBook $address_book)
    {
        $this->authorize('update', $address_book);
        $address_book->update($request->only(['name', 'phone_e164', 'email', 'city', 'address_line']));

        return back()->with('status', 'Address updated');
    }

    public function destroy(AddressBook $address_book)
    {
        $this->authorize('delete', $address_book);
        $address_book->delete();

        return redirect()->route('admin.address-book.index');
    }
}
