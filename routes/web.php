<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Logic\CisAccess\Facades\Access;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/',function() {
    return redirect()->route("dashboard");
})->name("home");

Route::get('user_login',function() {
    return redirect()->route("auth.login");
})->name("login");

Route::get('/Debug/Login',function() {
    $user = User::where('email','admin@istrator.de')->first();
    Auth::login($user);
    return redirect()->route("dashboard");
});

Route::get('/Auth/Login',[AuthController::class,'index'])->name("auth.login");
Route::post('/Auth/Login',[AuthController::class,'submit'])->name("auth.login.submit");
Route::post('/Auth/Logout',[AuthController::class,'logout'])->name("auth.logout");

Route::middleware(['auth'])->group(function() {
    Route::get('/Dashbaord',[DashboardController::class,'index'])->name("dashboard");
    Route::get('/Dashbaord/P',[DashboardController::class,'index'])->name("project");
    Route::get('/Dashbaord/Set',[DashboardController::class,'index'])->name("setting");
    Route::get('/Dashbaord/Pat',[DashboardController::class,'index'])->name("pattern");

    /** User */
    Route::get('/User',[UserController::class,'index'])->name("user")->middleware("area:user");
    Route::get('/User/Create',[UserController::class,'create'])->name("user.create")->middleware("area:user.create");
    Route::post('/User/Create',[UserController::class,'store'])->name("user.store")->middleware("area:user.create");
    Route::get('/User/Delete/{user}',[UserController::class,'delete'])->name("user.edit.delete")->middleware("area:user.edit.delete");
    Route::post('/User/Delete/{user}',[UserController::class,'deleteStore'])->name("user.edit.delete.store")->middleware("area:user.edit.delete");
    Route::get('/User/Edit/{user}',[UserController::class,'edit'])->name("user.edit")->middleware("area:user.edit.base");
    Route::post('/User/Edit/{user}',[UserController::class,'update'])->name("user.edit.update")->middleware("area:user.edit.base");
    Route::get('/User/Edit/{user}/Security',[UserController::class,'security'])->name("user.edit.security")->middleware("area:user.edit.base");
    Route::post('/User/Edit/{user}/Security',[UserController::class,'securityUpdate'])->name("user.edit.security.update")->middleware("area:user.edit.base");
    Route::get('/User/Edit/{user}/Roles',[UserController::class,'roles'])->name("user.edit.roles")->middleware("area:user.edit.roles");

    /** Roles */
    Route::get('/Role',[RoleController::class,'index'])->name("user.role.index")->middleware("area:role");
    Route::get('/Role/Edit/{role}',[RoleController::class,'edit'])->name("user.role.edit")->middleware("area:role.edit");
    Route::post('/Role/Edit/{role}',[RoleController::class,'update'])->name("user.role.edit.update")->middleware("area:role.edit");
    Route::get('/Role/Create',[RoleController::class,'create'])->name("user.role.create")->middleware("area:role.create");
    Route::post('/Role/Create',[RoleController::class,'store'])->name("user.role.store")->middleware("area:role.create");
    Route::get('/Role/Delete/{role}',[RoleController::class,'delete'])->name("user.role.edit.delete")->middleware("area:role.edit.delete");
    Route::post('/Role/Delete/{role}',[RoleController::class,'deleteStore'])->name("user.role.edit.delete.store")->middleware("area:role.edit.delete");

    /** Products */
    Route::get('/Products',[ProductController::class,'index'])->name("product")->middleware("area:product");
    Route::get('/Products/Create/{parentProduct?}',[ProductController::class,'create'])->name("product.create")->middleware("area:product.create");
    Route::post('/Products/Create',[ProductController::class,'store'])->name("product.store")->middleware("area:product.create");
    Route::post('/Products/Edit/{product}/Price',[ProductController::class,'storePrice'])->name("product.edit.price.store")->middleware("area:product.edit.price");
    Route::get('/Products/Delete/{product}',[ProductController::class,'delete'])->name("product.edit.delete")->middleware("area:product.edit.delete");
    Route::post('/Products/Delete/{product}',[UserConProductControllertroller::class,'deleteStore'])->name("product.edit.delete.store")->middleware("area:product.edit.delete");
    Route::get('/Products/Edit/{product}',[ProductController::class,'edit'])->name("product.edit")->middleware("area:product.edit");
    Route::post('/Products/Edit/{product}',[ProductController::class,'update'])->name("product.edit.update")->middleware("area:product.edit");
    Route::get('/Products/Price',[ProductController::class,'price'])->name("product.price")->middleware("area:product.edit.price");

    /** Productsource */
    Route::get('/Products/Source',[ProductController::class,'source'])->name("product.source.index")->middleware("area:product.source");
});
