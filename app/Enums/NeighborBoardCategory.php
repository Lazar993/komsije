<?php

declare(strict_types=1);

namespace App\Enums;

enum NeighborBoardCategory: string
{
    case Garage = 'garage';
    case ContractorRecommendation = 'contractor_recommendation';
    case NeighborHelp = 'neighbor_help';
    case LostFound = 'lost_found';
    case GiveAway = 'give_away';
    case ForSale = 'for_sale';
    case Wanted = 'wanted';
    case Question = 'question';

    public function label(): string
    {
        return match ($this) {
            self::Garage => '🚗 Garaža',
            self::ContractorRecommendation => '🔧 Preporuka majstora',
            self::NeighborHelp => '🤝 Pomoć komšiji',
            self::LostFound => '📦 Izgubljeno / pronađeno',
            self::GiveAway => '🎁 Poklanjam',
            self::ForSale => '💰 Prodajem',
            self::Wanted => '🛒 Kupujem',
            self::Question => '❓ Pitanje',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $category) {
            $options[$category->value] = $category->label();
        }

        return $options;
    }
}
