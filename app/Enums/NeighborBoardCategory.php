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
            self::Garage => '🚗 '.__('Garage'),
            self::ContractorRecommendation => '🔧 '.__('Contractor recommendation'),
            self::NeighborHelp => '🤝 '.__('Neighbor help'),
            self::LostFound => '📦 '.__('Lost and found'),
            self::GiveAway => '🎁 '.__('Give away'),
            self::ForSale => '💰 '.__('For sale'),
            self::Wanted => '🛒 '.__('Wanted'),
            self::Question => '❓ '.__('Question'),
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
