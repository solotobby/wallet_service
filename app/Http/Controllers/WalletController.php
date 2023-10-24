<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TransactionChannel;
use App\Http\Resources\WalletResource;

class WalletController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Retrieve the wallet for the given ID.
     *
     * @param  mixed $id
     * @return Response
     */
    public function show($id)
    {
        try {
            $wallet = Wallet::findOrFail($id);
        } catch (\Throwable $th) {
//            report($th);
            return response()->json("wallet not found", 500);
        }
        return response()->json(new WalletResource($wallet));
    }

    /**
     * Retrieve the wallet for the given User ID.
     *
     * @param  mixed $user_id
     * @return Response
     */
    public function showByUser($user_id)
    {
        try {
            $wallet = Wallet::where('user_id', $user_id)->firstOrFail();
        } catch (\Exception $exception) {
           // report($th);
            return response()->json("user wallet not found", 500);
        }
        return response()->json(new WalletResource($wallet));
    }

    public function debitWallet(Request $request)
    {
        $validated = $this->validate($request, [
            'user_id' => 'required',
            'amount' => 'required|numeric|min:50',
            'platform' => 'required|string',
            'trans_type' => 'required|string',
            'reference' => 'required|string'
        ]);

        try {
            $wallet = Wallet::where('user_id', $validated['user_id'])->first();
            if (is_null($wallet)) {
                return response()->json("wallet not found", 400);
            }
            if ($wallet->balance < $validated['amount']) {
                return response()->json("insufficient balance", 400);
            }
            \DB::transaction(function () use ($wallet, $validated) {
                $wallet->balance -= $validated['amount'];
                $wallet->save();
                //create transaction
                Transaction::create([
                    'reference' => $validated['reference'],
                    'wallet_id' => $wallet->id,//$validated['wallet_id'],
                    'category' => $validated['trans_type'],
                    'channel_id' => TransactionChannel::where('name', strtolower($validated['platform']))->firstOrFail()->id,
                    'amount' => -$validated['amount'],
                    'status' => 'successful'
                ]);
            });
        } catch (\Exception $exception) {
            //report($th);
            return response()->json($exception->getMessage(), 500);
        }
        return response()->json("debit was successful");
    }

    public function creditWallet(Request $request)
    {
        $validated = $this->validate($request, [
            'user_id' => 'required',
            'amount' => 'required|numeric|gt:0',
            'platform' => 'required|string',
            'trans_type' => 'required|string',
            'reference' => 'required|string'
        ]);

        try {
            $wallet = Wallet::where('user_id', $validated['user_id'])->first(); //where('id', $validated['wallet_id'])->orWhere('user_id', $validated['wallet_id'])->first();
            if (is_null($wallet)) {
                return response()->json("wallet not found", 400);
            }
            $transaction = \DB::transaction(function () use ($wallet, $validated) {
                $wallet->balance += $validated['amount'];
                $wallet->save();
                //create transaction
                return Transaction::create([
                    'reference' => $validated['reference'],
                    'wallet_id' => $wallet->id, //$validated['wallet_id'],
                    'category' => $validated['trans_type'],
                    'channel_id' => TransactionChannel::where('name', strtolower($validated['platform']))->firstOrFail()->id,
                    'amount' => $validated['amount'],
                    'status' => 'successful'
                ]);
            });
        } catch (\Exception $exception) {
            //report($th);
            return response()->json($exception->getMessage(), 500);
        }
        return response()->json($transaction);
    }

    public function createWallet(Request $request)
    {
        $validated = $this->validate($request, [
            'user_id' => 'required',
        ]);

        try {
            $wallet = Wallet::firstOrCreate(['user_id' => $validated['user_id'], 'balance' => '0.0', 'revenue_share_group' => 'audience']);
        }catch (\Exception $exception) {
//            report($th);
            return response()->json($exception->getMessage(), 500);
        }
        return response()->json($wallet);
    }

}
