<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Interfaces;

interface BookResolverInterface
{
    public function resolve(mixed $book): BookInterface;
    public function canResolve(mixed $book): bool;
}