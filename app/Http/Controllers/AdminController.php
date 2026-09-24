<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalProducts = Product::count();
        $totalCategories = Category::whereNull('parent_id')->count();
        $activeProducts = Product::where('is_active', true)->count();

        return view('admin.dashboard', compact('totalProducts', 'totalCategories', 'activeProducts'));
    }
}
