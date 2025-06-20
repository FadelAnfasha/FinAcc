<?php

namespace App\Http\Controllers;

use App\Models\ProcessCost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render("pc/index");
    }
}
