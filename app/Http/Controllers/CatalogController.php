<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Display landing page with catalog.
     */
    public function index(Request $request)
    {
        $categories = Category::all();
        
        $query = Inventory::where('status', 'available');

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $inventories = $query->with('category')->get();

        return view('home', compact('categories', 'inventories'));
    }

    /**
     * Display details of a specific inventory item.
     */
    public function show($slug)
    {
        $inventory = Inventory::where('slug', $slug)
            ->where('status', 'available')
            ->with(['category', 'variants'])
            ->firstOrFail();

        return view('catalog.detail', compact('inventory'));
    }
}
