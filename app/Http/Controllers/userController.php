<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

use App\User;
use Validator;
use Auth;
use App\Http\Controllers\CustomerController;
use Carbon\Carbon;
use DB;

class userController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $api_key = 'e238088637ba7a39822f39cf852f49c2a8849eb4';
    private $user_name = 'ekohfranklin@gmail.com';
    private $json_url = 'http://api.ebulksms.com:8080/sendsms.json';
    private $sender = 'Pat MicFin';
    private $flash = 0;

    public function __construct()
    {

    }

    //

//validating user
    public  function authenticate(Request $request){

        Validator::make($request->all(),   [
            'user_id' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $request['user_id'])
                        ->orwhere('username', $request['user_id'])->first();
    dd(Hash::check($request['password'], $user->password));

        if($user != null && Hash::check($request['password'], $user->password)){

            $api_key = str_random(60);

            User::where('email', $request['user_id'])
                ->orwhere('username', $request['user_id'])
                ->update(['api_token' => $api_key]);

            return response()->json(['status' => 'success', 'api_key' => $api_key]);

        }else{
            return response()->json(['status' => 'fail'], 401);
        }
    }

    public static function  updateLastTransaction($account_number, $staff_id){

        $user_id = customerController::getAccount($account_number)->user_id;

//updating last transaction for customer
        DB::table('customers')->where('user_id', $user_id)->update(['last_transaction' => Carbon::now()]);

//        updating last transaction for staff
        DB::table('staff')->where('user_id', $staff_id)->update(['last_transaction' => Carbon::now()]);
    }

    public static function updateTransactionHistory($account_number, $amount, $transaction_type){

        $account_id = customerController::getAccount($account_number)->id;
        $account_balance = customerController::getAccount($account_number)->account_balance;

//        creating a new record for history
        DB::table('history')->insert([
           'account_id' => $account_id,
            'balance' => $account_balance,
            'amount' => $amount,
            'transaction_type' => $transaction_type,
            'date' => Carbon::now()
        ]);
    }

//    sending SMS to customers using ebulkSMS api
    public  function sendSms($message, $recipient){
      return  $this->useJSON($this->json_url, $this->user_name, $this->api_key, $this->flash, $this->sender, $message, $recipient);
    }


    public function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients) {
        $gsm = array();
        $country_code = '234';
        $arr_recipient = explode(',', $recipients);
        foreach ($arr_recipient as $recipient) {
            $mobilenumber = trim($recipient);
            if (substr($mobilenumber, 0, 1) == '0') {
                $mobilenumber = $country_code . substr($mobilenumber, 1);
            } elseif (substr($mobilenumber, 0, 1) == '+') {
                $mobilenumber = substr($mobilenumber, 1);
            }
            $generated_id = uniqid('int_', false);
            $generated_id = substr($generated_id, 0, 30);
            $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
        }
        $message = array(
            'sender' => $sendername,
            'messagetext' => $messagetext,
            'flash' => "{$flash}",
        );
        $request = array('SMS' => array(
            'auth' => array(
                'username' => $username,
                'apikey' => $apikey
            ),
            'message' => $message,
            'recipients' => $gsm
        ));
        $json_data = json_encode($request);
        if ($json_data) {
            $response = $this->doPostRequest($url, $json_data, array('Content-Type: application/json'));
            $result = json_decode($response);
            return $result->response->status;
        } else {
            return false;
        }
    }

    //Function to connect to SMS sending server using HTTP POST
    public function doPostRequest($url, $data, $headers = array()) {
        $php_errormsg = '';
        if (is_array($data)) {
            $data = http_build_query($data, '', '&');
        }
        $params = array('http' => array(
            'method' => 'POST',
            'content' => $data)
        );
        if ($headers !== null) {
            $params['http']['header'] = $headers;
        }
        $ctx = stream_context_create($params);
        $fp = fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            return "Error: gateway is inaccessible";
        }
//stream_set_timeout($fp, 0, 250);
        try {
            $response = stream_get_contents($fp);
            if ($response === false) {
                throw new Exception("Problem reading data from $url, $php_errormsg");
            }
            return $response;
        } catch (Exception $e) {
            $response = $e->getMessage();
            return $response;
        }
    }

    public static function createUserGroup($user_id, $group_id){

        DB::table('user_to_group')->insert([
            'user_id' => $user_id,
            'group_id' => $group_id
        ]);
    }

}
