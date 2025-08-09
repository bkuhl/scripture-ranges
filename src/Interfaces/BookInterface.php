<?php

declare(strict_types=1);

namespace BKuhl\BibleRanges\Interfaces;

interface BookInterface
{
    public function name(): string;
    public function position(): int;
    public function chapterVerseCount(int $chapter): int;
} 