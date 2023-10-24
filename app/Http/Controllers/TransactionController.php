<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use App\Services\VerifyExternalTransaction;
use App\Services\GetTransactionCategoryID;
use App\Services\GetTransactionChannelID;
use App\Http\Resources\TransactionResource;
use Carbon\Carbon;

class TransactionController extends Controller
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
     * List all transactions for a given wallet ID
     *
     * @param  mixed $wallet_id
     * @return Response
     */
    public function index($wallet_id)
    {
        try {
            $transactions = Transaction::where('wallet_id', $wallet_id)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json("resource not found", 400);
        }
        return TransactionResource::collection($transactions);
    }
    
    /**
     * Retrieve the transaction for the given ID.
     *
     * @param  mixed $id
     * @return Response
     */
    public function show($id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json("resource not found", 400);
        }
        
        return new TransactionResource($transactions);
    }

    public function histories(Request $request, $wallet_id)
    {
        $validated = $this->validate($request, [
            'range' => 'nullable|integer'
        ]);

        try {
            $data = Transaction::where('wallet_id', $wallet_id)
                                    ->when(!is_null($validated['range']), function ($query) use ($validated) {
                                        $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                    })->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(["message" => "unable to fetch transaction histories"], 500);
        }
        return TransactionResource::collection($data);
    }
}
