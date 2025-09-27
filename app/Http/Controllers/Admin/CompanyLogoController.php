<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyLogoController extends Controller
{
    public function index()
    {
        $logo = CompanyLogo::getActiveLogo();
        return view('admin.company-logo.index', compact('logo'));
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
}
