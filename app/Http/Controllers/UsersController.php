<?php

namespace App\Http\Controllers;



class UsersController extends Controller
{


    public function __construct()
    {

    }

//    public function index()
//    {
//        return view('users.index');
//    }

    public function getUserManagement()
    {
        return view('users.index');
    }

}
