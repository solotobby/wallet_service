<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionChannel;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TopUpController extends Controller
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
    public function initializePayment(Request $request)
    {
        $validated = $this->validate($request, [
            'wallet_id' => 'required',//|exists:wallets,id',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1000',
            'platform' => 'required|string|exists:transaction_channels,name',
            'redirect_url' => 'required|url'
        ]);

        try {
            // get channel info from platform name
            $channel = TransactionChannel::where('name', strtolower($validated['platform']))->first();

            $payload = [
                'tx_ref' => $validated['platform'].'-tx-'.time(),
                'amount' => $validated['amount'],
                'currency' => 'NGN',
                'redirect_url' => $validated['redirect_url'],
                'payment_options' => 'card',
                'meta' => [
                    'wallet_id' => $validated['wallet_id'],
                    'platform' => $validated['platform']
                ],
                'customer' => [
                    'email' => $validated['email']
                ],
                'customizations' => [
                    'title' => $validated['platform'].' Payments',
                    'description' => 'Fund your wallet',
                    'logo' => (is_null($channel->logo)) ? 'https://brandmobileafrica.com/images/bma-logo.png' : $channel->logo
                ]
            ];
            $paymentInitialized = Http::withToken(env('FLUTTERWAVE_KEY'))->post(env('FLUTTERWAVE_BASE_URL').'/payments', $payload)->throw()->json();

        }catch (\Exception $exception){
            //report($exception);
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json([$paymentInitialized], 200);
    }

    /**
     * verifyTransaction
     *
     * @param  mixed $request
     * @return void
     */
    public function verifyTransaction(Request $request)
    {
        $validated = $this->validate($request, [
            'wallet_id' => 'required',//|exists:wallets,id',
            'transaction_id' => 'required|numeric',
            'platform' => 'required|string'
        ]);

        try {

            $url = env('FLUTTERWAVE_BASE_URL')."/transactions/".$validated['transaction_id']."/verify";

            $verifyTrans = Http::withToken(env('FLUTTERWAVE_KEY'))->get($url)->throw()->json();

            //if ($verifyTrans['data']['status'] != 'successful' || $verifyTrans['data']['meta']['wallet_id'] != $validated['wallet_id']) {
            if ($verifyTrans['data']['status'] != 'successful') {
                return response()->json("Transaction cannot be verified", 400);
            }

            // check if transaction_id has been claimed
            $existingTrans = Transaction::where('reference', $validated['transaction_id'])->where('status', 'successful')->first();
            if ($existingTrans) {
                return response()->json("Payment already verified and value assigned to wallet", 400);
            }

            \DB::transaction(function () use ($verifyTrans, $validated) {

                $wallet = Wallet::findOrFail($validated['wallet_id']);
                if ($verifyTrans['data']['status'] == 'successful') {
                    // update wallet
                    $wallet->balance += (double) $verifyTrans['data']['amount_settled'];
                    $wallet->save();
                }
//                 create Or update transaction history
                Transaction::updateOrCreate([
                    'reference' => $verifyTrans['data']['id'],
                    'wallet_id' => $validated['wallet_id'],
                    'category' => 'top_up',
                    'channel_id' => TransactionChannel::where('name', strtolower($validated['platform']))->firstOrFail()->id,
                ], [
                    'amount' => $verifyTrans['data']['amount_settled'],
                    'status' => $verifyTrans['data']['status']
                ]);
            });
        }catch (\Exception $exception){
            //report($exception);
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }

        return response()->json("transaction verified, wallet balance updated");
    }
}
