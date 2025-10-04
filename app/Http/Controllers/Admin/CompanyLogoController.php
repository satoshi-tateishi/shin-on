<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyLogo;
use App\Models\CompanyInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyLogoController extends Controller
{
    public function index()
    {
        $logo = CompanyLogo::getActiveLogo();
        $companyInfo = CompanyInfo::getActiveCompanyInfo();
        return view('admin.company-logo.index', compact('logo', 'companyInfo'));
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

            return redirect()->route('admin.company-logo.index')
                           ->with('success', '会社ロゴをアップロードしました。');
        }

        return redirect()->route('admin.company-logo.index')
                       ->with('error', 'ファイルのアップロードに失敗しました。');
    }

    public function destroy()
    {
        $logo = CompanyLogo::getActiveLogo();

        if ($logo) {
            $logo->delete();
            return redirect()->route('admin.company-logo.index')
                           ->with('success', '会社ロゴを削除しました。');
        }

        return redirect()->route('admin.company-logo.index')
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

        if ($companyInfo) {
            // 既存のレコードを更新
            $companyInfo->update($validated);
            $message = '会社情報を更新しました。';
        } else {
            // 新規作成
            $validated['is_active'] = true;
            CompanyInfo::create($validated);
            $message = '会社情報を登録しました。';
        }

        return redirect()->route('admin.company-logo.index')
            ->with('success', $message);
    }
}
