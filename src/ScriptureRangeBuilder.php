<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges;

use BKuhl\ScriptureRanges\Interfaces\BookInterface;
use BKuhl\ScriptureRanges\Interfaces\BookResolverInterface;
use BKuhl\ScriptureRanges\Interfaces\VerseInterface;
use InvalidArgumentException;

class ScriptureRangeBuilder
{
    private array $bookResolvers = [];
    private RangeCollection $collection;
    private ?ScriptureRange $currentRange = null;

    public function __construct(array $bookResolvers = [])
    {
        $this->bookResolvers = $bookResolvers;
        $this->collection = new RangeCollection();
    }

    public function withResolvers(array $resolvers): self
    {
        $this->bookResolvers = $resolvers;
        return $this;
    }

    public function addResolver(BookResolverInterface $resolver): self
    {
        $this->bookResolvers[] = $resolver;
        return $this;
    }

    public function with(
        mixed $book, 
        int $chapter, 
        mixed $verse = null, 
        mixed $toVerse = null
    ): self {
        $bookInterface = $this->resolveBook($book);
        $fromVerse = $this->resolveVerse($verse) ?? 1;
        $toVerse = $this->resolveVerse($toVerse) ?? $bookInterface->chapterVerseCount($chapter);

        $this->currentRange = new ScriptureRange(
            $bookInterface,
            $chapter,
            $chapter,
            $fromVerse,
            $toVerse
        );

        $this->collection->addRange($this->currentRange);
        
        return $this;
    }

    public function without(
        mixed $book, 
        int $chapter, 
        mixed $verse = null, 
        mixed $toVerse = null
    ): self {
        if ($this->currentRange === null) {
            throw new InvalidArgumentException('Cannot add exclusion without an active range. Call with() first.');
        }

        $bookInterface = $this->resolveBook($book);
        
        $fromVerse = $this->resolveVerse($verse) ?? 1;
        $toVerse = $this->resolveVerse($toVerse) ?? $fromVerse;

        // Verify exclusion is in same book as current range
        if ($bookInterface->name() !== $this->currentRange->book()->name()) {
            throw new InvalidArgumentException('Exclusion must be in the same book as the current range');
        }

        $this->currentRange->addExclusion($chapter, $chapter, $fromVerse, $toVerse);
        
        return $this;
    }

    public function build(): RangeCollection
    {
        return $this->collection;
    }

    private function resolveBook(mixed $book): BookInterface
    {
        if ($book instanceof BookInterface) {
            return $book;
        }

        foreach ($this->bookResolvers as $resolver) {
            if ($resolver->canResolve($book)) {
                return $resolver->resolve($book);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Unable to resolve book: %s. No registered resolver can handle this type.',
            is_object($book) ? get_class($book) : gettype($book)
        ));
    }

    private function resolveVerse(mixed $verse): ?int
    {
        if ($verse === null) {
            return null;
        }

        if (is_int($verse)) {
            return $verse;
        }

        if ($verse instanceof VerseInterface) {
            return $verse->number();
        }

        throw new InvalidArgumentException(sprintf(
            'Unable to resolve verse: %s. Expected int or VerseInterface.',
            is_object($verse) ? get_class($verse) : gettype($verse)
        ));
    }
}