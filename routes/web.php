<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

//    login route
$app->post('/login', 'userController@authenticate');

//creates customer record in various tables in the database
$app->post('create/user/customer', 'CustomerController@createUser');

//creates staff record
$app->post('create/user/staff', 'StaffController@createUser');

$app->group(['middleware' => 'auth'], function ($app){

//    staff credit account
    $app->post('staff/account/credit', 'StaffController@creditAccount');

//    Admin credit (post request)
    $app->post('admin/account/transact', 'AdminController@accountTransaction');

//   getting all customers
    $app->get('customers', 'CustomerController@getAllcustomers');

//    getting individual customer for each staff
    $app->get('customers/staff', 'StaffController@getCustomer');

//    admin monthly deduction
    $app->post('monthlydeduction', 'AdminController@monthlyDeduction');

//    sign in
    $app->post('signin', 'AdminController@staffSignIn');

//    signout
    $app->post('signout', 'AdminController@staffSignOut');

//    get all transaction history
    $app->get('history', 'CustomerController@getAllTransactionHistory');

//    change password
    $app->post('changepassword', 'AdminController@changePassword');
});
