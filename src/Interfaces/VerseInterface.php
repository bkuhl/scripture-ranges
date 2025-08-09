<?php

declare(strict_types=1);

namespace BKuhl\BibleRanges\Interfaces;

interface VerseInterface
{
    public function number(): int;
    public function chapterNumber(): int;
    public function book(): BookInterface;
} 