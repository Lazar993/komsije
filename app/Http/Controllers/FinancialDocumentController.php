<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FinancialDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams private financial documents. Access is limited to managers of the
 * document's building (super admins bypass via Gate::before). Documents live on
 * the non-public `local` disk and are never exposed through a public URL.
 */
final class FinancialDocumentController extends Controller
{
    public function download(Request $request, FinancialDocument $document): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless($user->isBuildingAdmin((int) $document->building_id), 403);

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        $downloadName = $this->downloadNameFor($document);

        $contentDisposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $downloadName,
            $this->asciiFallbackFilenameFor($downloadName),
        );

        return response()->stream(function () use ($disk, $document): void {
            $stream = $disk->readStream($document->path);

            if ($stream === null) {
                return;
            }

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
            'Content-Length' => (string) ($document->size ?: $disk->size($document->path)),
            'Content-Disposition' => $contentDisposition,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function downloadNameFor(FinancialDocument $document): string
    {
        $name = trim(str_replace(['/', '\\'], '-', (string) $document->original_name));

        return $name !== '' ? $name : 'document';
    }

    private function asciiFallbackFilenameFor(string $name): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'document';
        $ascii = str_replace('%', '_', $ascii);

        return $ascii !== '' ? $ascii : 'document';
    }
}
