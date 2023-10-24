<?php

namespace App\Http\Controllers;


use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletRewardController extends Controller
{
    public  function rewards($audience_id)
    {
        try{
            $wallet_id = Wallet::where('user_id', $audience_id)->first()->id;
            $reward = Transaction::where('category', 'game-play-reward-credit')->where('wallet_id', $wallet_id)->get();
            $data['total'] = $reward->sum('amount');
            //$data['list'] = $reward;
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'nessage' => $exception->getMessage()]);
        }
        return response()->json(['error' => false, 'message' => 'game play cash reward', 'data' => $data]);
    }
}
