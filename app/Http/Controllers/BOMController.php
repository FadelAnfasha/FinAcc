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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BOM $bOM)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BOM $bOM)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BOM $bOM)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BOM $bOM)
    {
        //
    }
}
