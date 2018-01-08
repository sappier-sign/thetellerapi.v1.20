<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 08/01/2018
 * Time: 2:48 AM
 */

namespace App\Console\Commands;


use App\Jobs\TransferJob;
use Illuminate\Console\Command;

class Transfer extends Command
{
    protected $name = 'transaction:transfer';

    protected $description = 'Completes transfer transactions';

    public function fire(){
        return dispatch(new TransferJob);
    }
}