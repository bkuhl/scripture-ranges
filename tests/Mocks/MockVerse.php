<?php

declare(strict_types=1);

namespace BKuhl\BibleRanges\Tests\Mocks;

use BKuhl\BibleRanges\Interfaces\BookInterface;
use BKuhl\BibleRanges\Interfaces\VerseInterface;

class MockVerse implements VerseInterface
{
    public function __construct(
        private readonly int $number,
        private readonly int $chapter,
        private readonly BookInterface $book
    ) {
    }

    public function number(): int
    {
        return $this->number;
    }

    public function chapterNumber(): int
    {
        return $this->chapter;
    }

    public function book(): BookInterface
    {
        return $this->book;
    }

    // Factory methods for creating mock verses
    public static function genesis(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::genesis());
    }

    public static function exodus(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::exodus());
    }

    public static function luke(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::luke());
    }

    public static function john(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::john());
    }

    public static function samuel(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::samuel());
    }

    public static function psalms(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::psalms());
    }

    public static function matthew(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::matthew());
    }

    public static function mark(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::mark());
    }

    public static function acts(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::acts());
    }

    public static function romans(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::romans());
    }

    public static function corinthians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::corinthians());
    }

    public static function galatians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::galatians());
    }

    public static function ephesians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::ephesians());
    }

    public static function philippians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::philippians());
    }

    public static function colossians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::colossians());
    }

    public static function thessalonians(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::thessalonians());
    }

    public static function timothy(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::timothy());
    }

    public static function titus(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::titus());
    }

    public static function philemon(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::philemon());
    }

    public static function hebrews(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::hebrews());
    }

    public static function james(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::james());
    }

    public static function peter(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::peter());
    }

    public static function johnEpistle(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::johnEpistle());
    }

    public static function jude(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::jude());
    }

    public static function revelation(int $verse, int $chapter = 1): self
    {
        return new self($verse, $chapter, MockBook::revelation());
    }

    // Convenience methods for common verses
    public static function genesis1_1(): self
    {
        return self::genesis(1, 1);
    }

    public static function john3_16(): self
    {
        return self::john(16, 3);
    }

    public static function psalms23_1(): self
    {
        return self::psalms(1, 23);
    }

    public static function matthew5_3(): self
    {
        return self::matthew(3, 5);
    }

    public static function luke2_10(): self
    {
        return self::luke(10, 2);
    }
} 