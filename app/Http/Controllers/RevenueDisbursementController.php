<?php

namespace App\Http\Controllers;

use App\Models\RevenueHistory;
use App\Models\TransactionChannel;
use App\Models\Wallet;
use App\Services\GetBatchAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RevenueDisbursementController extends Controller{

    public function index(Request $request)
    {
        $validated = $this->validate($request, [
            'channel' => 'required',
            'revenue_type' => 'required',
            'revenue' => 'sometimes',
            'influencer_id' => 'sometimes',
            'audience_id' => 'required',
            'campaign_id' => 'required',
            'activity_id' => 'required',
        ]);

       // dd($validated);

        try{

//            \DB::transaction(function ($validated) {
            \DB::transaction(function () use ($validated) {

                // get channel ID from name
                $channelID = TransactionChannel::where('name', $validated['channel'])->firstOrFail()->id;

                // disburse group 1 - platform owner
                $platformOwnerwallet = Wallet::where('revenue_share_group', 'platform_owner')->firstOrFail();
                $platformOwnerwallet->increment('balance', $this->computeRevenue(50, $validated['revenue_type'], $validated['revenue']));
                $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(50, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id'] );

                // disburse group 2 - partner
                $partnerWallet = Wallet::where('revenue_share_group', 'partner')->firstOrFail();
                $partnerWallet->increment('balance', $this->computeRevenue(20, $validated['revenue_type'], $validated['revenue']));
                $this->createHistory($partnerWallet->id, $this->computeRevenue(20, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id']);

//                // check if audience registered via an influencer at sign up
                $batchAudience = (new GetBatchAudience(['user_ids' => explode(" ", $validated['audience_id'])]))->run();
                if (count($batchAudience[0]) > 0 && $batchAudience[0]['referrer_id'] !== null) {
                    $defaultInfluencerWallet = Wallet::where('user_id', $batchAudience[0]['referrer_id'])->firstOrFail();
                    $defaultInfluencerWallet->increment('balance', $this->computeRevenue(5, $validated['revenue_type'], $validated['revenue']));
                    $this->createHistory($defaultInfluencerWallet->id, $this->computeRevenue(5, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id']);
                } else {
                    // if audience does not have influencer id, disburse revenue to platform owner
                    $platformOwnerwallet->increment('balance', $this->computeRevenue(5, $validated['revenue_type'], $validated['revenue']));
                    $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(5, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id'] );
                }

//                // disburse influencer. check that influencer is not the same as audience

                //if (!is_null($validated['influencer_id']) && $validated['influencer_id'] != $validated['audience_id']) {
                if($validated['influencer_id'] == null){
                    $platformOwnerwallet->increment('balance', $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']));
                    $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id'] );

                }elseif($validated['influencer_id'] != $validated['audience_id']){
                    $influencerWallet = Wallet::where('user_id', $validated['influencer_id'])->firstOrFail();
                    $influencerWallet->increment('balance', $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']));
                    $this->createHistory($influencerWallet->id, $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id']);
                } else {
                    // if influencer ID is null, give influencer value to platform owner
                    $platformOwnerwallet->increment('balance', $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']));
                    $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(25, $validated['revenue_type'], $validated['revenue']), $validated['revenue_type'], $validated['channel'], $validated['campaign_id'], $validated['activity_id'] );

                }
            },2);

        }catch (\Throwable $throwable){
            report($throwable);
            return response()->json("there was an error in disbursement", 400);
        }
        return response()->json('Disbursement completed', 200);
    }

    public function computeRevenue($percent, $revenue_type, $revenue_amount)
    {
        $revenue = 0;
        if ($revenue_type == 'subscription') {
            $revenue = $revenue_amount;
        } elseif ($revenue_type == 'ads') {
            $revenue = 0.7; // 70 kobo per ad veiew
        }
        // 80% of revenue is split by stakeholders
        return ($revenue * 0.8) * ($percent / 100);
    }

    public function createHistory($walletId, $amount, $revenue_type, $channel, $campaignID, $activityID)
    {
        RevenueHistory::create([
            'wallet_id' => $walletId,
            'category' => $revenue_type,
            'channel_id' => $channel,
            'amount' => $amount,
            'campaign_id' => $campaignID,
            'activity_id' => $activityID
        ]);
    }
}
