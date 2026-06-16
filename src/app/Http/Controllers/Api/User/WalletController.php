<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWalletRequest;
use App\Http\Requests\Wallet\TopupRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags User/Wallet
 */
class WalletController extends Controller
{
    /**
     * List wallets.
     */
    public function index(Request $request): JsonResponse
    {
        $wallets = $request->user()->wallets()->latest()->get();
        return response()->json($wallets);
    }

    /**
     * Create wallet.
     */
    public function store(StoreWalletRequest $request): JsonResponse
    {
        $data = $request->validated();

        $isFirst = !$request->user()->wallets()->exists();

        $wallet = $request->user()->wallets()->create([
            'label'      => $data['label'],
            'is_default' => $isFirst,
        ]);
        $wallet->refresh();

        return response()->json([
            'message' => 'Wallet created successfully.',
            'wallet'  => $wallet,
        ], 201);
    }

    /**
     * View wallet.
     */
    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        return response()->json([
            'wallet'       => $wallet,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Topup wallet.
     */
    public function topup(TopupRequest $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('update', $wallet);

        $transaction = WalletTransaction::create([
            'wallet_id'   => $wallet->id,
            'type'        => 'topup',
            'amount'      => (float) $request->validated('amount'),
            'status'      => 'completed',
            'description' => 'Wallet top-up',
        ]);

        return response()->json([
            'message'     => 'Top-up successful.',
            'transaction' => $transaction,
            'balance'     => $transaction->balance_after,
        ], 201);
    }

    /**
     * Set default wallet.
     */
    public function setDefault(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('setDefault', $wallet);

        // Unset existing default for this user
        $request->user()->wallets()->where('is_default', true)->update(['is_default' => false]);

        $wallet->is_default = true;
        $wallet->save();

        return response()->json([
            'message' => 'Default wallet updated.',
            'wallet'  => $wallet,
        ]);
    }
}
