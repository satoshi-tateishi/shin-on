<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhaseRequest;
use App\Models\CompanyLogo;
use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
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

    public function exportPdf(Phase $phase)
    {
        $phase->load('location', 'performance.productions', 'performance.staff.user', 'performance.staff.position');
        $performance = $phase->performance;

        // 使用機材データ取得（reserved, checked_out のみ、返却済み除外）
        $phaseEquipments = $phase->phaseEquipments()
            ->with(['equipment.subcategory.category'])
            ->whereIn('phase_equipment.status', ['reserved', 'checked_out'])
            ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
            ->orderBy('equipments.sort')
            ->select('phase_equipment.*')
            ->get();

        // 同じ機材名でグループ化
        $groupedEquipments = $phaseEquipments->groupBy(function ($item) {
            return $item->equipment->name;
        })->map(function ($group) {
            $first = $group->first();

            return (object) [
                'equipment' => $first->equipment,
                'total_quantity' => $group->sum('quantity'),
                'company_numbers' => $group->map(function ($item) {
                    return $item->equipment->company_number;
                })->filter()->unique()->implode(', '),
            ];
        });

        // アクティブなロゴを取得
        $companyLogo = CompanyLogo::getActiveLogo();
        $logoPath = $companyLogo ? public_path('storage/'.$companyLogo->file_path) : null;

        // PDFデータ準備
        $data = [
            'phase' => $phase,
            'performance' => $performance,
            'groupedEquipments' => $groupedEquipments,
            'exportDate' => now()->format('Y年m月d日 H:i'),
            'logoPath' => $logoPath,
        ];

        // PDF生成
        $pdf = Pdf::loadView('phases.pdf', $data);

        // DOMPDFの設定
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        $pdf->setPaper('A4', 'portrait');

        // ファイル名生成
        $phaseStartDate = $phase->start_date ? $phase->start_date->format('Y-md') : now()->format('Y-md');
        $filename = sprintf(
            '%s_%s_%s_%s.pdf',
            $phaseStartDate,
            $performance->title,
            $phase->name,
            now()->format('Ymd')
        );

        return $pdf->download($filename);
    }
}
