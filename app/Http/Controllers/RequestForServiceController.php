<?php

namespace App\Http\Controllers;

use App\Models\HistoryRFS;
use App\Models\Priority;
use App\Models\RequestForService;
use Illuminate\Http\Request;
use App\Models\Status;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;

class RequestForServiceController extends Controller
{

    public function index()
    {
        $services = RequestForService::with('priority', 'status')->get();
        return Inertia::render('rfs/index', [
            'services' => $services,
            'auth' => [
                'user' => Auth::check() ? [
                    'name' => Auth::user()->name,
                    'npk' => Auth::user()->npk,
                    'roles' => Auth::user()->getRoleNames()->toArray(), // Pastikan ini diubah ke array
                    'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(), // Juga pastikan ini dikirim
                ] : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npk' => 'required|string|max:50',
            'priority' => 'required|integer',
            'description' => 'required|string',
            'status' => 'required',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls|max:10240',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachment', 'public');
        }

        // dd($validated);

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
        // $rfs = RequestForService::findOrFail($id);
        // $rfs->status_id = '3';
        // $rfs->save();

        // HistoryRFS::create([
        //     'rfs_id' => RequestForService::latest()->first()->id,
        //     'updated_by' => auth()->user()->name,
        // ]);

        return redirect()->back()->with('success', 'Request accepted.');
    }

    public function reject($id)
    {
        // $rfs = RequestForService::findOrFail($id);
        // $rfs->status_id = '6';
        // $rfs->save();

        // HistoryRFS::create([
        //     'rfs_id' => RequestForService::latest()->first()->id,
        //     'updated_by' => auth()->user()->name,
        // ]);

        return redirect()->back()->with('error', 'Request rejected.');
    }

    public function execute($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '2';
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
        $rfs->status_id = '4';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request waiting for acceptance.');
    }

    public function finish($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status_id = '5';
        $rfs->save();

        HistoryRFS::create([
            'rfs_id' => RequestForService::latest()->first()->id,
            'updated_by' => auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Request finish.');
    }
}
