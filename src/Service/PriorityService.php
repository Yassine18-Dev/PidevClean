<?php

namespace App\Service;

class PriorityService
{
    /**
     * Critical keywords that indicate severe urgency.
     */
    private const CRITICAL_KEYWORDS = [
        'hack', 'hacked', 'security', 'breach', 'exploit', 'ddos', 'attack',
        'data leak', 'compromised', 'vulnerability', 'ransomware',
    ];

    /**
     * High-priority keywords that indicate strong urgency.
     */
    private const HIGH_KEYWORDS = [
        'crash', 'down', 'broken', 'error', 'fail', 'not working', 'bug',
        'cannot login', 'locked out', 'payment failed', 'charge', 'refund',
        'urgent', 'emergency', 'asap', 'immediately', 'critical',
    ];

    /**
     * Medium-priority keywords.
     */
    private const MEDIUM_KEYWORDS = [
        'slow', 'lag', 'delay', 'issue', 'problem', 'trouble', 'glitch',
        'missing', 'wrong', 'incorrect', 'help', 'confused', 'stuck',
    ];

    /**
     * Category weight multipliers (higher = more urgent categories).
     */
    private const CATEGORY_WEIGHTS = [
        'Bug Report' => 1.5,
        'Technical' => 1.3,
        'Payment' => 1.4,
        'Account' => 1.2,
        'Tournament' => 1.1,
        'General' => 1.0,
    ];

    /**
     * Evaluate ticket priority based on subject, description, and category.
     * Returns: 'critical', 'high', 'medium', or 'low'.
     */
    public function evaluate(string $subject, string $description, string $categoryName = 'General'): string
    {
        $text = strtolower($subject . ' ' . $description);
        $score = 0.0;

        // — Keyword scoring —
        foreach (self::CRITICAL_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                $score += 40;
            }
        }
        foreach (self::HIGH_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                $score += 20;
            }
        }
        foreach (self::MEDIUM_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                $score += 8;
            }
        }

        // — Exclamation density bonus —
        $exclamations = substr_count($text, '!');
        $score += min($exclamations * 5, 25);

        // — Uppercase ratio bonus (shouting) —
        $original = $subject . ' ' . $description;
        $upperCount = preg_match_all('/[A-Z]/', $original);
        $totalChars = max(strlen($original), 1);
        $upperRatio = $upperCount / $totalChars;
        if ($upperRatio > 0.5) {
            $score += 15;
        }

        // — Description length bonus (more detail = more urgent intent) —
        $wordCount = str_word_count($description);
        if ($wordCount > 100) {
            $score += 10;
        }
        elseif ($wordCount > 50) {
            $score += 5;
        }

        // — Category weight multiplier —
        $weight = self::CATEGORY_WEIGHTS[$categoryName] ?? 1.0;
        $score *= $weight;

        // — Map score to priority level —
        return match (true) {
                $score >= 60 => 'critical',
                $score >= 30 => 'high',
                $score >= 12 => 'medium',
                default => 'low',
            };
    }

    /**
     * Returns a human-readable explanation of the priority decision.
     */
    public function explain(string $subject, string $description, string $categoryName = 'General'): array
    {
        $text = strtolower($subject . ' ' . $description);
        $reasons = [];

        foreach (self::CRITICAL_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                $reasons[] = "Critical keyword detected: \"{$kw}\"";
            }
        }
        foreach (self::HIGH_KEYWORDS as $kw) {
            if (str_contains($text, $kw)) {
                $reasons[] = "High-urgency keyword: \"{$kw}\"";
            }
        }

        $exclamations = substr_count($text, '!');
        if ($exclamations > 0) {
            $reasons[] = "{$exclamations} exclamation mark(s) detected";
        }

        $weight = self::CATEGORY_WEIGHTS[$categoryName] ?? 1.0;
        if ($weight > 1.0) {
            $reasons[] = "Category \"{$categoryName}\" has elevated weight ({$weight}x)";
        }

        if (empty($reasons)) {
            $reasons[] = 'No urgency indicators found — assigned low priority';
        }

        return $reasons;
    }
}