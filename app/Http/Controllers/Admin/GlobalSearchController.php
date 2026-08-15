<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request, GlobalSearchService $service)
    {
        return response()->json(
            $service->search($request->get('q'))
        );
    }
}
