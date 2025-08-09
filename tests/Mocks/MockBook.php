<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Tests\Mocks;

use BKuhl\ScriptureRanges\Interfaces\BookInterface;

class MockBook implements BookInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $abbreviation,
        private readonly int $position,
        private readonly string $testament,
        private readonly int $chapterCount
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function chapterVerseCount(int $chapter): int
    {
        return static::getVerseCounts($this->name)[$chapter] ?? 30;
    }

    // Factory methods for creating mock books
    public static function genesis(): self
    {
        return new self('Genesis', 'Gen', 1, 'OLD', 50);
    }

    public static function exodus(): self
    {
        return new self('Exodus', 'Exod', 2, 'OLD', 40);
    }

    public static function luke(): self
    {
        return new self('Luke', 'Luke', 42, 'NEW', 24);
    }

    public static function john(): self
    {
        return new self('John', 'John', 43, 'NEW', 21);
    }

    public static function samuel(): self
    {
        return new self('1 Samuel', '1Sam', 9, 'OLD', 31);
    }

    public static function psalms(): self
    {
        return new self('Psalms', 'Ps', 19, 'OLD', 150);
    }

    public static function matthew(): self
    {
        return new self('Matthew', 'Matt', 40, 'NEW', 28);
    }

    public static function mark(): self
    {
        return new self('Mark', 'Mark', 41, 'NEW', 16);
    }

    public static function acts(): self
    {
        return new self('Acts', 'Acts', 44, 'NEW', 28);
    }

    public static function romans(): self
    {
        return new self('Romans', 'Rom', 45, 'NEW', 16);
    }

    public static function corinthians(): self
    {
        return new self('1 Corinthians', '1Cor', 46, 'NEW', 16);
    }

    public static function galatians(): self
    {
        return new self('Galatians', 'Gal', 48, 'NEW', 6);
    }

    public static function ephesians(): self
    {
        return new self('Ephesians', 'Eph', 49, 'NEW', 6);
    }

    public static function philippians(): self
    {
        return new self('Philippians', 'Phil', 50, 'NEW', 4);
    }

    public static function colossians(): self
    {
        return new self('Colossians', 'Col', 51, 'NEW', 4);
    }

    public static function thessalonians(): self
    {
        return new self('1 Thessalonians', '1Thess', 52, 'NEW', 5);
    }

    public static function timothy(): self
    {
        return new self('1 Timothy', '1Tim', 54, 'NEW', 6);
    }

    public static function titus(): self
    {
        return new self('Titus', 'Titus', 56, 'NEW', 3);
    }

    public static function philemon(): self
    {
        return new self('Philemon', 'Phlm', 57, 'NEW', 1);
    }

    public static function hebrews(): self
    {
        return new self('Hebrews', 'Heb', 58, 'NEW', 13);
    }

    public static function james(): self
    {
        return new self('James', 'James', 59, 'NEW', 5);
    }

    public static function peter(): self
    {
        return new self('1 Peter', '1Pet', 60, 'NEW', 5);
    }

    public static function johnEpistle(): self
    {
        return new self('1 John', '1John', 62, 'NEW', 5);
    }

    public static function jude(): self
    {
        return new self('Jude', 'Jude', 65, 'NEW', 1);
    }

    public static function revelation(): self
    {
        return new self('Revelation', 'Rev', 66, 'NEW', 22);
    }

    // Helper method to get verse counts for different books
    private static function getVerseCounts(string $bookName): array
    {
        $verseCounts = [
            'Genesis' => [
                1 => 31, 2 => 25, 3 => 24, 5 => 32, 7 => 24, 10 => 32, 12 => 20
            ],
            'Exodus' => [
                1 => 22, 2 => 25, 3 => 22, 4 => 31, 5 => 23, 6 => 30, 7 => 29, 8 => 28, 9 => 35, 10 => 29
            ],
            'Luke' => [
                1 => 80, 2 => 52, 3 => 38, 4 => 44, 5 => 39, 6 => 49, 7 => 50, 8 => 56, 9 => 62, 10 => 42,
                11 => 54, 12 => 59, 13 => 35, 14 => 35, 15 => 32, 16 => 31, 17 => 37, 18 => 43, 19 => 48,
                20 => 47, 21 => 38, 22 => 71, 23 => 56, 24 => 53
            ],
            'John' => [
                1 => 51, 2 => 25, 3 => 36, 4 => 54, 5 => 47, 6 => 71, 7 => 53, 8 => 59, 9 => 41, 10 => 42,
                11 => 57, 12 => 50, 13 => 38, 14 => 31, 15 => 27, 16 => 33, 17 => 26, 18 => 40, 19 => 42,
                20 => 31, 21 => 25
            ],
            '1 Samuel' => [
                16 => 23, 17 => 58, 18 => 30, 19 => 24, 20 => 42, 21 => 15, 22 => 23, 23 => 29, 24 => 22,
                25 => 44, 26 => 25, 27 => 12, 28 => 25, 29 => 11, 30 => 31, 31 => 13
            ],
            'Psalms' => [
                1 => 6, 2 => 12, 3 => 8, 4 => 8, 5 => 12, 6 => 10, 7 => 17, 8 => 9, 9 => 20, 10 => 18,
                11 => 7, 12 => 8, 13 => 6, 14 => 7, 15 => 5, 16 => 11, 17 => 15, 18 => 50, 19 => 14, 20 => 9
            ],
            'Matthew' => [
                1 => 25, 2 => 23, 3 => 17, 4 => 25, 5 => 48, 6 => 34, 7 => 29, 8 => 34, 9 => 38, 10 => 42,
                11 => 30, 12 => 50, 13 => 58, 14 => 36, 15 => 39, 16 => 28, 17 => 27, 18 => 35, 19 => 30,
                20 => 34, 21 => 46, 22 => 46, 23 => 39, 24 => 51, 25 => 46, 26 => 75, 27 => 66, 28 => 20
            ],
            'Mark' => [
                1 => 45, 2 => 28, 3 => 35, 4 => 41, 5 => 43, 6 => 56, 7 => 37, 8 => 38, 9 => 50, 10 => 52,
                11 => 33, 12 => 44, 13 => 37, 14 => 72, 15 => 47, 16 => 20
            ],
            'Acts' => [
                1 => 26, 2 => 47, 3 => 26, 4 => 37, 5 => 42, 6 => 15, 7 => 60, 8 => 40, 9 => 43, 10 => 48,
                11 => 30, 12 => 25, 13 => 52, 14 => 28, 15 => 41, 16 => 40, 17 => 34, 18 => 28, 19 => 41,
                20 => 38, 21 => 40, 22 => 30, 23 => 35, 24 => 27, 25 => 27, 26 => 32, 27 => 44, 28 => 31
            ]
        ];

        return $verseCounts[$bookName] ?? [];
    }
} 