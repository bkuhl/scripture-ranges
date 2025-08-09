<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Interfaces;

interface VerseInterface
{
    public function number(): int;
    public function chapterNumber(): int;
    public function book(): BookInterface;
} 