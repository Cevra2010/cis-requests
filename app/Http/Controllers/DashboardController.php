<?php

namespace App\Http\Controllers;

use App\Http\Logic\CisAccess\Facades\Access;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        return view("dashboard.index");
    }
}
