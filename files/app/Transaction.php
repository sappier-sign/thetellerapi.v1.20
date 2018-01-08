<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 06/01/2018
 * Time: 9:12 AM
 */

namespace App;

use App\Airtel;
use App\Http\Controllers\TransactionController;
use App\Jobs\TransferJob;
use App\Tigo;
use App\Vodafone;
use App\Zenith;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    CONST CREATED_AT = 'fld_012';
    public $incrementing = false;
    protected $primaryKey = 'fld_011';
    protected $table = 'logs_ttm_messages';
    protected $fillable = [
        'fld_002', // account number
        'fld_003', // processing_code
        'fld_004', // transaction amount
        'fld_009', // device type
        'fld_011', // stan
        'fld_012', // transaction date
        'fld_014', // card expiration date
        'fld_037', // reference
        'fld_038', // approved code
        'fld_039', // response code
        'fld_041', // Terminal ID
        'fld_042', // Merchant ID
        'fld_043', // merchant name and location
        'fld_057', // R - Switch
        'fld_103', // To-Account Number
        'fld_116', // Description
        'fld_117', // To-Account Issuer
        'fld_123', // cvv
        'rfu_001', // reserved for future use
        'rfu_002', // reserved for future use
        'rfu_003', // reserved for future use
        'rfu_004', // reserved for future use
        'rfu_005', // reserved for future use
    ];
    protected $message;

    public function debit(Request $request, $path)
    {
        $this->message = '';
        $transaction = $this->createdTransaction($request);
        if ($transaction->exists()) {
            switch ($transaction->fld_057) {
                case 'MTN':
                    $mtn = new Mtn();
                    $responseMessage =  $mtn->debit($transaction->fld_002, Functions::toFloat($transaction->fld_004), $transaction->fld_116, $transaction->fld_011, $path,  $this->message);
                    $response = $this->response($responseMessage);
                    $this->updatePurchaseTransaction($transaction, $response, $responseMessage, $path);
                    return $response;
                    break;
                case 'TGO':
                    $tigo = new Tigo();
                    return $tigo->debit($number, $amount, $description, $transaction_id);
                    break;
                case 'ATL':
                    $airtel = new Airtel();
                    return $airtel->debit($number, $amount, $description, $transaction_id);
                    break;
                case 'VDF':
                    $vodafone = new Vodafone();
                    return $vodafone->debit($number, $amount, $transaction_id, $voucher_code);
                    break;
                case 'MAS':
                    $zenith = new Zenith();
                    $responseMessage = $zenith->debit($request->pan, $request->exp_month, $request->exp_year, $request->cvv, Functions::toFloat($transaction->fld_004), $request->merchant_id, $transaction->fld_011, $transaction->fld_011, $transaction->fld_011, $request->input('3d_url_response'), $message);
                    $response = $this->response($responseMessage);
                    $this->updatePurchaseTransaction($transaction, $response, $responseMessage, $path);
                    return $response;
                    break;
                case 'VIS':
                    $zenith = new Zenith();
                    $responseMessage = $zenith->debit($request->pan, $request->exp_month, $request->exp_year, $request->cvv, Functions::toFloat($transaction->fld_004), $request->merchant_id, $transaction->fld_011, $transaction->fld_011, $transaction->fld_011, $request->input('3d_url_response'), $message);
                    $response = $this->response($responseMessage);
                    $this->updatePurchaseTransaction($transaction, $response, $responseMessage, $path);
                    return $response;
                    break;
                default:
                    return [
                        'code' => 910,
                        'status' => 'error',
                        'reason' => 'r-switch not supported'
                    ];
            }
        }
    }

    public function credit(Transaction $transaction, $message)
    {
        switch ($transaction->fld_117) {
            case 'MTN':
                $mtn = new Mtn();
                $responseMessage    = $mtn->credit($transaction->fld_103, Functions::toFloat($transaction->fld_004), $transaction->fld_011, $message);
                $response           = $this->response($responseMessage);
                $this->updateTransferTransaction($transaction, $response, $responseMessage);
                return $response;
                break;
            case 'TGO':
                $tigo = new Tigo();
                return $tigo->debit($number, $amount, $description, $transaction_id);
                break;
            case 'ATL':
                $airtel = new Airtel();
                return $airtel->debit($number, $amount, $description, $transaction_id);
                break;
            case 'VDF':
                $vodafone = new Vodafone();
                return $vodafone->debit($number, $amount, $transaction_id);
                break;
            default:
                return [
                    'code' => 910,
                    'status' => 'error',
                    'reason' => 'r-switch not supported'
                ];
                break;
        }

    }

    public static function generateStan()
    {
        $time = explode(' ', microtime());
        return $time[1].substr(str_shuffle(explode('.', $time[0])[1]), -2);
    }

    public static function requestToArray(Request $request){
        return [
            'fld_002' => $request->input('subscriber_number', $request->input('pan', null)), // account number
            'fld_003' => $request->processing_code, // processing_code
            'fld_004' => $request->amount, // transaction amount
            'fld_009' => $request->input('device_type', 'N'), // device type
            'fld_011' => Transaction::generateStan(), // stan
//            'fld_012', // transaction date
            'fld_014' => $request->input('exp_month').'/'.$request->input('exp_year'), // card expiration date
            'fld_037' => $request->transaction_id, // reference
            'fld_038' => null, // approved code
            'fld_039' => null, // response code
            'fld_041' => null, // Terminal ID
            'fld_042' => $request->merchant_id, // Merchant ID
            'fld_043' => null, // merchant name and location
            'fld_057' => $request->r_switch, // R - Switch
            'fld_103' => $request->input('account_number', null), // To-Account Number
            'fld_116' => $request->input('desc', null), // Description
            'fld_117' => $request->input('account_issuer', null), // To-Account Issuer
            'fld_123' => $request->input('cvv', null), // cvv
            'rfu_001' => $request->input('rfu_001', null), // reserved for future use
            'rfu_002' => $request->input('rfu_002', null), // reserved for future use
            'rfu_003' => $request->input('rfu_003', null), // reserved for future use
            'rfu_004' => $request->input('rfu_004', null), // reserved for future use
            'rfu_005' => $request->input('rfu_005', null), // reserved for future use
        ];
    }

    /**
     * @param Request $request
     * @return Transaction $transaction
     */
    public function createdTransaction(Request $request)
    {
        $data                   = $request->all();
        $data['description']    = $request->desc;
        $data['r_switch']       = $request->input('r-switch');

        unset($data['desc']); unset($data['r-switch']);

        if (DB::table('logs_api_messages')->insert($data)){
            return Transaction::create(Transaction::requestToArray($request));
        }
    }

    public function updatePurchaseTransaction(Transaction $transaction, $response, $responseMessage, $path)
    {
        # Processing codes for transfer
        $transfer = ['400000', '400100', '400110', '400120', '400130', '400200', '400210', '400220'];

        if ($response['code'] == 201) { // if transaction is pending approval
            $transaction->fld_039 = '101';
        } elseif ( $response['code'] == '000') { // if transaction has been approved
            if (in_array($transaction->fld_003, $transfer)){
                $transaction->fld_039 = '102';
            } else {
                $transaction->fld_039 = '000';
            }
        } else { // if transaction fails
            $transaction->fld_039 = '100';
        }

        $transaction->fld_038 = implode('', $responseMessage);
        $transaction->save();

        # Check if transaction is a transfer? then queue for crediting if debit has been successful

        Functions::writettlrResponse($response, $path);
    }

    public function updateTransferTransaction(Transaction $transaction, $response, $responseMessage)
    {
        if ( $response['code'] == '000') { // If crediting the recipient was successful
            $transaction->fld_039 = '000';
        } else {
            $transaction->fld_039 = '103';
        }

        $transaction->fld_038 = implode('', $responseMessage);
        $transaction->save();
    }

    public function response(array $responseMessage)
    {
        switch ($responseMessage[0]) {
            case 100:
                return [
                    'status' => 'approved',
                    'code' => '000',
                    'reason' => 'Transaction processed successfully!'
                ];
                break;

            case 201:
                return [
                    'status' => 'success',
                    'code' => 201,
                    'reason' => 'pending approval'
                ];
                break;

            case '0000':
                return [
                    'status' => 'success',
                    'code' => 201,
                    'reason' => 'pending approval'
                ];
                break;

            case 101:
                return [
                    'status' => 'declined',
                    'code' => 101,
                    'reason' => 'Insufficient funds in wallet'
                ];
                break;

            case 102:
                return [
                    'status' => 'declined',
                    'code' => 102,
                    'reason' => 'Number not registered for mobile money!'
                ];
                break;

            case 103:
                return [
                    'status' => 'declined',
                    'code' => 103,
                    'reason' => 'Wrong PIN or transaction timed out!'
                ];
                break;

            case 111:
                return [
                    'status' => 'success',
                    'code' => 111,
                    'reason' => 'Payment request sent successfully'
                ];
                break;

            case 104:
                return [
                    'status' => 'declined',
                    'code' => 104,
                    'reason' => 'Transaction declined or terminated!'
                ];
                break;

            case 105:
                return [
                    'status' => 'declined',
                    'code' => 105,
                    'reason' => 'Invalid amount or general failure. Try changing transaction id!',
                ];
                break;

            case 106:
                return [
                    'status' => 'declined',
                    'code' => 106,
                    'reason' => 'Duplicate transaction ID!'
                ];
                break;

            case 107:
                return [
                    'status' => 'network down',
                    'code' => 107,
                    'reason' => 'Network error please try again later!'
                ];
                break;

            case '-900':
                return [
                    'status' => 'declined',
                    'code' => 901,
                    'reason' => 'System error: The requested name is valid, but no data of the requested type was found'
                ];
                break;

            case 'vbv required':
                return [
                    'status' => 'vbv required',
                    'code' => 200,
                    'reason' => $responseMessage[2]
                ];
                break;

            case '62':
                return [
                    'status' => 'Declined',
                    'code' => 202,
                    'reason' => 'Restricted Card'
                ];
                break;

            case '2':
                return [
                    'status' => 'Declined',
                    'code' => 202,
                    'reason' => 'Bank Declined Transaction'
                ];
                break;

            case '05':
                return [
                    'status' => 'Declined',
                    'code' => 202,
                    'reason' => 'Do not honor'
                ];
                break;

            case '54':
                return [
                    'status' => 'Declined',
                    'code' => 202,
                    'reason' => 'Card expired'
                ];
                break;

            case '010':
                return [
                    'status' => 'Declined',
                    'code' => '010',
                    'reason' => 'Suspicious transaction'
                ];
                break;

            case '020':
                return [
                    'status' => 'Declined',
                    'code' => '020',
                    'reason' => 'Transaction amount above GHS 500 are not allowed.'
                ];
                break;

            case '21VD':
                return [
                    'status' => 'pending',
                    'code' => 110,
                    'reason' => 'bill not paid'
                ];
                break;

            default:
                return [
                    'status' => 'declined',
                    'code' => 109,
                    'reason' => 'Error occurred please try again later!'
                ];
                break;
        }
    }
}