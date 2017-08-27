<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use SebastianBergmann\CodeCoverage\Report\Crap4j;
use Validator;
use Auth;
use App\Http\Controllers\userController;
use App\User;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
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

    //

    public function accountTransaction(Request $request){

//    getting the instance of a customer
        $customer = CustomerController::getCustomer($request['account_number']);
if ($customer == null){
    return response()->json('Page not found', 404);
}



        if(self::isAdmin(Auth::user()->id)){

            //        validation
            $rules = [
                'transaction_type' => 'required',
                'amount' => 'required',
                'account_number' => 'required'
            ];

            $userController = new userController();
            //        getting the instance of a customer
//SMS parameters
            $amount = $request['amount'];
            $sender = 'Pat MicroFinance';
            $card_no = $customer->card_number;
            $time = Carbon::now();
            $account_balance = '';
            $acc_no = $request['account_number'];
            $replace = 'xxxx';
            $msg_acc_no = substr_replace($acc_no, $replace, 3, 4 );

//            $message = [
//                "credit" => "Your account $msg_acc_no has being credited with NGN$amount on card no: $card_no, on $time by Administration balance: $account_balance. Thank you for banking with us, $sender",
//                "debit" => "Your account $msg_acc_no has being debited with NGN$amount on card no: $card_no, on $time by Administration balance: $account_balance. Thank you for banking with us, $sender"];
            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()){
                return $validator->errors()->all();
            }

//            crediting or debiting account depending on transaction type
switch ($request['transaction_type']){
                case 1;
//                crediting account
                    customerController::creditAccount($request['account_number'], $request['amount']);
//                    SMS parameter
                    $customer = customerController::getCustomer($request['account_number']);
                    $account_balance = $customer->account_balance;
                    $msg_credit = "Your account $msg_acc_no has being credited with NGN$amount on card no: $card_no, on $time by Administration balance: $account_balance. Thank you for banking with us, $sender";
//                    sending sms
                    $userController->sendSms($msg_credit, $customer->phone);
//                    updating transaction history
                    $userController::updateTransactionHistory($request['account_number'], $request['amount'], 1);
        //        updating last transaction
        userController::updateLastTransaction($request['account_number'], Auth::user()->id);
                   return response()->json('account credited successfully');
                    break;
                case 2;
                if($request['amount'] <= $customer->account_balance){
//                    debiting account
                    customerController::debitAccount($request['account_number'], $request['amount']);
//                    SMS parameter
                    $account_balance = $customer->account_balance;
                    $msg_debit = "Your account $msg_acc_no has being debited with NGN$amount on card no: $card_no, on $time by Administration balance: $account_balance. Thank you for banking with us, $sender";
//                    Sending SMS
                    $userController->sendSms($msg_debit, $customer->phone);
//                    updating transaction history
                    $userController::updateTransactionHistory($request['account_number'], $request['amount'], 2);
                    //        updating last transaction
                    userController::updateLastTransaction($request['account_number'], Auth::user()->id);
                    return response()->json('account debited successfully');
                }else{
                    return response()->json('insufficient fund');
                }
                    break;

}


        }else{
            return response()->json('Administrator only', 401);
        }


    }


    public static function isAdmin($id){

        $staff = DB::table('user_to_group')
                        ->where('user_id', $id)
                            ->first();

        if($staff->group_id == 3){
            return true;
        }

        return false;
    }

    public static function monthlyDeduction(Request $request){

//        validating
      $validator =  Validator::make($request->all(), [
            'admin_password' => 'required'
        ]);

        if($validator->fails()){
            return $validator->errors()->all();
        }

//        checking if the password matches admin password
        $user = User::where('id', Auth::user()->id)->first();

        if (Hash::check($request['admin_password'], $user->password)){
                    $all_balance = CustomerController::getAllBalance();

                    foreach ($all_balance as $balance){

//                        amount that should be deducted
                        $deduct = 200;
                        if($balance->account_balance != null || $balance->account_balance > 200){
                            $new_balance = $balance->account_balance - $deduct;
//                            customer info

                            $customers = CustomerController::getCustomers($balance->account_number);
//            looping through customers
                            foreach ($customers as $customer){
//                                SMS parameters
                            $customer_name = $customer->firstname.' '.$customer->lastname;

                            $msg_txt = "Dear $customer_name, $deduct NGN monthly service charge was deducted from your account, your balance as at the end of the month is $new_balance, thank you for banking with us, Pat Microfinance";
                                //sending SMS
                            $userController = new userController();
                            $userController->sendSms($msg_txt, $customer->phone);
                            }
//debiting account
                CustomerController::debitAccount($balance->account_number, $deduct);
                            //                    updating transaction history
                            $userController::updateTransactionHistory($balance->account_number, $deduct, 2);
//        updating last transaction
                            userController::updateLastTransaction($balance->account_number, Auth::user()->id);

                            return response()->json('transaction completed successfully');
                        }
                    }
        }else{
            return response()->json('unauthorized, administrator only', 401);
        }


    }


//    signs staff in
        public function staffSignIn(Request $request){

//        validation
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required|exists:staff,staff_id'
            ]);

            if($validator->fails()){
                return $validator->errors()->all();
            }

//            updating clocking status and time
            DB::table('staff')
                ->where('staff_id', $request['staff_id'])
                    ->update([
                        'clocking_status' => 1,
                        'clock_in_time' => Carbon::now()
                    ]);

            //            creating clocking history
            $this->updateClockingHistory($request['staff_id'], 'signIn');

            return response()->json('signed in successfully');
        }

//        signs staff out

        public function staffSignOut(Request $request){

            //        validation
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required|exists:staff,staff_id'
            ]);

            if($validator->fails()){
                return $validator->errors()->all();
            }

//            updating clocking status and time
            DB::table('staff')
                ->where('staff_id', $request['staff_id'])
                ->update([
                    'clocking_status' => 0,
                ]);
//            creating clocking history
            $this->updateClockingHistory($request['staff_id'], 'signOut');

            return response()->json('signed out successfully');
        }

//        updates clocking card record
        public function updateClockingHistory($staff_id, $type){

            switch ($type){
                case 'signIn';
          DB::table('clocking_record')
                ->insert(['staff_id' => $staff_id,
                'sign_in_time' => Carbon::now()]);
                break;
                case 'signOut';

//                getting current sign in time
                 $clock_in_time =   DB::table('staff')
                        ->where('staff_id', $staff_id)
                            ->first()->clock_in_time;
//                 updating the clocking record
//                 dd($clock_in_time);
                 DB::table('clocking_record')
                     ->where('sign_in_time', $clock_in_time)
                        ->update(['sign_out_time' => Carbon::now()]);
                break;
            }

        }

        public function changePassword(Request $request){

            $validator = Validator::make($request->all(), [
                'password' => 'required|min:6',
                'staff_id' => 'required|exists:staff,staff_id'
            ]);

            if ($validator->fails())
                $validator->errors->all();

            $request['password'] = app('hash')->make($request['password']);

            $user_id = DB::table('staff')
                            ->where('staff_id', $request['staff_id'])
                                ->first()->user_id;
//            updating the user table

            DB::table('users')->where('id', $user_id)
                ->update([
                   'password' => $request['password']
                ]);

            return response()->json('password updated successfully');
        }
}
