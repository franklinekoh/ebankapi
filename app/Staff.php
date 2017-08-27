<?php
/**
 * Created by PhpStorm.
 * User: FRANK
 * Date: 8/13/2017
 * Time: 4:23 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;


class Staff extends Model {

    protected $table = 'staff';

    public function user(){
        return $this->hasOne('App\User', 'user_id');
    }
}