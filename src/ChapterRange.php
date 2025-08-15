<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges;

class ChapterRange
{
    public function __construct(
        private readonly int $startChapter,
        private readonly int $endChapter
    ) {
        if ($startChapter > $endChapter) {
            throw new \InvalidArgumentException('Start chapter cannot be greater than end chapter');
        }
    }

    /**
     * Create a range spanning multiple chapters
     */
    public static function range(int $startChapter, int $endChapter): self
    {
        return new self($startChapter, $endChapter);
    }

    public function getStart(): int
    {
        return $this->startChapter;
    }

    public function getEnd(): int
    {
        return $this->endChapter;
    }
} 