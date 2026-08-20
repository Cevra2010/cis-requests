<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index()
    {
        return view('category.index');
    }
}
