<?php

namespace App\Jobs;

use App\Mtn;
use Carbon\Carbon;

class CheckInvoiceStatusJob extends Job
{
    protected $invoiceNo;

    public $tries = 3;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mtn = new Mtn();
        $purchases = Mtn::where('responseCode', '21VD')->whereDate('created_at', Carbon::today()->toDateString())->orderBy('created_at', 'desc')->limit(500)->get();
        foreach ($purchases as $purchase) {
            $mtn->checkInvoiceOffline($purchase->invoiceNo);
        }
    }
}
