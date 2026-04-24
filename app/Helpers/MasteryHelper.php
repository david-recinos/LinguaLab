<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * @deprecated Use the mastery_color_class() global helper function directly.
 */
class MasteryHelper
{
    public static function getColorClass(string $masteryLevel): string
    {
        return mastery_color_class($masteryLevel);
    }
}
