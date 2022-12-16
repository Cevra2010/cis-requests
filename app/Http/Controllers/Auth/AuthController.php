<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index() {
        if(config("app.debug") && config("app.autologin")) {
            $user = User::where('email','admin@istrator.de')->first();
            Auth::login($user);
            return redirect()->route("home");
        }
        return view("auth.index");
    }

    public function submit(LoginRequest $request) {
        if(Auth::attempt(['email' => $request->get('email'),'password' => $request->get('password')])) {
            return redirect()->route("home");
        }
        else {
            return redirect()->back()->withInput()->withErrors(['Username or password are incorrect']);
        }
    }

    public function logout() {
        Auth::logout();
        return redirect()->route("auth.login");
    }
}
