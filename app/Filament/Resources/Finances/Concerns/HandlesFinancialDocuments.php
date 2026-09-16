<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\Concerns;

use App\Models\FinancialDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Stores/removes private financial documents attached to income and expense
 * records and keeps them isolated on the `local` (non-public) disk.
 */
trait HandlesFinancialDocuments
{
    /**
     * @param  array<int, mixed>  $uploads
     */
    protected function storeFinancialDocuments(Model $record, array $uploads): void
    {
        $disk = 'local';
        $directory = "financial-documents/{$record->building_id}/".$record->getMorphClass()."/{$record->getKey()}";

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store($directory, $disk);

            if (! is_string($path) || $path === '') {
                continue;
            }

            $record->documents()->create([
                'building_id' => $record->building_id,
                'uploaded_by' => $this->financialActorId(),
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    protected function removeFinancialDocuments(Model $record, array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if ($ids === []) {
            return;
        }

        $documents = $record->documents()->whereKey($ids)->get();

        foreach ($documents as $document) {
            /** @var FinancialDocument $document */
            if (Storage::disk($document->disk)->exists($document->path)) {
                Storage::disk($document->disk)->delete($document->path);
            }

            $document->delete();
        }
    }

    private function financialActorId(): ?int
    {
        $user = $this->getFinancialActor();

        return $user?->getKey();
    }

    private function getFinancialActor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
