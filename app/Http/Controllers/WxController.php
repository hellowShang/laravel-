<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }
}
