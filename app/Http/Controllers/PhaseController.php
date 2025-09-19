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
        $locations = Location::active()->orderBy('name')->get();

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
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['performance_id'] = $performance->id;

        $overlappingPhases = Phase::where('performance_id', $performance->id)
            ->overlappingWith($validated['start_date'], $validated['end_date'])
            ->exists();

        if ($overlappingPhases) {
            return back()->withErrors([
                'start_date' => '指定された期間は他のフェーズと重複しています。',
            ])->withInput();
        }

        $phase = Phase::create($validated);

        return redirect()->route('performances.phases.show', [$performance, $phase])
            ->with('success', 'フェーズが正常に作成されました。');
    }

    public function show(Performance $performance, Phase $phase): View
    {
        $phase->load('location');

        return view('phases.show', compact('performance', 'phase'));
    }

    public function edit(Performance $performance, Phase $phase): View
    {
        $locations = Location::active()->orderBy('name')->get();

        return view('phases.edit', compact('performance', 'phase', 'locations'));
    }

    public function update(Request $request, Performance $performance, Phase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'sort' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $overlappingPhases = Phase::where('performance_id', $performance->id)
            ->overlappingWith($validated['start_date'], $validated['end_date'], $phase->id)
            ->exists();

        if ($overlappingPhases) {
            return back()->withErrors([
                'start_date' => '指定された期間は他のフェーズと重複しています。',
            ])->withInput();
        }

        $phase->update($validated);

        return redirect()->route('performances.phases.show', [$performance, $phase])
            ->with('success', 'フェーズが正常に更新されました。');
    }

    public function destroy(Performance $performance, Phase $phase): RedirectResponse
    {
        $phase->delete();

        return redirect()->route('performances.phases.index', $performance)
            ->with('success', 'フェーズが正常に削除されました。');
    }
}
