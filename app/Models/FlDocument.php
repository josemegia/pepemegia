<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FlDocument extends Model
{
    protected $table = 'fl_documents';

    protected $fillable = [
        'title', 'description', 'filename', 'path',
        'mime_type', 'size_bytes', 'category',
        'downloads', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getDownloadUrl(): string
    {
        return Storage::url($this->path);
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function getIconClass(): string
    {
        return match(true) {
            str_contains($this->mime_type, 'pdf') => 'fas fa-file-pdf text-red-400',
            str_contains($this->mime_type, 'word') || str_contains($this->filename, '.docx') => 'fas fa-file-word text-blue-400',
            str_contains($this->mime_type, 'excel') || str_contains($this->filename, '.xlsx') => 'fas fa-file-excel text-green-400',
            str_contains($this->mime_type, 'image') => 'fas fa-file-image text-purple-400',
            str_contains($this->mime_type, 'zip') => 'fas fa-file-archive text-yellow-400',
            default => 'fas fa-file text-gray-400',
        };
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads');
    }
}
