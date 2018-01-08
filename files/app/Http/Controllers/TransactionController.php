<?php

namespace App\Http\Controllers;

use App\Functions;
use App\Transaction;
use Carbon\Carbon;
use Faker\Provider\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    protected $transaction;
    protected $message;
    protected $path;
    protected $stan;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transaction = new Transaction();
        $this->stan = Transaction::generateStan();
        $this->path = storage_path('logs_ttm_messages/'.$this->stan.'.txt');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function validateFieldCount(Request $request)
    {
        if ( ($request->path() === 'purchase.do' ) && (count($request->all()) > 12 ) ){
            return [
                'status'    =>  'format error',
                'code'      =>  660,
                'reason'    =>  'too many fields in request body'
            ];
        } elseif ( ($request->path() === 'transfer.do' ) && (count($request->all()) > 15) ) {
            return [
                'status'    =>  'format error',
                'code'      =>  660,
                'reason'    =>  'too many fields in request body'
            ];
        }
    }

    public function writeMerchantRequestOpen(Request $request)
    {
        $this->message = "<<<START TRANS ".$this->stan."\r\n"; // start request log message
        $this->message .= "\t<<<".Carbon::now()->toTimeString()." START MERCHANT TO TTLR REQUEST FOREIGN\r\n"; // start merchant request message

        foreach ($request->all() as $index => $value) { // loop through request and create message
            $this->message .= "\t\t".str_pad($index, 20, ' ', 1)." :\t$value\r\n"; // format the index and values for proper alignment
        }

        $this->message .= "\t<<<".Carbon::now()->toTimeString()." END MERCHANT TO TTLR REQUEST FOREIGN\r\n\r\n"; // end merchant request message
        Functions::writeRequestWithTimestamp($this->path, $this->message);
    }

    public function writeMerchantRequestClose()
    {
        Functions::writeRequestWithTimestamp($this->path, "<<<END TRANS ".$this->stan."\r\n\r\n");
        $content = fread(fopen($this->path, 'r'), filesize($this->path));
        unlink($this->path);
        $file = fopen(storage_path('logs_ttm_messages/'.date('Ymd').'.txt'), 'a+');
        fwrite($file, $content);
        fclose($file);
    }

    public function purchase(Request $request)
    {
        if ($failed = $this->validateFieldCount($request)){
            return $failed;
        };
        $this->validate($request, [
            'processing_code'   =>  'bail|required|digits:6|in:000000,000100,000200',
            'merchant_id'       =>  'bail|required|size:12',
            'amount'            =>  'bail|required|digits:12',
            'subscriber_number' =>  'bail|required_if:processing_code,000200|digits:10',
            'pan'               =>  'bail|required_if:processing_code,000000,000100|digits:16|required_with:cvv,exp_month,exp_year',
            'r_switch'          =>  'bail|required|in:VIS,MAS,MTN,VDF,TGO|size:3',
            'transaction_id'    =>  'bail|required|digits:12',
            'desc'              =>  'bail|required|max:100',
            '3d_url_response'   =>  'bail|required_with:pan,cvv,exp_month,exp_year|url',
            'cvv'               =>  'bail|digits:3|required_with:pan',
            'exp_month'         =>  'bail|digits:2|required_with:pan',
            'exp_year'          =>  'bail|digits:2|required_with:pan',
        ]);
        $this->writeMerchantRequestOpen($request);
        $response = $this->transaction->debit($request, $this->path);
        $this->writeMerchantRequestClose();
        return $response;
    }

    public function transfer(Request $request)
    {
        if ($failed = $this->validateFieldCount($request)){
            return $failed;
        };

        $this->validate($request, [
            'processing_code'   =>  'bail|required|digits:6|in:400000,400100,400110,400120,400130,400200,400210,400220',
            'merchant_id'       =>  'bail|required|size:12',
            'amount'            =>  'bail|required|digits:12',
            'subscriber_number' =>  'bail|required_if:processing_code,000200|digits:10',
            'pan'               =>  'bail|required_if:processing_code,400000,400100,400110,400120,400130,400200,400210,400220|digits:16|required_with:cvv,exp_month,exp_year',
            'r_switch'          =>  'bail|required|in:VIS,MAS,MTN,VDF,TGO|size:3',
            'transaction_id'    =>  'bail|required|digits:12',
            'desc'              =>  'bail|required|max:100',
            '3d_url_response'   =>  'bail|required_with:pan,cvv,exp_month,exp_year|active_url',
            'cvv'               =>  'bail|digits:3|required_with:pan',
            'exp_month'         =>  'bail|digits:2|required_with:pan',
            'exp_year'          =>  'bail|digits:2|required_with:pan',
            'account_issuer'    =>  'bail|required|min:2|max:20',
            'account_number'    =>  'bail|required|min:6|max:30'
        ]);
        $this->writeMerchantRequestOpen($request);
        $response = $this->transaction->debit($request, $this->path);
        $this->writeMerchantRequestClose();
        return $response;
    }

    public function getTransactionById()
    {
        
    }

    public function getTransactions()
    {

    }
}
