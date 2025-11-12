<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\HistoryRFS;
use App\Models\RequestForService;
use App\Http\Requests\RequestForServiceValidation;

class RequestForServiceController extends Controller
{
    public function store(RequestForServiceValidation $request)
    {
        $validated = $request->validated();
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachment', 'public');
        }

        $user = Auth::user();

        if ($user) {
            if ($user->hasRole(['Director', 'Deputy Division', 'Deputy Department'])) {
                $validated['status'] = '3';
            }
        }

        RequestForService::create([
            'name' => $validated['name'],
            'npk' => $validated['npk'],
            'priority_id' => $validated['priority'],
            'description' => $validated['description'],
            'status_id' => $validated['status'],
            'attachment' => $validated['attachment'],
        ]);

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => $validated['name'],
        ]);


        return redirect()->route('rfs.index')->with('success', 'Request submitted successfully.');
    }

    public function accept($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '2';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request accepted.');
    }

    public function reject($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '3';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('error', 'Request rejected.');
    }

    public function execute($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '4';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request executed.');
    }

    public function user_accept($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '5';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request waiting for acceptance.');
    }

    public function revision($id, Request $request)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '6';
        $rfs->revision = $request->revision;
        $rfs->save();
    }

    public function finish($id, Request $request)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '7';
        $rfs->impact = $request->impact;
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request finish.');
    }
}
