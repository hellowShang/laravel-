<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }

    public function wx(){
        var_dump($_POST);
    }
}
