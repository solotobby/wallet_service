<?php

namespace App\Jobs;

use App\Models\Wallet;
use App\Models\RevenueHistory;
use App\Models\TransactionChannel;
use App\Services\GetBatchAudience;

class DisburseRevenueJob extends Job
{
    protected $influencerID;
    protected $audienceID;
    protected $campaignID;
    protected $activityID;
    protected $channel;
    protected $revenueType;
    protected $revenue;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($influencerID=null, $audienceID, $campaignID, $activityID, $channel, $revenueType, $revenue=0)
    {
        $this->influencerID = $influencerID;
        $this->audienceID = $audienceID;
        $this->campaignID = $campaignID;
        $this->activityID = $activityID;
        $this->channel = $channel;
        $this->revenueType = $revenueType;
        $this->revenue = $revenue;
        $this->onQueue(env('AWS_SQS_DISBURSEADREVENUE_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            \DB::transaction(function () {
                // get channel ID from name
                $this->channel = TransactionChannel::where('name', $this->channel)->firstOrFail()->id;

                // disburse group 1 - platform owner
                $platformOwnerwallet = Wallet::where('revenue_share_group', 'platform_owner')->firstOrFail();
                $platformOwnerwallet->increment('balance', $this->computeRevenue(50));
                $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(50));

                // disburse group 2 - partner
                $partnerWallet = Wallet::where('revenue_share_group', 'partner')->firstOrFail();
                $partnerWallet->increment('balance', $this->computeRevenue(20));
                $this->createHistory($partnerWallet->id, $this->computeRevenue(20));

                // check if audience registered via an influencer at sign up
                $batchAudience = (new GetBatchAudience(['ids' => explode(" ", $this->audienceID)]))->run();
                if (count($batchAudience[0]) > 0 && $batchAudience[0]['referrer_id'] !== null) {
                    $defaultInfluencerWallet = Wallet::where('user_id', $batchAudience[0]['referrer_id'])->firstOrFail();
                    $defaultInfluencerWallet->increment('balance', $this->computeRevenue(5));
                    $this->createHistory($defaultInfluencerWallet->id, $this->computeRevenue(5));
                } else {
                    // if audience does not have influencer id, disburse revenue to platform owner
                    $platformOwnerwallet->increment('balance', $this->computeRevenue(5));
                    $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(5));
                }

                // disburse influencer. check that influencer is not the same as audience
                if (!is_null($this->influencerID) && $this->influencerID != $this->audienceID) {
                    $influencerWallet = Wallet::where('user_id', $this->influencerID)->firstOrFail();
                    $influencerWallet->increment('balance', $this->computeRevenue(25));
                    $this->createHistory($influencerWallet->id, $this->computeRevenue(25));
                } else {
                    // if influencer ID is null, give influencer value to platform owner
                    $platformOwnerwallet->increment('balance', $this->computeRevenue(25));
                    $this->createHistory($platformOwnerwallet->id, $this->computeRevenue(25));
                }
            });
        } catch (\Throwable $th) {
            report($th);
        }
    }

    public function computeRevenue($percent)
    {
        $revenue = 0;
        if ($this->revenueType == 'subscription') {
            $revenue = $this->revenue;
        } elseif ($this->revenueType == 'ads') {
            $revenue = 0.7; // 70 kobo per ad veiew
        }
        // 80% of revenue is split by stakeholders
        return ($revenue * 0.8) * ($percent / 100); 
    }

    public function createHistory($walletId, $amount)
    {
        RevenueHistory::create([
            'wallet_id' => $walletId,
            'category' => $this->revenueType,
            'channel_id' => $this->channel,
            'amount' => $amount,
            'campaign_id' => $this->campaignID,
            'activity_id' => $this->activityID
        ]);
    }
}
