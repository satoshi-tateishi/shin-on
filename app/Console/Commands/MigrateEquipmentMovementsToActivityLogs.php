<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\EquipmentMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateEquipmentMovementsToActivityLogs extends Command
{
    protected $signature = 'activity:migrate-equipment-movements {--dry-run : Show what would be migrated without actually doing it}';

    protected $description = 'Migrate existing equipment_movements data to activity_logs table';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in dry-run mode. No data will be modified.');
        }

        $this->info('Starting migration of equipment_movements to activity_logs...');

        $movements = EquipmentMovement::with(['equipment', 'phase.performance', 'movedBy'])
            ->orderBy('moved_at')
            ->get();

        $this->info("Found {$movements->count()} equipment movements to migrate.");

        if ($movements->isEmpty()) {
            $this->info('No movements to migrate.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($movements->count());
        $bar->start();

        $migratedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($movements as $movement) {
            try {
                // アクションタイプの変換
                $action = $this->convertMovementType($movement->movement_type);

                if (! $action) {
                    $skippedCount++;
                    $bar->advance();

                    continue;
                }

                // 説明文の生成
                $description = $this->generateDescription($movement);

                // プロパティの生成
                $properties = $this->generateProperties($movement);

                if (! $isDryRun) {
                    // 重複チェック
                    $exists = ActivityLog::where('action', $action)
                        ->where('subject_type', get_class($movement->equipment))
                        ->where('subject_id', $movement->equipment_id)
                        ->where('created_at', $movement->moved_at)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        $bar->advance();

                        continue;
                    }

                    // ActivityLog作成
                    DB::table('activity_logs')->insert([
                        'user_id' => $movement->moved_by,
                        'action' => $action,
                        'subject_type' => get_class($movement->equipment),
                        'subject_id' => $movement->equipment_id,
                        'subject_name' => $movement->equipment?->name,
                        'description' => $description,
                        'properties' => json_encode($properties),
                        'created_at' => $movement->moved_at,
                    ]);
                }

                $migratedCount++;
            } catch (\Exception $e) {
                $errors[] = "Movement ID {$movement->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Migration completed!');
        $this->info("- Migrated: {$migratedCount}");
        $this->info("- Skipped: {$skippedCount}");
        $this->info('- Errors: '.count($errors));

        if (! empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return Command::SUCCESS;
    }

    private function convertMovementType(string $movementType): ?string
    {
        return match ($movementType) {
            'checkout' => 'equipment.checkout',
            'checkin' => 'equipment.checkin',
            'transfer' => 'equipment.transfer',
            'repair_start' => 'equipment.repair_start',
            'repair_complete' => 'equipment.repair_complete',
            'maintenance' => 'equipment.maintenance',
            'disposal' => 'equipment.disposal',
            default => null,
        };
    }

    private function generateDescription(EquipmentMovement $movement): string
    {
        $equipment = $movement->equipment;
        $phase = $movement->phase;
        $performance = $phase?->performance;

        $equipmentName = $equipment?->name ?? '不明な機材';
        $quantity = $movement->quantity;

        return match ($movement->movement_type) {
            'checkout' => $performance
                ? "{$equipmentName} × {$quantity} を「{$performance->title}」{$phase->name}に出庫"
                : "{$equipmentName} × {$quantity} を出庫",
            'checkin' => $performance
                ? "{$equipmentName} × {$quantity} を「{$performance->title}」{$phase->name}から返却"
                : "{$equipmentName} × {$quantity} を返却",
            'transfer' => "{$equipmentName} × {$quantity} を倉庫間移動",
            'repair_start' => "{$equipmentName} の修理を開始",
            'repair_complete' => "{$equipmentName} の修理が完了",
            'maintenance' => "{$equipmentName} のメンテナンス",
            'disposal' => "{$equipmentName} を廃棄",
            default => "{$equipmentName} の操作",
        };
    }

    private function generateProperties(EquipmentMovement $movement): array
    {
        $properties = [
            'equipment_movement_id' => $movement->id,
            'quantity' => $movement->quantity,
        ];

        if ($movement->phase_id) {
            $properties['phase_id'] = $movement->phase_id;
            $properties['phase_name'] = $movement->phase?->name;
        }

        if ($movement->phase?->performance) {
            $properties['performance_id'] = $movement->phase->performance->id;
            $properties['performance_title'] = $movement->phase->performance->title;
        }

        if ($movement->from_location_id) {
            $properties['from_location_id'] = $movement->from_location_id;
            $properties['from_location_name'] = $movement->fromLocation?->name;
        }

        if ($movement->to_location_id) {
            $properties['to_location_id'] = $movement->to_location_id;
            $properties['to_location_name'] = $movement->toLocation?->name;
        }

        if ($movement->note) {
            $properties['note'] = $movement->note;
        }

        return $properties;
    }
}
