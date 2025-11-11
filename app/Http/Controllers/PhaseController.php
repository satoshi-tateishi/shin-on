<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhaseRequest;
use App\Models\CompanyLogo;
use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use App\Services\LineWorksBotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function sendPdfToLineWorks(Phase $phase): RedirectResponse
    {
        $tempFilePath = null;

        try {
            // ユーザー情報取得
            $user = auth()->user();

            if (! $user->lineworks_id) {
                return redirect()->route('phases.show', $phase)
                    ->with('error', 'LINE WORKS IDが設定されていません。');
            }

            // PDF生成（exportPdfメソッドと同じロジック）
            $phase->load('location', 'performance.productions', 'performance.staff.user', 'performance.staff.position');
            $performance = $phase->performance;

            // 使用機材データ取得
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

            // 一時ディレクトリに保存
            $tempDir = 'temp';
            if (! Storage::exists($tempDir)) {
                Storage::makeDirectory($tempDir);
            }

            $tempFileName = uniqid('phase_pdf_').'.pdf';
            $tempFilePath = storage_path("app/{$tempDir}/{$tempFileName}");

            // PDFを一時ファイルとして保存
            file_put_contents($tempFilePath, $pdf->output());

            // LINE WORKSに送信
            $botService = app(LineWorksBotService::class);
            $botService->sendPdfToUser($user->lineworks_id, $tempFilePath, $filename);

            Log::info('Phase PDF sent to LINE WORKS', [
                'user_id' => $user->id,
                'lineworks_id' => $user->lineworks_id,
                'phase_id' => $phase->id,
                'filename' => $filename,
            ]);

            return redirect()->route('phases.show', $phase)
                ->with('success', 'PDFファイルをLINE WORKSに送信しました。');
        } catch (\Exception $e) {
            Log::error('Failed to send Phase PDF to LINE WORKS', [
                'user_id' => auth()->id(),
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('phases.show', $phase)
                ->with('error', 'PDFの送信に失敗しました: '.$e->getMessage());
        } finally {
            // 一時ファイルを削除
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }
}
