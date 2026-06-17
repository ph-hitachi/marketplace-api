<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Customer/Addresses
 */
class AddressController extends Controller
{
    /**
     * List addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return response()->json(['addresses' => $addresses]);
    }

    /**
     * Create address.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['country'] = $data['country'] ?? 'Philippines';

        // First address is automatically default
        if (! $request->user()->addresses()->exists()) {
            $data['is_default'] = true;
        }

        $address = Address::create($data);

        return response()->json(['address' => $address], 201);
    }

    /**
     * View address.
     */
    public function show(Request $request, Address $address): JsonResponse
    {
        $this->authorize('view', $address);

        return response()->json(['address' => $address]);
    }

    /**
     * Update address.
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->update($request->validated());

        return response()->json(['address' => $address]);
    }

    /**
     * Delete address.
     */
    public function destroy(Request $request, Address $address): \Illuminate\Http\Response
    {
        $this->authorize('delete', $address);

        $address->delete();

        return response()->noContent();
    }

    /**
     * Set default address.
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        $this->authorize('setDefault', $address);

        // Unset existing default
        $request->user()->addresses()->where('is_default', true)->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json(['message' => 'Default address updated.', 'address' => $address]);
    }
}
