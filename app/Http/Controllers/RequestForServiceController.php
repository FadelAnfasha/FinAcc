<?php

namespace App\Http\Controllers;

use App\Models\RequestForService;
use Illuminate\Http\Request;
use Inertia\Inertia;


class RequestForServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $services = RequestForService::all();
        return Inertia::render('rfs/index', [
            'services' => $services,
            'auth' => [
                'user' => [
                    'name' => auth()->user()->name,
                    'npk' => auth()->user()->npk,
                    'role' => auth()->user()->getRoleNames()->first(), // Kirim role
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {   
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npk' => 'required|string|max:50',
            'priority' => 'required|in:low,medium,high,urgent',
            'input_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'required',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls|max:10240',
        ]);

        // Upload file if exists
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachment', 'public');
        }

        RequestForService::create($validated);

        return redirect()->route('rfs.index')->with('success', 'Request submitted successfully.');
    }

    public function accept($id)
    {   
        $rfs = RequestForService::findOrFail($id);
        $rfs->status = 'accepted';
        $rfs->save();

        return redirect()->back()->with('success', 'Request accepted.');
    }

    public function reject($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status = 'rejected';
        $rfs->save();

        return redirect()->back()->with('danger', 'Request rejected.');
    }

    public function execute($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status = 'in_progress';
        $rfs->save();

        return redirect()->back()->with('success', 'Request executed.');
    }

    public function finish($id)
    {
        $rfs = RequestForService::findOrFail($id);
        $rfs->status = 'finish';
        $rfs->save();

        return redirect()->back()->with('success', 'Request finish.');
    }

}
