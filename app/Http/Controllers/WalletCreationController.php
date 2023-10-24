<?php

namespace App\Http\Controllers;


use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletCreationController extends Controller{

    public function walletCreation(Request $request)
    {
        $return = dd($request);
        $validated = $this->validate($request, [
            'user_id' => 'required',
        ]);
        try {
            $wallet = Wallet::create(['user_id' => $validated['user_id'], 'balance' => '0.0', 'revenue_share_group' => 'audience']);
        }catch (\Throwable $th) {
            report($th);
            return response()->json("wallet could not be created", 400);
        }
        return response()->json($return);
    }
}
