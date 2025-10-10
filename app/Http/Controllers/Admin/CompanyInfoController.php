<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyLogo;
use App\Models\CompanyInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyInfoController extends Controller
{
    public function index()
    {
        $logo = CompanyLogo::getActiveLogo();
        $companyInfo = CompanyInfo::getActiveCompanyInfo();
        return view('admin.company-info.index', compact('logo', 'companyInfo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('company-logos', $fileName, 'public');

            CompanyLogo::where('is_active', true)->update(['is_active' => false]);

            CompanyLogo::create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'is_active' => true,
            ]);

            return redirect()->route('admin.company-info.index')
                           ->with('success', '会社ロゴをアップロードしました。');
        }

        return redirect()->route('admin.company-info.index')
                       ->with('error', 'ファイルのアップロードに失敗しました。');
    }

    public function destroy()
    {
        $logo = CompanyLogo::getActiveLogo();

        if ($logo) {
            $logo->delete();
            return redirect()->route('admin.company-info.index')
                           ->with('success', '会社ロゴを削除しました。');
        }

        return redirect()->route('admin.company-info.index')
                       ->with('error', '削除するロゴが見つかりません。');
    }

    /**
     * 会社情報の保存・更新
     */
    public function storeCompanyInfo(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'repair_contact_person' => 'nullable|string|max:255',
            'repair_contact_email' => 'nullable|email|max:255',
        ]);

        $companyInfo = CompanyInfo::getActiveCompanyInfo();

        // どの情報が更新されたかを判定
        $isRepairInfoUpdate = $request->filled('repair_contact_person') || $request->filled('repair_contact_email');
        $isCompanyInfoUpdate = !$companyInfo ||
            ($companyInfo->company_name !== $validated['company_name']) ||
            ($companyInfo->postal_code !== ($validated['postal_code'] ?? null)) ||
            ($companyInfo->address !== ($validated['address'] ?? null)) ||
            ($companyInfo->phone !== ($validated['phone'] ?? null));

        if ($companyInfo) {
            // 既存のレコードを更新
            $companyInfo->update($validated);

            // メッセージの決定
            if ($isRepairInfoUpdate && !$isCompanyInfoUpdate) {
                $message = '修理担当者情報を更新しました。';
            } elseif (!$isRepairInfoUpdate && $isCompanyInfoUpdate) {
                $message = '会社情報を更新しました。';
            } else {
                $message = '会社情報を更新しました。';
            }
        } else {
            // 新規作成
            $validated['is_active'] = true;
            CompanyInfo::create($validated);
            $message = '会社情報を登録しました。';
        }

        return redirect()->route('admin.company-info.index')
            ->with('success', $message);
    }
}
