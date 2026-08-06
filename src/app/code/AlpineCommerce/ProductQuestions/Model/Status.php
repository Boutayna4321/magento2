<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

class Status
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public static function toOptionArray(): array
    {
        return [
            ["value" => self::STATUS_PENDING, "label" => __("Pending")],
            ["value" => self::STATUS_APPROVED, "label" => __("Approved")],
            ["value" => self::STATUS_REJECTED, "label" => __("Rejected")],
        ];
    }

    public static function getLabel(int $status): string
    {
        return (string) match ($status) {
            self::STATUS_APPROVED => __("Approved"),
            self::STATUS_REJECTED => __("Rejected"),
            default => __("Pending"),
        };
    }
}
