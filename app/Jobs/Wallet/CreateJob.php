<?php

namespace App\Jobs\Wallet;

use App\Jobs\Job;
use App\Models\Wallet;

class CreateJob extends Job
{
    protected $userID;
    protected $groupNum;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userID=null, $groupNum=0)
    {
        $this->userID = $userID;
        $this->groupNum = $groupNum;
        $this->onQueue(env('AWS_SQS_CREATEWALLET_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $wallet = Wallet::firstOrCreate([
                'user_id' => $this->userID
            ], [ 'revenue_share_group' => ($this->groupNum == 0) ? 'audience' : null ]);
        } catch (\Throwable $th) {
            report($th);
        }
    }

    /**
     * Execute jobs created outside lumen app.
     *
     * @return void
     */
    public function handleExternalJobs(\Illuminate\Contracts\Queue\Job $job)
    {
        try {
            $payload = $job->payload();
            $this->userID = $payload['data']['userID'];
            $this->groupNum = $payload['data']['groupNum'];
            $wallet = Wallet::firstOrCreate([
                'user_id' => $this->userID
            ], [ 'revenue_share_group' => ($this->groupNum == 0) ? 'audience' : null ]);
            $job->delete();
        } catch (\Throwable $th) {
            $job->fail($th);
        }
    }
}
