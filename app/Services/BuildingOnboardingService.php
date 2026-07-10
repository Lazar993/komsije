<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Building;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class BuildingOnboardingService
{
    public function ensureToken(Building $building): Building
    {
        if (filled($building->onboarding_token)) {
            return $building;
        }

        $building->forceFill([
            'onboarding_token' => Building::generateOnboardingToken(),
        ])->save();

        return $building->refresh();
    }

    public function regenerateToken(Building $building): Building
    {
        $building->forceFill([
            'onboarding_token' => Building::generateOnboardingToken(),
        ])->save();

        return $building->refresh();
    }

    public function findByToken(string $token): ?Building
    {
        return Building::query()
            ->where('onboarding_token', mb_strtolower(trim($token)))
            ->first();
    }

    public function joinUrl(Building $building): string
    {
        $building = $this->ensureToken($building);

        return route('join.show', ['token' => $building->onboarding_token]);
    }

    public function qrPng(Building $building, int $size = 1200): string
    {
        $url = $this->joinUrl($building);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(6, (int) floor($size / 45)),
            'imageTransparent' => false,
        ]);

        $output = (new QRCode($options))->render($url);

        if (str_starts_with($output, 'data:image/png;base64,')) {
            $encoded = substr($output, strlen('data:image/png;base64,'));
            $decoded = base64_decode($encoded, true);

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $output;
    }

    public function qrDataUri(Building $building, int $size = 900): string
    {
        $png = $this->qrPng($building, $size);

        if (str_starts_with($png, 'data:image/png;base64,')) {
            return $png;
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function pdf(Building $building): string
    {
        $pdf = Pdf::loadView('pdf.building-onboarding-qr', [
            'building' => $this->ensureToken($building),
            'joinUrl' => $this->joinUrl($building),
            'qrDataUri' => $this->qrDataUri($building, 1200),
        ])->setPaper('a4');

        return $pdf->output();
    }
}
