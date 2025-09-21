<?php

namespace App\Console\Commands;

use App\Models\Phase;
use App\Models\PhaseEquipment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdatePhaseEquipmentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phase:update-equipment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'フェーズの開始日・終了日に基づいて機材使用ステータスを自動更新';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $updatedCount = 0;

        // 今日開始するフェーズの機材を「出庫中」に変更
        $startingPhases = Phase::where('start_date', $today)->get();
        foreach ($startingPhases as $phase) {
            $reservedEquipments = PhaseEquipment::where('phase_id', $phase->id)
                ->where('status', 'reserved')
                ->get();

            foreach ($reservedEquipments as $equipment) {
                $equipment->update([
                    'status' => 'checked_out',
                    'checkout_date' => $today,
                    'checkout_user_id' => null, // 自動更新の場合はユーザー指定なし
                ]);
                $updatedCount++;
            }

            if ($reservedEquipments->count() > 0) {
                $this->info("フェーズ「{$phase->name}」: {$reservedEquipments->count()}件の機材を出庫中に変更");
            }
        }

        // 今日終了するフェーズの機材を「返却済み」に変更
        $endingPhases = Phase::where('end_date', $today)->get();
        foreach ($endingPhases as $phase) {
            $checkedOutEquipments = PhaseEquipment::where('phase_id', $phase->id)
                ->where('status', 'checked_out')
                ->get();

            foreach ($checkedOutEquipments as $equipment) {
                $equipment->update([
                    'status' => 'checked_in',
                    'checkin_date' => $today,
                    'checkin_user_id' => null, // 自動更新の場合はユーザー指定なし
                ]);
                $updatedCount++;
            }

            if ($checkedOutEquipments->count() > 0) {
                $this->info("フェーズ「{$phase->name}」: {$checkedOutEquipments->count()}件の機材を返却済みに変更");
            }
        }

        if ($updatedCount === 0) {
            $this->info('今日更新対象のフェーズ機材はありませんでした。');
        } else {
            $this->info("合計 {$updatedCount} 件の機材ステータスを更新しました。");
        }

        return Command::SUCCESS;
    }
}