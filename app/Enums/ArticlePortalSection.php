<?php

namespace App\Enums;

enum ArticlePortalSection: string
{
    case Articles = 'articles';
    case HistoryCuriosities = 'history-curiosities';
    case Techniques = 'techniques';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $section): string => $section->value,
            self::cases()
        );
    }

    public function label(): string
    {
        return __("article_sections.placements.{$this->value}");
    }

    public function title(): string
    {
        return __("article_sections.sections.{$this->value}.title");
    }

    public function kicker(): string
    {
        return __("article_sections.sections.{$this->value}.kicker");
    }

    public function description(): string
    {
        return __("article_sections.sections.{$this->value}.description");
    }
}
