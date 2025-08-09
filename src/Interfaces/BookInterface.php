<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Interfaces;

interface BookInterface
{
    public function name(): string;
    public function position(): int;
    public function chapterVerseCount(int $chapter): int;
} 