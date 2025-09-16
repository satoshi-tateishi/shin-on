<?php

use App\Http\Controllers\Auth\LineWorksController;
use App\Http\Controllers\Master\EquipmentCategoryController;
use App\Http\Controllers\Master\EquipmentController;
use App\Http\Controllers\Master\EquipmentSetController;
use App\Http\Controllers\Master\EquipmentSubcategoryController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\PositionController;
use App\Http\Controllers\Master\ProductionController;
use App\Http\Controllers\Master\UserController;
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

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', ['companyLogo' => null]);
    })->name('dashboard');

    Route::post('/logout', [LineWorksController::class, 'logout'])->name('logout');

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

        // ユーザーマスタ（管理者のみ）
        Route::get('users/export-csv', [UserController::class, 'exportCsv'])->name('users.export-csv');
        Route::get('users/template-csv', [UserController::class, 'templateCsv'])->name('users.template-csv');
        Route::post('users/import-csv', [UserController::class, 'importCsv'])->name('users.import-csv');
        Route::post('users/update-sort', [UserController::class, 'updateSort'])->name('users.update-sort');
        Route::delete('users/{user}/remove-icon', [UserController::class, 'removeIcon'])->name('master.users.remove-icon');
        Route::resource('users', UserController::class);
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
