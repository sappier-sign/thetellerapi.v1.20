<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 07/01/2018
 * Time: 11:51 AM
 */

namespace App\Console\Commands;
use App\Jobs\CheckInvoiceStatusJob;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class CheckInvoiceStatus extends Command
{
    protected $name = "check:invoice";
    
    protected $description = "Checks the status of momo invoices";

    protected function getArguments()
    {
        return [
//            [
//                'invoiceNo', InputArgument::REQUIRED, 'invoice number of the transaction generated by mtn'
//            ]
        ];
    }

    public function fire()
    {
        return dispatch(new CheckInvoiceStatusJob);
    }
}