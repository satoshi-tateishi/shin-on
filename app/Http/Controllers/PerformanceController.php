<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function index(): View
    {
        $performances = Performance::with(['phases', 'staff'])
            ->active()
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return view('performances.index', compact('performances'));
    }

    public function create(): View
    {
        return view('performances.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'performance_type' => 'required|in:演劇,ミュージカル,コンサート,その他',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'venue' => 'nullable|string|max:255',
            'director' => 'nullable|string|max:255',
            'producer' => 'nullable|string|max:255',
            'status' => 'required|in:planning,preparation,in_progress,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $performance = Performance::create($validated);

        return redirect()->route('performances.show', $performance)
            ->with('success', '公演が正常に作成されました。');
    }

    public function show(Performance $performance): View
    {
        $performance->load(['phases.location', 'staff.user', 'staff.position']);

        return view('performances.show', compact('performance'));
    }

    public function edit(Performance $performance): View
    {
        return view('performances.edit', compact('performance'));
    }

    public function update(Request $request, Performance $performance): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'performance_type' => 'required|in:演劇,ミュージカル,コンサート,その他',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'venue' => 'nullable|string|max:255',
            'director' => 'nullable|string|max:255',
            'producer' => 'nullable|string|max:255',
            'status' => 'required|in:planning,preparation,in_progress,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $performance->update($validated);

        return redirect()->route('performances.show', $performance)
            ->with('success', '公演が正常に更新されました。');
    }

    public function destroy(Performance $performance): RedirectResponse
    {
        $performance->delete();

        return redirect()->route('performances.index')
            ->with('success', '公演が正常に削除されました。');
    }
}
