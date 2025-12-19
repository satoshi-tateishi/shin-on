<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyLogo extends Model
{
    protected $fillable = [
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public static function getActiveLogo(): ?self
    {
        return self::where('is_active', true)->first();
    }

    public function delete(): bool
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }

        return parent::delete();
    }
}
