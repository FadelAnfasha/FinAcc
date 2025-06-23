<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use App\Models\ProcessCost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessCostController extends Controller
{
    public function master()
    {
        $bPartner = BusinessPartner::all();

        return Inertia::render("pc/master", [
            'businessPartners' => $bPartner,
            // opsional, jika kamu ingin akses session manual
            'flash' => session()->only(['success', 'addedItems', 'updatedItems', 'invalidItems']),
        ]);
    }

    public function report()
    {
        return Inertia::render("pc/report");
    }
}
