<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use App\Models\CycleTime;
use App\Models\ProcessCost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessCostController extends Controller
{
    public function master()
    {
        $bPartner = BusinessPartner::all();
        $cTimes = CycleTime::all();
        return Inertia::render("pc/master", [
            'businessPartners' => $bPartner,
            'cycleTimes' => $cTimes,
        ]);
    }

    public function report()
    {
        return Inertia::render("pc/report");
    }
}
