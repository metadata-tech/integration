<?php

namespace App\Http\Controllers;



class LogAPIController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        return view('logs.index');
    }

}
