<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionChannel;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

class KeyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function index()
    {
        try{
            $key['flutterwave'] = env('FLUTTERWAVE_KEY');
            $key['db'] = env('DB_DATABASE');
            $key['host'] = env('DB_HOST');
            $key['username'] = env('DB_USERNAME');
            $key['audience_url'] = env('AUDIENCE_URL');
            $key['env'] = env('APP_ENV');
        }catch (\Exception $exception){
            //report($exception);
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json([$key], 200);
    }


}
