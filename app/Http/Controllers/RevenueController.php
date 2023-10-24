<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\RevenueHistory;
use Carbon\Carbon;

class RevenueController extends Controller
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
     * getDailyRevenue
     *
     * @param  mixed $campaign_id
     * @return void
     */
    public function getDailyRevenueByCampaign($campaign_id)
    {
        try {
            $data = [];
            // ad revenue is split between 4 stakeholders; either 4 revenue ad is 1 ad view ---> same goes for subscription
            // hence we select distinct while trying to get total revenue ads or total revenue subscription
            $revenueAds = RevenueHistory::where('campaign_id', $campaign_id)->where('category', 'ads')->distinct('activity_id')->whereDate('created_at', Carbon::now()->toDateString())->count();
            $revenueSubscriptions = RevenueHistory::where('campaign_id', $campaign_id)->where('category', 'subscription')->distinct('activity_id')->whereDate('created_at', Carbon::now()->toDateString())->count();
            $data['eighty_percent_revenue'] = (double) RevenueHistory::where('campaign_id', $campaign_id)->whereDate('created_at', Carbon::now()->toDateString())->sum('amount');
            $data['hundred_percent_revenue'] = ($revenueAds * 0.7) + ($revenueSubscriptions * 50);
            $data['available_for_reward'] = $data['hundred_percent_revenue'] - $data['eighty_percent_revenue'];
        } catch (\Exception $exception) {
            //report($th);
            return response()->json(["error" => false, "message" => $exception->getMessage()], 500);
        }
        return response()->json($data, 200);
    }

    public function getMonthlyRevenueByCampaign($campaign_id)
    {
        try {
            $data = [];
            // ad revenue is split between 4 stakeholders; either 4 revenue ad is 1 ad view ---> same goes for subscription
            // hence we select distinct while trying to get total revenue ads or total revenue subscription
            $revenueAds = RevenueHistory::where('campaign_id', $campaign_id)->where('category', 'ads')->distinct('activity_id')->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->count();
            $revenueSubscriptions = RevenueHistory::where('campaign_id', $campaign_id)->where('category', 'subscription')->distinct('activity_id')->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->count();
            $data['eighty_percent_revenue'] = (double) RevenueHistory::where('campaign_id', $campaign_id)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->sum('amount');
            $data['hundred_percent_revenue'] = ($revenueAds * 0.7) + ($revenueSubscriptions * 50);
            $data['available_for_reward'] = $data['hundred_percent_revenue'] - $data['eighty_percent_revenue'];
        } catch (\Exception $exception) {
            //report($th);
            return response()->json(["error" => true, "message" => $exception->getMessage()], 500);
        }
        return response()->json($data, 200);
    }

    public function revenueSummary(Request $request)
    {
        $validated = $this->validate($request, [
            'start' => 'nullable|date',
            'end' => 'nullable|date',
            'wallet_id' => 'required|string',
            'range' => 'nullable|integer'
        ]);

        try {
            $data = [];
            $data['revenue'] = RevenueHistory::where('wallet_id', $validated['wallet_id'])
                                    ->when(!is_null($validated['range']), function ($query) use ($validated) {
                                        $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                    })->sum('amount');
        } catch (\Exception $exception) {
            //report($th);
            return response()->json(["error"=>true, "message" => $exception->getMessage()], 500);
        }
        return response()->json(["data" => $data]);
    }
}
