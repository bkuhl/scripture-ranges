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
        ChapterRange|int $chapter, 
        ?int $chapterEnd = null,
        mixed $verse = null, 
        mixed $toVerse = null
    ): self {
        $bookInterface = $this->resolveBook($book);
        
        // Determine chapter range
        if ($chapter instanceof ChapterRange) {
            $startChapter = $chapter->getStart();
            $endChapter = $chapter->getEnd();
        } else {
            $startChapter = $chapter;
            $endChapter = $chapterEnd ?? $chapter;
        }
        
        // Resolve verse parameters
        $fromVerse = $this->resolveVerse($verse) ?? 1;
        $resolvedToVerse = $this->resolveVerse($toVerse);
        
        // Determine ending verse based on context
        if ($resolvedToVerse !== null) {
            $toVerse = $resolvedToVerse;
        } else {
            // Default to end of the target chapter
            $targetChapter = ($chapter instanceof ChapterRange) ? $endChapter : $endChapter;
            $toVerse = $bookInterface->chapterVerseCount($targetChapter);
        }

        $this->currentRange = new ScriptureRange(
            $bookInterface,
            $startChapter,
            $endChapter,
            $fromVerse,
            $toVerse
        );

        $this->collection->addRange($this->currentRange);
        
        return $this;
    }

    public function without(
        mixed $book, 
        ChapterRange|int $chapter,
        ?int $chapterEnd = null,
        mixed $verse = null, 
        mixed $toVerse = null
    ): self {
        if ($this->currentRange === null) {
            throw new InvalidArgumentException('Cannot add exclusion without an active range. Call with() first.');
        }

        $bookInterface = $this->resolveBook($book);
        
        // Determine chapter range
        if ($chapter instanceof ChapterRange) {
            $startChapter = $chapter->getStart();
            $endChapter = $chapter->getEnd();
        } else {
            $startChapter = $chapter;
            $endChapter = $chapterEnd ?? $chapter;
        }
        
        // Resolve verse parameters
        $fromVerse = $this->resolveVerse($verse) ?? 1;
        $resolvedToVerse = $this->resolveVerse($toVerse);
        
        // Determine ending verse based on context
        if ($resolvedToVerse !== null) {
            $toVerse = $resolvedToVerse;
        } else {
            // For exclusions, default to end of range or single verse
            $targetChapter = ($chapter instanceof ChapterRange) ? $endChapter : $endChapter;
            $toVerse = ($chapter instanceof ChapterRange) 
                ? $bookInterface->chapterVerseCount($targetChapter)
                : $fromVerse; // For traditional syntax, default to single verse exclusion
        }

        // Verify exclusion is in same book as current range
        if ($bookInterface->name() !== $this->currentRange->book()->name()) {
            throw new InvalidArgumentException('Exclusion must be in the same book as the current range');
        }

        $this->currentRange->addExclusion($startChapter, $endChapter, $fromVerse, $toVerse);
        
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