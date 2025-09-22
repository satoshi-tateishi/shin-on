<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use App\Models\PerformanceStaff;
use App\Models\Position;
use App\Models\Production;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Performance::with(['phases.location', 'staff'])
            ->where('performances.is_active', 1);

        // 検索フィルター
        if ($request->filled('search')) {
            $query->where('performances.title', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('performance_type')) {
            $query->where('performances.performance_type', $request->performance_type);
        }

        if ($request->filled('status')) {
            $query->where('performances.status', $request->status);
        }

        $performances = $query->leftJoin('phases', 'performances.id', '=', 'phases.performance_id')
            ->select('performances.*', \DB::raw('COUNT(phases.id) as phases_count'))
            ->orderByRaw('COALESCE(MAX(phases.start_date), performances.created_at) DESC')
            ->groupBy('performances.id')
            ->paginate(15);

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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'performance_type' => 'required|in:演劇,ミュージカル,リーディング,ダンス,イベント,コンサート,その他',
            'director' => 'nullable|string|max:255',
            'status' => 'required|in:planning,preparation,in_progress,completed,cancelled',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
            'production_ids' => 'nullable|array',
            'production_ids.*' => 'exists:productions,id',
            'staff' => 'nullable|array',
            'staff.*.user_id' => 'nullable|exists:users,id',
            'staff.*.position_id' => 'nullable|exists:positions,id',
            'sound_designers' => 'nullable|array',
            'sound_designers.*' => 'nullable|exists:users,id',
        ]);

        $performance = Performance::create($validated);

        // プロダクションの関連付け
        if (! empty($validated['production_ids'])) {
            $performance->productions()->attach($validated['production_ids']);
        }

        // 担当者の関連付け
        if (! empty($validated['staff'])) {
            foreach ($validated['staff'] as $staffData) {
                if (! empty($staffData['user_id']) && ! empty($staffData['position_id'])) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $staffData['user_id'],
                        'position_id' => $staffData['position_id'],
                    ]);
                }
            }
        }

        // サウンドデザイナーの関連付け
        if (! empty($validated['sound_designers'])) {
            foreach ($validated['sound_designers'] as $designerId) {
                if (! empty($designerId)) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $designerId,
                        'position_id' => 1, // サウンドデザインのポジションID
                    ]);
                }
            }
        }

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

    public function update(Request $request, Performance $performance): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'performance_type' => 'required|in:演劇,ミュージカル,リーディング,ダンス,イベント,コンサート,その他',
            'director' => 'nullable|string|max:255',
            'status' => 'required|in:planning,preparation,in_progress,completed,cancelled',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
            'production_ids' => 'nullable|array',
            'production_ids.*' => 'exists:productions,id',
            'staff' => 'nullable|array',
            'staff.*.user_id' => 'nullable|exists:users,id',
            'staff.*.position_id' => 'nullable|exists:positions,id',
            'sound_designers' => 'nullable|array',
            'sound_designers.*' => 'nullable|exists:users,id',
        ]);

        $performance->update($validated);

        // プロダクションの同期（既存の関連を削除して新しい関連を追加）
        $performance->productions()->sync($validated['production_ids'] ?? []);

        // 担当者の同期
        $performance->staff()->delete(); // 既存の担当者を削除

        if (! empty($validated['staff'])) {
            foreach ($validated['staff'] as $staffData) {
                if (! empty($staffData['user_id']) && ! empty($staffData['position_id'])) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $staffData['user_id'],
                        'position_id' => $staffData['position_id'],
                    ]);
                }
            }
        }

        // サウンドデザイナーの更新
        if (! empty($validated['sound_designers'])) {
            foreach ($validated['sound_designers'] as $designerId) {
                if (! empty($designerId)) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $designerId,
                        'position_id' => 1, // サウンドデザインのポジションID
                    ]);
                }
            }
        }

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
