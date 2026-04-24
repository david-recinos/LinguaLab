<?php

declare(strict_types=1);

namespace App\Services;

class AnswerMatchingService
{
    /**
     * Check if the user's answer matches the expected answer.
     * Uses fuzzy matching with configurable tolerance for typos.
     * All comparisons are character-based (multibyte-safe) to correctly handle
     * languages with accented or non-ASCII characters (é, ñ, ü, ö, etc.).
     *
     * @param  float  $tolerance  Fraction of characters that may differ (0.0–1.0)
     */
    public function matches(string $userAnswer, string $expectedAnswer, float $tolerance = 0.1): bool
    {
        $normalizedUser     = $this->normalize($userAnswer);
        $normalizedExpected = $this->normalize($expectedAnswer);

        if ($normalizedUser === $normalizedExpected) {
            return true;
        }

        $distance      = $this->mbLevenshtein($normalizedUser, $normalizedExpected);
        $maxLength     = max(mb_strlen($normalizedUser, 'UTF-8'), mb_strlen($normalizedExpected, 'UTF-8'));
        $allowedErrors = max(1, (int) floor($maxLength * $tolerance));

        return $distance <= $allowedErrors;
    }

    /**
     * Normalize a string for comparison.
     * - NFC Unicode form, lowercase, trimmed, collapsed whitespace.
     * - Optionally strips diacritics (á → a, ñ → n, etc.).
     */
    public function normalize(string $text, bool $removeDiacritics = false): string
    {
        $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        $text = mb_strtolower($text, 'UTF-8');
        $text = trim($text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        if ($removeDiacritics) {
            $text = $this->removeDiacritics($text);
        }

        return $text;
    }

    /**
     * Get a similarity score between two strings (0.0–1.0).
     * Character-based to correctly handle multibyte input.
     */
    public function getSimilarityScore(string $userAnswer, string $expectedAnswer): float
    {
        $normalizedUser     = $this->normalize($userAnswer);
        $normalizedExpected = $this->normalize($expectedAnswer);

        if (empty($normalizedUser) || empty($normalizedExpected)) {
            return 0.0;
        }

        $distance  = $this->mbLevenshtein($normalizedUser, $normalizedExpected);
        $maxLength = max(mb_strlen($normalizedUser, 'UTF-8'), mb_strlen($normalizedExpected, 'UTF-8'));

        return 1.0 - ($distance / $maxLength);
    }

    /**
     * Get a detailed match result with explanation.
     */
    public function getMatchResult(string $userAnswer, string $expectedAnswer, float $tolerance = 0.1): array
    {
        return [
            'is_match'            => $this->matches($userAnswer, $expectedAnswer, $tolerance),
            'similarity_score'    => round($this->getSimilarityScore($userAnswer, $expectedAnswer), 2),
            'normalized_user'     => $this->normalize($userAnswer),
            'normalized_expected' => $this->normalize($expectedAnswer),
            'tolerance_used'      => $tolerance,
        ];
    }

    /**
     * Multibyte-safe Levenshtein distance.
     * PHP's built-in levenshtein() counts bytes, not characters, which gives wrong
     * results for multibyte characters. This implementation splits on grapheme
     * clusters and operates on character arrays.
     */
    private function mbLevenshtein(string $a, string $b): int
    {
        $aChars = mb_str_split($a, 1, 'UTF-8');
        $bChars = mb_str_split($b, 1, 'UTF-8');

        $aLen = count($aChars);
        $bLen = count($bChars);

        if ($aLen === 0) {
            return $bLen;
        }

        if ($bLen === 0) {
            return $aLen;
        }

        // Build the DP matrix row by row, keeping only the previous and current rows.
        $prev = range(0, $bLen);

        for ($i = 1; $i <= $aLen; $i++) {
            $curr    = array_fill(0, $bLen + 1, 0);
            $curr[0] = $i;

            for ($j = 1; $j <= $bLen; $j++) {
                $cost    = ($aChars[$i - 1] === $bChars[$j - 1]) ? 0 : 1;
                $curr[$j] = min(
                    $prev[$j] + 1,          // deletion
                    $curr[$j - 1] + 1,      // insertion
                    $prev[$j - 1] + $cost   // substitution
                );
            }

            $prev = $curr;
        }

        return $prev[$bLen];
    }

    private function removeDiacritics(string $text): string
    {
        $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);

        return $transliterated !== false ? $transliterated : $text;
    }
}
