<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $provinces = Store::active()
            ->select('province')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        $stores = collect();

        if ($request->filled('province') || $request->filled('city')) {
            $query = Store::active();

            if ($request->filled('province')) {
                $query->byProvince($request->input('province'));
            }

            if ($request->filled('city')) {
                $query->where('city', 'LIKE', '%'.$request->input('city').'%');
            }

            $stores = $query->orderBy('sign_name')->get();
        }

        return view('stores.index', compact('provinces', 'stores'));
    }

    public function search(Request $request): JsonResponse
    {
        $query = Store::active();

        if ($request->filled('province')) {
            $query->byProvince($request->input('province'));
        }

        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%'.$request->input('city').'%');
        }

        return response()->json($query->orderBy('name')->get());
    }
}
