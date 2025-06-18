<?php

namespace App\Http\Controllers;

use App\Models\BOM;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BOMController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('bom/index');
    }
}
