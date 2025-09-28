<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerformanceRequest;
use App\Models\Performance;
use App\Models\Position;
use App\Models\Production;
use App\Models\User;
use App\Services\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(
        private PerformanceService $performanceService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'performance_type', 'status']);
        $performances = $this->performanceService->getFilteredPerformances($filters);

        return view('performances.index', compact('performances'));
    }

    public function create(): View
    {
        $productions = Production::active()->ordered()->get();
        $users = User::active()->staff()->ordered()->get();
        $positions = Position::active()->ordered()->get();
        $designers = User::active()->designers()->ordered()->get();

        return view('performances.create', compact('productions', 'users', 'positions', 'designers'));
    }

    public function store(PerformanceRequest $request): RedirectResponse
    {
        $performance = $this->performanceService->createPerformance($request->validated());

        return redirect()->route('performances.show', $performance)
            ->with('success', '公演が正常に作成されました。');
    }

    public function show(Performance $performance): View
    {
        $performance->load(['phases.location', 'staff.user', 'staff.position', 'productions']);

        return view('performances.show', compact('performance'));
    }

    public function edit(Performance $performance): View
    {
        $performance->load(['productions', 'staff']);
        $productions = Production::active()->ordered()->get();
        $users = User::active()->staff()->ordered()->get();
        $positions = Position::active()->ordered()->get();
        $designers = User::active()->designers()->ordered()->get();

        return view('performances.edit', compact('performance', 'productions', 'users', 'positions', 'designers'));
    }

    public function update(PerformanceRequest $request, Performance $performance): RedirectResponse
    {
        $performance = $this->performanceService->updatePerformance($performance, $request->validated());

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
