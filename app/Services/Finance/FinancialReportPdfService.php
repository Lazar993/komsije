<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Building;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders the financial report as an A4 PDF. Kept separate from the Filament UI
 * so the same report can later be reused for scheduled or emailed reports.
 */
final class FinancialReportPdfService
{
    public function __construct(private readonly FinancialReportBuilder $builder) {}

    public function pdf(Building $building, Carbon $from, Carbon $until): string
    {
        $data = $this->builder->build($building, $from, $until);

        return Pdf::loadView('pdf.financial-report', $data)
            ->setPaper('a4')
            ->output();
    }

    public function filename(Building $building, Carbon $from, Carbon $until): string
    {
        $slug = Str::slug((string) $building->name);

        if ($slug === '') {
            $slug = 'zgrada-'.$building->getKey();
        }

        return sprintf(
            'finansijski-izvestaj-%s-%s-%s.pdf',
            $slug,
            $from->format('Y-m-d'),
            $until->format('Y-m-d'),
        );
    }

    public function download(Building $building, Carbon $from, Carbon $until): StreamedResponse
    {
        $content = $this->pdf($building, $from, $until);
        $filename = $this->filename($building, $from, $until);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
