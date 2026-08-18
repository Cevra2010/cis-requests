<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Models\User;

class SetupController extends Controller
{
    public function index()
    {
        if (User::query()->exists()) {
            return redirect()->route('auth.login');
        }

        return view('setup.index');
    }
}
