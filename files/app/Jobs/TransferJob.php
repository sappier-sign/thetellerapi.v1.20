<?php

namespace App\Jobs;

use App\Transaction;

class TransferJob extends Job
{
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
        $transactions = Transaction::where('fld_039', '102')->orderBy('fld_012', 'desc')->limit(500)->get();
        $transfer = new Transaction();
        foreach($transactions as $transaction) {
            $transfer->credit($transaction, '');
        }
    }
}
