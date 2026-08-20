<?php

namespace App\Http\Controllers\Parameter;

use App\Http\Controllers\Controller;

class ParameterController extends Controller
{
    public function index()
    {
        return view('parameter.index');
    }
}
