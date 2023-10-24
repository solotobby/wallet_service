<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionChannel;

class TransactionChannelController extends Controller
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
     * List all channels
     *
     * @return Response
     */
    public function index()
    {
        try {
            $channels = TransactionChannel::get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'channels List', 'data' => $channels]);
    }
    
    /**
     * Retrieve the channel for the given ID.
     *
     * @param  mixed $id
     * @return Response
     */
    public function show($id)
    {
        try {
            $channel = TransactionChannel::findOrFail($id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Show Channel', 'data' => $channel]);
    }
    
    /**
     * create a new channel for a given user
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $validated = $this->validate($request, ['name' => 'required|string']);
        try {
            $channel = TransactionChannel::firstOrCreate(['name' => $validated['name']]);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Channel created', 'data' => $channel]);
    }
}
