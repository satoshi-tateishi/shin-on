<?php

use App\Http\Controllers\Auth\LineWorksController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DropboxAuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTransferController;
use App\Http\Controllers\Master\EquipmentCategoryController;
use App\Http\Controllers\Master\EquipmentController;
use App\Http\Controllers\Master\EquipmentSetController;
use App\Http\Controllers\Master\EquipmentSubcategoryController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\PositionController;
use App\Http\Controllers\Master\ProductionController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\PhaseEquipmentController;
use App\Http\Controllers\PhaseEquipmentApiController;
use App\Http\Controllers\PhaseEquipmentCheckoutController;
use App\Http\Controllers\PhaseEquipmentInheritanceController;
use App\Http\Controllers\RepairRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
});

// LINE WORKS OAuth認証ルート
Route::prefix('auth/lineworks')->group(function () {
    Route::get('redirect', [LineWorksController::class, 'redirect'])->name('lineworks.redirect');
    Route::get('callback', [LineWorksController::class, 'callback'])->name('lineworks.callback');
    Route::post('process-id-token', [LineWorksController::class, 'processIdToken'])->name('lineworks.process-id-token');
});

// 2FA（二段階認証）ルート
Route::prefix('two-factor')->name('two-factor.')->group(function () {
    Route::get('challenge', [TwoFactorController::class, 'show'])->name('show');
    Route::post('verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('resend', [TwoFactorController::class, 'resend'])->name('resend');
});

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::post('/logout', [LineWorksController::class, 'logout'])->name('logout');

    // 管理者機能ルート
    Route::prefix('admin')->name('admin.')->group(function () {
        // 会社設定管理
        Route::get('company-info', [\App\Http\Controllers\Admin\CompanyInfoController::class, 'index'])->name('company-info.index');
        Route::post('company-info/logo', [\App\Http\Controllers\Admin\CompanyInfoController::class, 'store'])->name('company-info.store');
        Route::delete('company-info/logo', [\App\Http\Controllers\Admin\CompanyInfoController::class, 'destroy'])->name('company-info.destroy');
        Route::post('company-info', [\App\Http\Controllers\Admin\CompanyInfoController::class, 'storeCompanyInfo'])->name('company-info.store-company-info');

        // バックアップ管理（管理者権限のみ）
        Route::prefix('backup')->name('backup.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
            Route::post('run', [\App\Http\Controllers\Admin\BackupController::class, 'runBackup'])->name('run');
            Route::get('list', [\App\Http\Controllers\Admin\BackupController::class, 'listBackups'])->name('list');
            Route::post('test', [\App\Http\Controllers\Admin\BackupController::class, 'testConnection'])->name('test');
            Route::post('refresh-token', [\App\Http\Controllers\Admin\BackupController::class, 'refreshToken'])->name('refresh-token');

            // 復元機能
            Route::get('restorable', [\App\Http\Controllers\Admin\BackupController::class, 'getRestorableBackups'])->name('restorable');
            Route::post('download', [\App\Http\Controllers\Admin\BackupController::class, 'downloadBackup'])->name('download');
            Route::post('restore', [\App\Http\Controllers\Admin\BackupController::class, 'restoreDatabase'])->name('restore');
            Route::post('validate-restore', [\App\Http\Controllers\Admin\BackupController::class, 'validateRestore'])->name('validate-restore');

            // 環境設定バックアップ機能
            Route::prefix('env')->name('env.')->group(function () {
                Route::post('backup', [\App\Http\Controllers\Admin\EnvBackupController::class, 'backupEnv'])->name('backup');
                Route::get('list', [\App\Http\Controllers\Admin\EnvBackupController::class, 'listEnvBackups'])->name('list');
                Route::post('restore', [\App\Http\Controllers\Admin\EnvBackupController::class, 'restoreEnv'])->name('restore');
                Route::post('validate-password', [\App\Http\Controllers\Admin\EnvBackupController::class, 'validatePassword'])->name('validate-password');
            });
        });

        // Role権限設定（管理者のみ）
        Route::get('role-permissions', [\App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->name('role-permissions.index');
    });

    // Dropbox OAuth認証ルート（管理者権限のみ）
    Route::middleware(['auth'])->prefix('auth/dropbox')->name('dropbox.')->group(function () {
        Route::get('redirect', [DropboxAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [DropboxAuthController::class, 'callback'])->name('callback');
    });

    // Dropbox API ルート（管理者権限のみ）
    Route::middleware(['auth'])->prefix('api/dropbox')->name('api.dropbox.')->group(function () {
        Route::get('auth-status', [DropboxAuthController::class, 'authStatus'])->name('auth-status');
        Route::post('refresh-token', [DropboxAuthController::class, 'refreshToken'])->name('refresh-token');
        Route::post('revoke-auth', [DropboxAuthController::class, 'revokeAuth'])->name('revoke-auth');
    });

    // マスタ管理ルート
    Route::prefix('master')->name('master.')->group(function () {
        // ポジションマスタ
        Route::post('positions/update-sort', [PositionController::class, 'updateSort'])->name('positions.update-sort');
        Route::post('positions/toggle-active', [PositionController::class, 'toggleActive'])->name('positions.toggle-active');
        Route::post('positions/bulk-delete', [PositionController::class, 'bulkDelete'])->name('positions.bulk-delete');
        Route::get('positions/export-csv', [PositionController::class, 'exportCsv'])->name('positions.export-csv');
        Route::get('positions/template-csv', [PositionController::class, 'templateCsv'])->name('positions.template-csv');
        Route::post('positions/import-csv', [PositionController::class, 'importCsv'])->name('positions.import-csv');
        Route::resource('positions', PositionController::class);

        // 機材カテゴリマスタ
        Route::post('equipment-categories/update-sort', [EquipmentCategoryController::class, 'updateSort'])->name('equipment-categories.update-sort');
        Route::post('equipment-categories/toggle-active', [EquipmentCategoryController::class, 'toggleActive'])->name('equipment-categories.toggle-active');
        Route::post('equipment-categories/bulk-delete', [EquipmentCategoryController::class, 'bulkDelete'])->name('equipment-categories.bulk-delete');
        Route::get('equipment-categories/export-csv', [EquipmentCategoryController::class, 'exportCsv'])->name('equipment-categories.export-csv');
        Route::get('equipment-categories/template-csv', [EquipmentCategoryController::class, 'templateCsv'])->name('equipment-categories.template-csv');
        Route::post('equipment-categories/import-csv', [EquipmentCategoryController::class, 'importCsv'])->name('equipment-categories.import-csv');
        Route::resource('equipment-categories', EquipmentCategoryController::class);

        // 機材サブカテゴリマスタ
        Route::post('equipment-subcategories/update-sort', [EquipmentSubcategoryController::class, 'updateSort'])->name('equipment-subcategories.update-sort');
        Route::post('equipment-subcategories/toggle-active', [EquipmentSubcategoryController::class, 'toggleActive'])->name('equipment-subcategories.toggle-active');
        Route::post('equipment-subcategories/bulk-delete', [EquipmentSubcategoryController::class, 'bulkDelete'])->name('equipment-subcategories.bulk-delete');
        Route::get('equipment-subcategories/export-csv', [EquipmentSubcategoryController::class, 'exportCsv'])->name('equipment-subcategories.export-csv');
        Route::get('equipment-subcategories/template-csv', [EquipmentSubcategoryController::class, 'templateCsv'])->name('equipment-subcategories.template-csv');
        Route::post('equipment-subcategories/import-csv', [EquipmentSubcategoryController::class, 'importCsv'])->name('equipment-subcategories.import-csv');
        Route::resource('equipment-subcategories', EquipmentSubcategoryController::class);

        // 機材マスタ
        Route::post('equipments/update-sort', [EquipmentController::class, 'updateSort'])->name('equipments.update-sort');
        Route::post('equipments/toggle-active', [EquipmentController::class, 'toggleActive'])->name('equipments.toggle-active');
        Route::post('equipments/bulk-delete', [EquipmentController::class, 'bulkDelete'])->name('equipments.bulk-delete');
        Route::get('equipments/export-csv', [EquipmentController::class, 'exportCsv'])->name('equipments.export-csv');
        Route::get('equipments/export-pdf', [EquipmentController::class, 'exportPdf'])->name('equipments.export-pdf');
        Route::post('equipments/send-lineworks', [EquipmentController::class, 'sendPdfToLineWorks'])->name('equipments.send-lineworks');
        Route::get('equipments/template-csv', [EquipmentController::class, 'templateCsv'])->name('equipments.template-csv');
        Route::post('equipments/import-csv', [EquipmentController::class, 'importCsv'])->name('equipments.import-csv');
        Route::get('equipments/subcategories', [EquipmentController::class, 'getSubcategories'])->name('equipments.subcategories');
        Route::resource('equipments', EquipmentController::class);

        // 機材セットマスタ
        Route::post('equipment-sets/update-sort', [EquipmentSetController::class, 'updateSort'])->name('equipment-sets.update-sort');
        Route::post('equipment-sets/toggle-active', [EquipmentSetController::class, 'toggleActive'])->name('equipment-sets.toggle-active');
        Route::post('equipment-sets/bulk-delete', [EquipmentSetController::class, 'bulkDelete'])->name('equipment-sets.bulk-delete');
        Route::get('equipment-sets/export-csv', [EquipmentSetController::class, 'exportCsv'])->name('equipment-sets.export-csv');
        Route::get('equipment-sets/template-csv', [EquipmentSetController::class, 'templateCsv'])->name('equipment-sets.template-csv');
        Route::post('equipment-sets/import-csv', [EquipmentSetController::class, 'importCsv'])->name('equipment-sets.import-csv');

        // 機材セット内容管理API
        Route::post('equipment-sets/{equipmentSet}/items', [EquipmentSetController::class, 'addEquipment'])->name('equipment-sets.add-equipment');
        Route::delete('equipment-sets/{equipmentSet}/items/{equipment}', [EquipmentSetController::class, 'removeEquipment'])->name('equipment-sets.remove-equipment');
        Route::patch('equipment-sets/{equipmentSet}/items/sort', [EquipmentSetController::class, 'updateItemSort'])->name('equipment-sets.update-item-sort');
        Route::patch('equipment-sets/{equipmentSet}/items/{equipment}', [EquipmentSetController::class, 'updateEquipmentItem'])->name('equipment-sets.update-equipment-item');

        Route::resource('equipment-sets', EquipmentSetController::class);

        // 使用場所マスタ
        Route::post('locations/update-sort', [LocationController::class, 'updateSort'])->name('locations.update-sort');
        Route::post('locations/toggle-active', [LocationController::class, 'toggleActive'])->name('locations.toggle-active');
        Route::post('locations/bulk-delete', [LocationController::class, 'bulkDelete'])->name('locations.bulk-delete');
        Route::get('locations/export-csv', [LocationController::class, 'exportCsv'])->name('locations.export-csv');
        Route::get('locations/template-csv', [LocationController::class, 'templateCsv'])->name('locations.template-csv');
        Route::post('locations/import-csv', [LocationController::class, 'importCsv'])->name('locations.import-csv');
        Route::resource('locations', LocationController::class);

        // プロダクションマスタ
        Route::post('productions/update-sort', [ProductionController::class, 'updateSort'])->name('productions.update-sort');
        Route::post('productions/toggle-active', [ProductionController::class, 'toggleActive'])->name('productions.toggle-active');
        Route::post('productions/bulk-delete', [ProductionController::class, 'bulkDelete'])->name('productions.bulk-delete');
        Route::get('productions/export-csv', [ProductionController::class, 'exportCsv'])->name('productions.export-csv');
        Route::get('productions/template-csv', [ProductionController::class, 'templateCsv'])->name('productions.template-csv');
        Route::post('productions/import-csv', [ProductionController::class, 'importCsv'])->name('productions.import-csv');
        Route::resource('productions', ProductionController::class);

        // ユーザーマスタ
        Route::get('users/export-csv', [UserController::class, 'exportCsv'])->name('users.export-csv');
        Route::get('users/template-csv', [UserController::class, 'templateCsv'])->name('users.template-csv');
        // 管理者のみ：ユーザーCRUD操作
        Route::middleware('role:admin')->group(function () {
            Route::post('users/import-csv', [UserController::class, 'importCsv'])->name('users.import-csv');
            Route::post('users/update-sort', [UserController::class, 'updateSort'])->name('users.update-sort');
            Route::delete('users/{user}/remove-icon', [UserController::class, 'removeIcon'])->name('master.users.remove-icon');
            Route::resource('users', UserController::class)->except(['index', 'show']);
        });
        Route::resource('users', UserController::class)->only(['index', 'show']);
    });

    // 公演・フェーズ管理ルート
    Route::resource('performances', PerformanceController::class)->middleware(['performance.access'])->except(['index', 'show']);
    Route::resource('performances', PerformanceController::class)->only(['index', 'show']);
    Route::resource('performances.phases', PhaseController::class)->shallow()->middleware(['performance.access'])->except(['index', 'show']);
    Route::get('phases/{phase}', [PhaseController::class, 'show'])->name('phases.show');
    Route::get('phases/{phase}/export-pdf', [PhaseController::class, 'exportPdf'])->name('phases.export-pdf');
    Route::post('phases/{phase}/send-lineworks', [PhaseController::class, 'sendPdfToLineWorks'])->name('phases.send-lineworks')->middleware(['performance.access']);

    // フェーズ機材使用管理ルート
    Route::prefix('phases/{phase}')->name('phases.')->middleware(['performance.access'])->group(function () {
        // 一括ステータス変更（resourceルートより前に配置）
        Route::patch('equipment/bulk-checkout', [PhaseEquipmentCheckoutController::class, 'bulkCheckout'])->name('equipment.bulk-checkout');
        Route::patch('equipment/bulk-checkout-reserved', [PhaseEquipmentCheckoutController::class, 'bulkCheckoutReserved'])->name('equipment.bulk-checkout-reserved');
        Route::patch('equipment/bulk-checkout-checked-in', [PhaseEquipmentCheckoutController::class, 'bulkCheckoutCheckedIn'])->name('equipment.bulk-checkout-checked-in');
        Route::patch('equipment/bulk-checkin', [PhaseEquipmentCheckoutController::class, 'bulkCheckin'])->name('equipment.bulk-checkin');

        // AJAX API（resourceルートより前に配置）
        Route::get('available-equipment', [PhaseEquipmentApiController::class, 'getAvailableEquipment'])->name('available-equipment');
        Route::get('equipment-set-availability', [PhaseEquipmentApiController::class, 'checkSetAvailability'])->name('equipment-set-availability');
        Route::get('equipment/checked-out-equipments', [PhaseEquipmentApiController::class, 'getCheckedOutEquipments'])->name('equipment.checked-out-equipments');
        Route::get('equipment/{phaseEquipment}/equipment-info', [PhaseEquipmentApiController::class, 'getEquipmentInfo'])->name('equipment.equipment-info');

        // 機材継承API
        Route::get('inheritable-target-phases', [PhaseEquipmentInheritanceController::class, 'getInheritableTargetPhases'])->name('inheritable-target-phases');
        Route::get('inheritance-preview', [PhaseEquipmentInheritanceController::class, 'getInheritancePreview'])->name('inheritance-preview');
        Route::post('inherit-to', [PhaseEquipmentInheritanceController::class, 'inheritEquipment'])->name('inherit-to');

        Route::resource('equipment', PhaseEquipmentController::class)->parameter('equipment', 'phaseEquipment');

        // 機材出庫・返却
        Route::patch('equipment/{phaseEquipment}/checkout', [PhaseEquipmentCheckoutController::class, 'checkout'])->name('equipment.checkout');
        Route::patch('equipment/{phaseEquipment}/checkin', [PhaseEquipmentCheckoutController::class, 'checkin'])->name('equipment.checkin');
    });

    // 修理管理ルート
    Route::resource('repair-records', RepairRecordController::class);

    // 修理伝票PDF出力
    Route::get('repair-records/{repairRecord}/pdf', [RepairRecordController::class, 'exportPdf'])->name('repair-records.export-pdf');

    // 修理ワークフロー専用アクション
    Route::patch('repair-records/{repairRecord}/start', [RepairRecordController::class, 'start'])->name('repair-records.start');
    Route::patch('repair-records/{repairRecord}/complete', [RepairRecordController::class, 'complete'])->name('repair-records.complete');
    Route::patch('repair-records/{repairRecord}/cancel', [RepairRecordController::class, 'cancel'])->name('repair-records.cancel');

    // 代替機機能
    Route::patch('repair-records/{repairRecord}/substitute-equipment', [RepairRecordController::class, 'substituteEquipment'])->name('repair-records.substitute-equipment');
    Route::patch('repair-records/{repairRecord}/cancel-future-reservations', [RepairRecordController::class, 'cancelFutureReservations'])->name('repair-records.cancel-future-reservations');

    // 修理統計API
    Route::get('repair-stats', [RepairRecordController::class, 'stats'])->name('repair-records.stats');

    // 機材将来予約チェックAPI
    Route::get('api/equipment/{equipment}/future-reservations', [\App\Http\Controllers\Master\EquipmentController::class, 'getFutureReservations'])->name('api.equipment.future-reservations');

    // 機材フィルタリングAPI
    Route::get('api/equipments/by-subcategory', [\App\Http\Controllers\Master\EquipmentController::class, 'getEquipmentsBySubcategory'])->name('api.equipments.by-subcategory');

    // サブカテゴリ取得API
    Route::get('api/subcategories/by-category', [\App\Http\Controllers\Master\EquipmentSubcategoryController::class, 'getSubcategoriesByCategory'])->name('api.subcategories.by-category');

    // スケジュール表ルート
    Route::get('schedule', function () {
        return view('schedule.index');
    })->name('schedule.index');

    Route::get('api/schedule/equipment', [\App\Http\Controllers\ScheduleController::class, 'getEquipmentSchedule'])->name('api.schedule.equipment');
    Route::get('api/schedule/categories', [\App\Http\Controllers\ScheduleController::class, 'getCategories'])->name('api.schedule.categories');
    Route::get('api/schedule/subcategories', [\App\Http\Controllers\ScheduleController::class, 'getSubcategories'])->name('api.schedule.subcategories');
    Route::get('api/schedule/equipments', [\App\Http\Controllers\ScheduleController::class, 'getEquipments'])->name('api.schedule.equipments');
    Route::get('api/schedule/performances', [\App\Http\Controllers\ScheduleController::class, 'getPerformances'])->name('api.schedule.performances');

    // スケジュールセルメモAPI
    Route::get('api/schedule/cell-memos', [\App\Http\Controllers\Api\ScheduleCellMemoController::class, 'index'])->name('api.schedule.cell-memos.index');
    Route::post('api/schedule/cell-memos', [\App\Http\Controllers\Api\ScheduleCellMemoController::class, 'store'])->name('api.schedule.cell-memos.store');
    Route::delete('api/schedule/cell-memos', [\App\Http\Controllers\Api\ScheduleCellMemoController::class, 'destroy'])->name('api.schedule.cell-memos.destroy');

    // 在庫管理ルート
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('api/inventory', [InventoryController::class, 'getInventory'])->name('api.inventory');
        Route::get('api/warehouses', [InventoryController::class, 'getWarehouses'])->name('api.warehouses');
        Route::get('export-pdf', [InventoryController::class, 'exportPdf'])->name('export-pdf');
    });

    // 倉庫間移動専用画面
    Route::prefix('equipment-transfer')->name('equipment-transfer.')->group(function () {
        Route::get('/', [InventoryTransferController::class, 'transferIndex'])->name('index');
        Route::get('/return-select', function () {
            return view('equipment-transfer.return-select');
        })->name('return-select');
        Route::get('api/equipment', [InventoryTransferController::class, 'getTransferableEquipment'])->name('api.equipment');
        Route::get('api/equipment-for-return', [InventoryTransferController::class, 'getEquipmentForReturn'])->name('api.equipment-for-return');
        Route::get('api/categories', [InventoryTransferController::class, 'getTransferableCategories'])->name('api.categories');
        Route::post('api/transfer', [InventoryTransferController::class, 'transferEquipment'])->name('api.transfer');
        Route::post('api/bulk-transfer', [InventoryTransferController::class, 'bulkTransferEquipment'])->name('api.bulk-transfer');
        Route::post('api/bulk-return', [InventoryTransferController::class, 'bulkReturn'])->name('api.bulk-return');
        Route::post('api/return/{equipment}', [InventoryTransferController::class, 'returnEquipmentToBase'])->name('api.return');
        Route::get('api/phase/{phase}', [InventoryTransferController::class, 'getPhaseInfo'])->name('api.phase');
    });

    // 短縮形ルート（ダッシュボードから直接アクセス用）
    Route::get('positions', [PositionController::class, 'index'])->name('positions.index');
    Route::get('equipment-categories', [EquipmentCategoryController::class, 'index'])->name('equipment-categories.index');
    Route::get('equipment-subcategories', [EquipmentSubcategoryController::class, 'index'])->name('equipment-subcategories.index');
    Route::get('equipments', [EquipmentController::class, 'index'])->name('equipments.index');
    Route::get('equipment-sets', [EquipmentSetController::class, 'index'])->name('equipment-sets.index');
    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('productions', [ProductionController::class, 'index'])->name('productions.index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
});

// 未認証ユーザー向けのログインページ
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

// テスト用API（認証なし）- ローカル・テスト環境のみ
if (app()->environment('local', 'testing')) {
    Route::prefix('test-api')->group(function () {
    Route::get('schedule/simple', function () {
        return response()->json([
            'status' => 'ok',
            'equipment_count' => \App\Models\Equipment::count(),
            'phase_count' => \App\Models\Phase::count(),
            'phase_equipment_count' => \App\Models\PhaseEquipment::count(),
        ]);
    });

    // テスト用機材使用状況API（認証なし）
    Route::get('equipment/{equipment}/usage', [InventoryController::class, 'getEquipmentUsageTest']);

    // 超シンプルテスト（UUIDハッシュID対応）
    Route::get('simple-test/{id}', function ($id) {
        // IDの最初の8文字を使って番号生成
        $shortId = substr($id, 0, 8);
        $numericId = abs(crc32($shortId)) % 1000; // 0-999の数値に変換

        return response()->json([
            'success' => true,
            'message' => 'シンプルテスト成功',
            'data' => [
                'equipment' => [
                    'id' => $id,
                    'name' => 'テスト機材 #'.$numericId,
                    'company_number' => 'TEST'.str_pad($numericId, 3, '0', STR_PAD_LEFT),
                    'subcategory' => 'テストカテゴリ',
                    'location' => 'テスト倉庫',
                ],
                'usage_info' => [
                    [
                        'type' => 'available',
                        'status' => '利用可能',
                        'details' => 'テスト倉庫保管',
                        'company_number' => 'TEST'.str_pad($numericId, 3, '0', STR_PAD_LEFT),
                    ],
                ],
                'as_of_date' => now()->format('Y-m-d'),
            ],
        ]);
    });
});
} // end if (local/testing environment)
