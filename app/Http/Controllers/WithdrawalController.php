<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionChannel;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }
        
    /**
     * initializePayment
     *
     * @param  mixed $request
     * @return void
     */
    public function initializeWithdraw(Request $request)
    {
        $validated = $this->validate($request, [
            'wallet_id' => 'required|exists:wallets,id',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:100',
            'platform' => 'required|string'
        ]);

        try {
            // check if sufficient wallet balance
            $wallet = Wallet::findOrFail($validated['wallet_id']);
            if (is_null($wallet)) {
                return response()->json("wallet not found", 400);
            }
            if ($wallet->balance < $validated['amount']) {
                return response()->json("insufficient balance", 400);
            }

            $payload = [
                'amount' => $validated['amount'] - 60,
                'currency' => 'NGN',
                'debit_currency' => 'NGN',
                'narration' => $validated['description'],
                'account_bank' => $validated['bank_code'],
                'account_number' => $validated['account_number'],
                'reference' => (string) Str::uuid(),
                'callback_url' => $request->fullUrl().'/verify'
            ];

            $transaction = \DB::transaction(function () use ($wallet, $validated, $payload) {
                $wallet->balance -= $validated['amount'];
                $wallet->save();
                // flutterwave payout
                $withdrawInitialized = Http::withToken(env('FLUTTERWAVE_KEY'))->post(env('FLUTTERWAVE_BASE_URL').'/transfers', $payload);
                if ($withdrawInitialized->failed()) {
                    abort(400, $withdrawInitialized->message);
                }
                $withdrawInitialized =  $withdrawInitialized->json();
                //create transaction
                $transaction = new Transaction();
                $transaction->id = $withdrawInitialized['data']['reference'];
                $transaction->wallet_id = $validated['wallet_id'];
                $transaction->category = 'withdrawal';
                $transaction->channel_id = TransactionChannel::where('name', strtolower($validated['platform']))->firstOrFail()->id;
                $transaction->amount = $withdrawInitialized['data']['amount'];
                $transaction->status = $withdrawInitialized['data']['status'];
                $transaction->reference = $withdrawInitialized['data']['id'];
                $transaction->save();
                return $transaction;
            });
        } catch (\Throwable $th) {
            report($th);
            return response()->json("withdrawal cannot be initialized", 400);
        }
        return response()->json("withdrawal was successful");
    }
    
    /**
     * verifyTransaction
     *
     * @param  mixed $request
     * @return void
     */
    public function verifyTransaction(Request $request)
    {
        try {
            if ($request->has('data')) {
                $transaction = Transaction::findOrFail($request->data['reference']);
                $transaction->status = $request->data['status'];
                $transaction->save();
            }
        } catch (\Throwable $th) {
            report($th);
            return false;
        }
        return "OK";
    }
}