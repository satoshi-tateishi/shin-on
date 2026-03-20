<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\Performance;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * 汎用ログ記録
     */
    public function log(string $action, Model $subject, ?string $description = null, ?array $properties = null): ActivityLog
    {
        return ActivityLog::log($action, $subject, $description, $properties);
    }

    // ========================================
    // 機材操作ログ
    // ========================================

    /**
     * 機材出庫ログ
     */
    public function logEquipmentCheckout(PhaseEquipment $phaseEquipment, Phase $phase, int $quantity): ActivityLog
    {
        $phaseEquipment->loadMissing('equipment');
        $phase->loadMissing('performance');
        $equipment = $phaseEquipment->equipment;
        $performance = $phase->performance;

        return $this->log(
            'equipment.checkout',
            $equipment,
            "{$equipment->name} × {$quantity} を「{$performance->title}」{$phase->name}に出庫",
            [
                'phase_equipment_id' => $phaseEquipment->id,
                'phase_id' => $phase->id,
                'phase_name' => $phase->name,
                'performance_id' => $performance->id,
                'performance_title' => $performance->title,
                'quantity' => $quantity,
            ]
        );
    }

    /**
     * 機材返却ログ
     */
    public function logEquipmentCheckin(PhaseEquipment $phaseEquipment, Phase $phase, int $quantity): ActivityLog
    {
        $phaseEquipment->loadMissing('equipment');
        $phase->loadMissing('performance');
        $equipment = $phaseEquipment->equipment;
        $performance = $phase->performance;

        return $this->log(
            'equipment.checkin',
            $equipment,
            "{$equipment->name} × {$quantity} を「{$performance->title}」{$phase->name}から返却",
            [
                'phase_equipment_id' => $phaseEquipment->id,
                'phase_id' => $phase->id,
                'phase_name' => $phase->name,
                'performance_id' => $performance->id,
                'performance_title' => $performance->title,
                'quantity' => $quantity,
            ]
        );
    }

    /**
     * 倉庫間移動ログ
     */
    public function logEquipmentTransfer(Equipment $equipment, string $fromLocation, string $toLocation, int $quantity): ActivityLog
    {
        return $this->log(
            'equipment.transfer',
            $equipment,
            "{$equipment->name} × {$quantity} を {$fromLocation} → {$toLocation} に移動",
            [
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'quantity' => $quantity,
            ]
        );
    }

    /**
     * 修理開始ログ
     */
    public function logEquipmentRepairStart(Equipment $equipment, ?string $note = null): ActivityLog
    {
        return $this->log(
            'equipment.repair_start',
            $equipment,
            "{$equipment->name} の修理を開始",
            ['note' => $note]
        );
    }

    /**
     * 修理完了ログ
     */
    public function logEquipmentRepairComplete(Equipment $equipment, ?string $note = null): ActivityLog
    {
        return $this->log(
            'equipment.repair_complete',
            $equipment,
            "{$equipment->name} の修理が完了",
            ['note' => $note]
        );
    }

    // ========================================
    // 公演操作ログ
    // ========================================

    /**
     * 公演作成ログ
     */
    public function logPerformanceCreate(Performance $performance): ActivityLog
    {
        return $this->log(
            'performance.create',
            $performance,
            "公演「{$performance->title}」を作成",
            [
                'performance_type' => $performance->performance_type,
                'director' => $performance->director,
            ]
        );
    }

    /**
     * 公演編集ログ
     */
    public function logPerformanceUpdate(Performance $performance, array $changes = []): ActivityLog
    {
        return $this->log(
            'performance.update',
            $performance,
            "公演「{$performance->title}」を編集",
            ['changes' => $changes]
        );
    }

    // ========================================
    // フェーズ操作ログ
    // ========================================

    /**
     * フェーズ作成ログ
     */
    public function logPhaseCreate(Phase $phase): ActivityLog
    {
        $phase->loadMissing('performance');
        $performance = $phase->performance;

        return $this->log(
            'phase.create',
            $phase,
            "「{$performance->title}」にフェーズ「{$phase->name}」を作成",
            [
                'performance_id' => $performance->id,
                'performance_title' => $performance->title,
                'start_date' => $phase->start_date?->format('Y-m-d'),
                'end_date' => $phase->end_date?->format('Y-m-d'),
            ]
        );
    }

    /**
     * フェーズ編集ログ
     */
    public function logPhaseUpdate(Phase $phase, array $changes = []): ActivityLog
    {
        $phase->loadMissing('performance');
        $performance = $phase->performance;

        return $this->log(
            'phase.update',
            $phase,
            "「{$performance->title}」のフェーズ「{$phase->name}」を編集",
            [
                'performance_id' => $performance->id,
                'performance_title' => $performance->title,
                'changes' => $changes,
            ]
        );
    }

    // ========================================
    // 機材継承ログ
    // ========================================

    /**
     * 機材継承実行ログ
     */
    public function logInheritanceExecute(Phase $sourcePhase, Phase $targetPhase, int $inheritedCount): ActivityLog
    {
        $sourcePhase->loadMissing('performance');
        $targetPhase->loadMissing('performance');
        $sourcePerformance = $sourcePhase->performance;
        $targetPerformance = $targetPhase->performance;

        return $this->log(
            'inheritance.execute',
            $targetPhase,
            "「{$sourcePerformance->title}」{$sourcePhase->name} から「{$targetPerformance->title}」{$targetPhase->name} へ {$inheritedCount}件の機材を継承",
            [
                'source_phase_id' => $sourcePhase->id,
                'source_phase_name' => $sourcePhase->name,
                'source_performance_id' => $sourcePerformance->id,
                'source_performance_title' => $sourcePerformance->title,
                'target_phase_id' => $targetPhase->id,
                'target_phase_name' => $targetPhase->name,
                'target_performance_id' => $targetPerformance->id,
                'target_performance_title' => $targetPerformance->title,
                'inherited_count' => $inheritedCount,
            ]
        );
    }

    // ========================================
    // ユーザーログ
    // ========================================

    /**
     * ログインログ
     */
    public function logUserLogin(User $user): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user.login',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'subject_name' => $user->name,
            'description' => "{$user->name} がログインしました",
            'properties' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
