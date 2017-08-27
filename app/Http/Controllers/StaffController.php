<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Validator;
use App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Http\Controllers\userController;
use Auth;

class StaffController extends Controller
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

    public function createUser(Request $request){
//    validating input
//        dd($request['username']);
        $rules = [
            'phone' => 'required|min:11|numeric',
            'username' => 'required|unique:users,username',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
            'passport' => 'required|max:2048|mimes:jpeg,jpg,png'
        ];

        $message = [
            'username.unique' => 'username already exists in the database. you should try something else',
            'confirm_password.same' => 'passwords do not match, please try again',
            'passport.max' => 'the passport  size must not be greater than 2mb',
            'passport.mimes' => 'the file type must be jpg or png file type only'
        ];

       $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()){
            return $validator->errors()->all();
        }

        $request['api_token'] = str_random(60);
//        hashing the password
        $request['password'] = app('hash')->make($request['password']);
//        creating record in user table
        DB::table('users')->insert([
            'firstname' => $request['firstname'],
            'middlename' => $request['middlename'],
            'lastname' => $request['lastname'],
            'email' => $request['email'],
            'username' => $request['username'],
            'gender' => $request['gender'],
            'api_token' => $request['api_token'],
            'password' => $request['password'],
            'created_at' => Carbon::now()
        ]);

//        getting the user
        $user = DB::table('users')->where('api_token', $request['api_token'])->first();
//passing parameter to createstaff as array
        $this->createStaff([
            'user_id' => $user->id,
            'qualification' => $request['qualification'],
            'residential_address' => $request['residential_address'],
            'phone' => $request['phone'],
            'next_of_kin' => $request['next_of_kin'],
            'next_of_kin_phone' => $request['next_of_kin_phone'],
            'next_of_kin_gender' => $request['next_of_kin_gender'],
            'next_of_kin_age' => $request['next_of_kin_age'],
            'relationship' => $request['relationship'],
            'state' => $request['state'],
            'local_govt' => $request['local_govt']
        ]);
//passing parameters to uploads function from CustomerController
        CustomerController::uploadPassport($request['passport'], $user->id);

//        passing parameters to createUserGroup function from usercontroller
        userController::createUserGroup($user->id, 2);
        return response()->json('staff registered successfully');
    }

//    creating a new record for staff
    public function createStaff($data = array()){

        $staff_id = str_random(4);
  $staff_id =  $this->checkStaffId($staff_id);

        DB::table('staff')->insert([
            'user_id' => $data['user_id'],
            'qualification' => $data['qualification'],
            'residential_address' => $data['residential_address'],
            'phone' => $data['phone'],
            'staff_id' => $staff_id,
            'next_of_kin' => $data['next_of_kin'],
            'next_of_kin_phone' => $data['next_of_kin_phone'],
            'next_of_kin_gender' => $data['next_of_kin_gender'],
            'next_of_kin_age' => $data['next_of_kin_age'],
            'relationship_to_next_of_kin' => $data['relationship'],
            'state_id' => $data['state'],
            'local_govt_id' => $data['local_govt'],
            'created_at' => Carbon::now()
        ]);
    }

//    check if staff exist
    private function checkStaffId($staff_id){

        //checking if staff id exits
     $staff =  DB::table('staff')->where('staff_id', $staff_id)->first();

        if ($staff == '' || $staff == null || !isset($staff)){
            return $staff_id;
        }else{
            $staff_id = str_random(4);
            return $staff_id;

        }

    }

//    crediting customer account
    public function creditAccount(Request $request){

        $rules = [
          'account_number' => 'required|exists:accounts,account_number',
            'amount' => 'required'
        ];

        $message = [
          'account_number.exists' => 'Incorrect account number, please try again!'
        ];

       $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()){
             return $validator->errors()->all();
        }

    //crediting the account
        customerController::creditAccount($request['account_number'], $request['amount']);

//        updating last transaction
        userController::updateLastTransaction($request['account_number'], Auth::user()->id);

//        updating transaction history
        userController::updateTransactionHistory($request['account_number'], $request['amount'], 1);

//       sending sms to customer
        $userController = new userController();

//        getting the instance of a customer
     $customer = customerController::getCustomer($request['account_number']);

//     message parameters
     $staff = Auth::user()->firstname.' '.Auth::user()->lastname;
     $amount = $request['amount'];
     $sender = 'Pat MicroFinance';
     $card_no = $customer->card_number;
     $time = Carbon::now();
     $account_balance = $customer->account_balance;
     $acc_no = $request['account_number'];
     $replace = 'xxxx';
     $msg_acc_no = substr_replace($acc_no, $replace, 3, 4 );

        $message = "Your account $msg_acc_no has being credited with NGN$amount on card no: $card_no, on $time by $staff balance: $account_balance. Thank you for banking with us, $sender";
     if ($customer->sms_status == 1) {
         $userController->sendSms($message,$customer->phone);
     }

    return response()->json('account credited successfully');
    }

//    returns all customer or a specific staff
    public static function getCustomer(){

      $staff_customers = DB::table('users')
                ->where('users.id', Auth::user()->id)
                    ->join('staff', 'users.id', '=', 'staff.user_id')
                        ->join('customers', 'customers.staff_id', '=', 'staff.staff_id')
                                ->get();

    }

}
