<?php

namespace App\Http\Controllers;

use App\Service\TokenService;

class LogAPIController extends Controller
{
    private TokenService $tokenService;

    public function __construct()
    {

    }

    public function index()
    {
        return view('logs.index');
    }

}
