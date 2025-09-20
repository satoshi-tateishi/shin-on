<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhaseController extends Controller
{
    public function index(Performance $performance): View
    {
        $phases = $performance->phasesOrdered()
            ->with('location')
            ->get();

        return view('phases.index', compact('performance', 'phases'));
    }

    public function create(Performance $performance): View
    {
        $locations = Location::active()->ordered()->get();

        return view('phases.create', compact('performance', 'locations'));
    }

    public function store(Request $request, Performance $performance): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'sort' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['performance_id'] = $performance->id;

        $phase = Phase::create($validated);

        return redirect()->route('phases.show', $phase)
            ->with('success', 'フェーズが正常に作成されました。');
    }

    public function show(Phase $phase): View
    {
        $phase->load('location', 'performance');
        $performance = $phase->performance;

        return view('phases.show', compact('performance', 'phase'));
    }

    public function edit(Phase $phase): View
    {
        $phase->load('performance');
        $performance = $phase->performance;
        $locations = Location::active()->ordered()->get();

        return view('phases.edit', compact('performance', 'phase', 'locations'));
    }

    public function update(Request $request, Phase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'sort' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $phase->update($validated);

        return redirect()->route('phases.show', $phase)
            ->with('success', 'フェーズが正常に更新されました。');
    }

    public function destroy(Phase $phase): RedirectResponse
    {
        $performance = $phase->performance;
        $phase->delete();

        return redirect()->route('performances.phases.index', $performance)
            ->with('success', 'フェーズが正常に削除されました。');
    }
}
