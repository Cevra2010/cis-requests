<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\Price\PriceController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\Project\OfferController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Source\SourceController;
use App\Http\Controllers\User\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
    Route::get('/Dashbaord/Pat',[DashboardController::class,'index'])->name("pattern");

    Route::get('/Dashboard/Me',          [UserController::class, 'self'])               ->name('dashboard.self');
    Route::post('/Dashboard/Me',         [UserController::class, 'selfUpdate'])          ->name('dashboard.self.update');
    Route::post('/Dashboard/Me/Password',[UserController::class, 'selfPasswordUpdate'])  ->name('dashboard.self.password');

    /** Settings */
    Route::get('/Settings',[\App\Http\Controllers\Setting\SettingController::class,'index'])->name("setting");

    /** Module Manager */
    Route::get('/Modules', fn() => view('modules.index'))->name('modules');

    /** User */
    Route::get('/User',                           [UserController::class, 'index'])             ->name('user');
    Route::get('/User/Create',                    [UserController::class, 'create'])            ->name('user.create');
    Route::post('/User/Create',                   [UserController::class, 'store'])             ->name('user.store');
    Route::get('/User/{user}',                    [UserController::class, 'edit'])              ->name('user.edit');
    Route::post('/User/{user}',                   [UserController::class, 'update'])            ->name('user.update');
    Route::get('/User/{user}/Security',           [UserController::class, 'security'])          ->name('user.security');
    Route::post('/User/{user}/Security',          [UserController::class, 'securityUpdate'])    ->name('user.security.update');
    Route::get('/User/{user}/Membership',         [UserController::class, 'membership'])        ->name('user.edit.membership');
    Route::post('/User/{user}/Membership',        [UserController::class, 'membershipUpdate'])  ->name('user.membership.update');
    Route::get('/User/{user}/Permissions',        [UserController::class, 'permissions'])       ->name('user.permissions');
    Route::post('/User/{user}/Permissions',       [UserController::class, 'permissionsUpdate']) ->name('user.permissions.update');
    Route::get('/User/{user}/Delete',             [UserController::class, 'delete'])            ->name('user.delete');
    Route::post('/User/{user}/Delete',            [UserController::class, 'destroy'])           ->name('user.destroy');

    /** Roles */
    Route::get('/Role',                           [RoleController::class, 'index'])             ->name('role.index');
    Route::get('/Role/Create',                    [RoleController::class, 'create'])            ->name('role.create');
    Route::post('/Role/Create',                   [RoleController::class, 'store'])             ->name('role.store');
    Route::get('/Role/{role}',                    [RoleController::class, 'edit'])              ->name('role.edit');
    Route::post('/Role/{role}',                   [RoleController::class, 'update'])            ->name('role.update');
    Route::get('/Role/{role}/Permissions',        [RoleController::class, 'permissions'])       ->name('role.permissions');
    Route::post('/Role/{role}/Permissions',       [RoleController::class, 'permissionsUpdate']) ->name('role.permissions.update');
    Route::get('/Role/{role}/Delete',             [RoleController::class, 'delete'])            ->name('role.delete');
    Route::post('/Role/{role}/Delete',            [RoleController::class, 'destroy'])           ->name('role.destroy');

    /** Groups */
    Route::get('/Group',                          [GroupController::class, 'index'])             ->name('group.index');
    Route::get('/Group/Create',                   [GroupController::class, 'create'])            ->name('group.create');
    Route::post('/Group/Create',                  [GroupController::class, 'store'])             ->name('group.store');
    Route::get('/Group/{group}',                  [GroupController::class, 'edit'])              ->name('group.edit');
    Route::post('/Group/{group}',                 [GroupController::class, 'update'])            ->name('group.update');
    Route::get('/Group/{group}/Permissions',      [GroupController::class, 'permissions'])       ->name('group.permissions');
    Route::post('/Group/{group}/Permissions',     [GroupController::class, 'permissionsUpdate']) ->name('group.permissions.update');
    Route::get('/Group/{group}/Delete',           [GroupController::class, 'delete'])            ->name('group.delete');
    Route::post('/Group/{group}/Delete',          [GroupController::class, 'destroy'])           ->name('group.destroy');

    /** Products */
    Route::get('/Products',                        [ProductController::class, 'index'])       ->name('product');
    Route::get('/Products/Create/{parentProduct?}',[ProductController::class, 'create'])      ->name('product.create');
    Route::post('/Products/Create',                [ProductController::class, 'store'])        ->name('product.store');
    Route::get('/Products/Edit/{product}',         [ProductController::class, 'edit'])         ->name('product.edit');
    Route::post('/Products/Edit/{product}',        [ProductController::class, 'update'])       ->name('product.edit.update');
    Route::post('/Products/Edit/{product}/Price',  [ProductController::class, 'storePrice'])   ->name('product.edit.price.store');
    Route::get('/Products/Delete/{product}',       [ProductController::class, 'delete'])       ->name('product.edit.delete');
    Route::post('/Products/Delete/{product}',      [ProductController::class, 'deleteStore'])  ->name('product.edit.delete.store');
    Route::get('/Products/Price',                  [ProductController::class, 'price'])        ->name('product.price');

    /** Productsource */
    Route::get('/Source',                          [SourceController::class, 'index'])   ->name('source');
    Route::get('/Source/Create',                   [SourceController::class, 'create'])  ->name('source.create');
    Route::post('/Source/Create',                  [SourceController::class, 'store'])   ->name('source.store');
    Route::get('/Source/Edit/{source}',            [SourceController::class, 'edit'])    ->name('source.edit');
    Route::post('/Source/Edit/{source}',           [SourceController::class, 'update'])  ->name('source.update');
    Route::get('/Source/Delete/{source}',          [SourceController::class, 'delete'])  ->name('source.delete');
    Route::delete('/Source/Delete/{source}',       [SourceController::class, 'destroy']) ->name('source.destroy');

    /** Price */
    Route::get('/Price',[PriceController::class,'index'])->name("price")->middleware("area:price");

    /** Projects */
    Route::get('/Projects',                          [ProjectController::class, 'index'])     ->name('project');
    Route::get('/Projects/Trash',                    [ProjectController::class, 'trash'])     ->name('project.trash');
    Route::post('/Projects/Trash/{project}/Restore', [ProjectController::class, 'restore'])   ->name('project.restore');
    Route::get('/Project/Create',                    [ProjectController::class, 'create'])    ->name('project.create');
    Route::post('/Project/Create',                   [ProjectController::class, 'store'])     ->name('project.store');
    Route::get('/Project/{project}/View',            [ProjectController::class, 'show'])      ->name('project.show');
    Route::get('/Project/{project}',                 [ProjectController::class, 'edit'])      ->name('project.edit');
    Route::post('/Project/{project}',                [ProjectController::class, 'update'])    ->name('project.update');
    Route::post('/Project/{project}/Duplicate',      [ProjectController::class, 'duplicate']) ->name('project.duplicate');
    Route::delete('/Project/{project}',              [ProjectController::class, 'destroy'])   ->name('project.destroy');
    Route::get('/Project/{project}/Export/PDF',      [ProjectController::class, 'exportPdf']) ->name('project.export.pdf');
    Route::post('/Project/{project}/Status/Advance', [ProjectController::class, 'advanceStatus']) ->name('project.status.advance');
    Route::post('/Project/{project}/Status/Revert',  [ProjectController::class, 'revertStatus'])  ->name('project.status.revert');

    /** Angebote & Bestelllisten */
    Route::get('/Project/{project}/Offers/{offer}/OrderList/PDF',[OfferController::class, 'exportOrderListPdf'])->name('offer.orderlist.pdf');

    /** Categories */
    Route::get('/Category',              [CategoryController::class, 'index'])   ->name('category.index');
    Route::get('/Category/Create',       [CategoryController::class, 'create'])  ->name('category.create');
    Route::post('/Category/Create',      [CategoryController::class, 'store'])   ->name('category.store');
    Route::get('/Category/{id}/Edit',    [CategoryController::class, 'edit'])    ->name('category.edit');
    Route::put('/Category/{id}/Edit',    [CategoryController::class, 'update'])  ->name('category.update');
    Route::get('/Category/{id}/Delete',  [CategoryController::class, 'delete'])  ->name('category.delete');
    Route::delete('/Category/{id}',      [CategoryController::class, 'destroy']) ->name('category.destroy');
});
