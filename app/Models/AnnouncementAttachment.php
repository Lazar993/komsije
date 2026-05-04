<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['announcement_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class AnnouncementAttachment extends Model
{
    use HasFactory;

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deleteFile(): void
    {
        if ($this->path !== null && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
