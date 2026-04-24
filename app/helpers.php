<?php

declare(strict_types=1);

if (! function_exists('mastery_color_class')) {
    /**
     * Return the Tailwind CSS colour classes for a given mastery level.
     * Levels: new, learning, recognized, known, mastered.
     */
    function mastery_color_class(string $masteryLevel): string
    {
        return match ($masteryLevel) {
            'new'        => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'learning'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'recognized' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'known'      => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
            'mastered'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            default      => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}
