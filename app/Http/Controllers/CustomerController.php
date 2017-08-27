<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Validator;
use Auth;

class CustomerController extends Controller
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

    //creating a record in user table
    public function createUser(Request $request){

//        validating input
        $rules = [
            'phone' => 'required|min:11|numeric',
            'next_of_kin_phone' => 'required|min:11|numeric',
            'staff_id' => 'required|min:4|max:4|exists:staff,staff_id',
            'passport' => 'required|max:2048|mimes:jpeg,jpg,png'
        ];

        $messages = [
            'phone.required' => 'the phone number cannot be less than eleven digits',
            'next_of_kin_phone.required' => 'the phone number cannot be less than eleven digits',
            'staff_id.exists' => 'there is no staff with staff id '. $request['staff_id'] .' please try again',
            'passport.max' => 'the passport  size must not be greater than 2mb',
            'passport.mimes' => 'the file type must be jpg or png file type only'
        ];

        $validator = Validator::make($request->all(),$rules , $messages);

        if ($validator->fails()){
            return $validator->errors()->all();
        }
        $request['api_token'] = str_random(60);

//generating account number for user
//        each account number begins with the current year
        $acc_no = date('y').rand(10, 99999999);

        DB::table('users')->insert([
           'firstname' => $request['firstname'],
            'middlename' => $request['middlename'],
            'lastname' => $request['lastname'],
            'api_token' => $request['api_token'],
            'email' => $request['email'],
            'gender' => $request['gender'],
            'created_at' => Carbon::now()
        ]);

//        getting the user
       $user = DB::table('users')->where('api_token', $request['api_token'])->first();

//passing the corresponding parameters as array to create a record in customer table
        $this->createCustomer([
            'user_id' => $user->id,
            'phone' => $request['phone'],
            'residential_address' => $request['residential_address'],
            'business_address' => $request['business_address'],
            'card_number' => $request['card_number'],
            'last_transaction' => Carbon::now(),
            'next_of_kin' => $request['next_of_kin'],
            'next_of_kin_phone' => $request['next_of_kin_phone'],
            'relationship_to_next_of_kin' => $request['relationship_to_next_of_kin'],
            'staff_id' => $request['staff_id'],
            'state_id' => $request['state_id'],
            'local_govt_id' => $request['local_govt_id']
        ]);
//dd($acc_no);
        //passing the corresponding parameters as array to create a record in accounts table
        $this->createAccount([
            'user_id' => $user->id,
            'account_name' => $request['account_name'],
            'account_number' => $acc_no,
        ]);
//        passing the corresponding parameters to upload passport
        self::uploadPassport($request['passport']  , $user->id);

//        passing  the corresponding parameter to createUserGroupFunction from userController
        userController::createUserGroup($user->id, 1);
     return response()->json('Customer registered successfully');
    }



//creating a record in customer table
    public function  createCustomer($data = array()){
//dd($data['']);
        DB::table('customers')->insert([
            'user_id' => $data['user_id'],
            'phone' => $data['phone'],
            'residential_address' => $data['residential_address'],
            'business_address' => $data['business_address'],
            'last_transaction' => $data['last_transaction'],
            'card_number' => $data['card_number'],
            'sms_status' => 0,
            'next_of_kin' => $data['next_of_kin'],
            'next_of_kin_phone' => $data['next_of_kin_phone'],
            'relationship_to_next_of_kin' => $data['relationship_to_next_of_kin'],
            'staff_id' => str_random(4),
            'state_id' => $data['state_id'],
            'local_govt_id' => $data['local_govt_id']
        ]);

    }


//    creating a record in accounts tables
public function createAccount($data = array()){

    DB::table('accounts')->insert([
        'user_id' => $data['user_id'],
        'account_name' => $data['account_name'],
        'account_number' => $data['account_number'],
        'account_balance' => 0
    ]);
}

//uploads customer passport to ./public/uploads and sends the name to database

public static function uploadPassport($data, $user_id){
//    dd($data);
    if($data->isValid()){

        $destination = 'uploads';

        $extension = $data->getClientOriginalExtension(); //getting file extension
        $file_name = str_random(16).'.'.$extension;

        $data->move($destination, $file_name); //uploading to a given path

//        creating a record in the uploads table
        DB::table('uploads')->insert([
            'file_name' => $file_name,
            'user_id' => $user_id,
            'created_at' => Carbon::now()
        ]);

    }
}

//gets current account balance
public static function getCurrentBalance($account_number){

   $query = DB::table('accounts')->where('account_number', $account_number)->first();
   if($query != null){
       return $query;
   }

   return response()->json('The account number seems to be incorrect, please try again!');
}

//credit user
public static function creditAccount($account_number, $amount){

//    adding to the current balance
    DB::table('accounts')->where('account_number', $account_number)->increment('account_balance', $amount);


}

//debit user
public  static  function debitAccount($account_number, $amount){

//    subtracting from current balance
    DB::table('accounts')->where('account_number', $account_number)->decrement('account_balance', $amount);
}
//gets customer id with account number
public static function getAccount($account_number){

  return  DB::table('accounts')->where('account_number', $account_number)->first();
}

//gets an instance of a customer
public static function getCustomer($account_number)
{

    return DB::table('accounts')
        ->where('accounts.account_number', $account_number)
                ->join('customers', 'accounts.user_id', '=', 'customers.user_id')
                                ->first();


}

//returns all customers
public static function getAllCustomers(){

    $customers = DB::table('users')
        ->join('customers', 'users.id', '=', 'customers.user_id')
            ->join('accounts', 'accounts.user_id', '=', 'users.id')
                ->get();

    return $customers;
}

//get all account balance
public static function getAllBalance(){
    return DB::table('accounts')
                ->get();
}

//get all instances of customers with account parameter
public static function getCustomers($account_number){

    return DB::table('accounts')
        ->where('accounts.account_number', $account_number)
        ->join('customers', 'accounts.user_id', '=', 'customers.user_id')
        ->join('users', 'users.id', '=', 'accounts.user_id')
        ->get();
}

public static function getAllTransactionHistory(){
    return DB::table('history')
                ->join('accounts', 'history.account_id', '=', 'accounts.id')
                    ->join('users', 'accounts.user_id', '=', 'users.id')
                        ->get();
}
}
