<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhaseRequest;
use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhaseController extends Controller
{
    public function index(Performance $performance): RedirectResponse
    {
        return redirect()->route('performances.show', $performance);
    }

    public function create(Performance $performance): View
    {
        $locations = Location::active()->ordered()->get();

        return view('phases.create', compact('performance', 'locations'));
    }

    public function store(PhaseRequest $request, Performance $performance): RedirectResponse
    {
        $validated = $request->validated();
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

    public function update(PhaseRequest $request, Phase $phase): RedirectResponse
    {
        $phase->update($request->validated());

        return redirect()->route('phases.show', $phase)
            ->with('success', 'フェーズが正常に更新されました。');
    }

    public function destroy(Phase $phase): RedirectResponse
    {
        $performance = $phase->performance;
        $phase->delete();

        return redirect()->route('performances.show', $performance)
            ->with('success', 'フェーズが正常に削除されました。');
    }
}
