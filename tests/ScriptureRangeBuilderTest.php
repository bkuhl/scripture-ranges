<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Tests;

use BKuhl\ScriptureRanges\Interfaces\BookInterface;
use BKuhl\ScriptureRanges\Interfaces\BookResolverInterface;
use BKuhl\ScriptureRanges\RangeCollection;
use BKuhl\ScriptureRanges\ScriptureRangeBuilder;
use BKuhl\ScriptureRanges\Tests\Mocks\MockBook;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TestBookResolver implements BookResolverInterface
{
    public function resolve(mixed $book): BookInterface
    {
        if (is_string($book)) {
            return match (strtolower($book)) {
                'john' => MockBook::john(),
                'matthew' => MockBook::matthew(),
                'luke' => MockBook::luke(),
                default => throw new InvalidArgumentException("Unknown book: {$book}")
            };
        }
        
        if (is_int($book)) {
            return match ($book) {
                43 => MockBook::john(),
                40 => MockBook::matthew(),
                42 => MockBook::luke(),
                default => throw new InvalidArgumentException("Unknown book position: {$book}")
            };
        }
        
        throw new InvalidArgumentException('Unable to resolve book');
    }

    public function canResolve(mixed $book): bool
    {
        if ($book instanceof BookInterface) {
            return false;
        }

        if (is_string($book)) {
            return in_array(strtolower($book), ['john', 'matthew', 'luke']);
        }

        if (is_int($book)) {
            return in_array($book, [43, 40, 42]);
        }

        return false;
    }
}

class ScriptureRangeBuilderTest extends TestCase
{
    private function getBuilder(): ScriptureRangeBuilder
    {
        return new ScriptureRangeBuilder([new TestBookResolver()]);
    }

    public function testWithBookInterface(): void
    {
        $book = MockBook::john();
        $builder = new ScriptureRangeBuilder();
        
        $collection = $builder->with($book, chapter: 3, verse: 16, toVerse: 17)->build();
        
        $this->assertInstanceOf(RangeCollection::class, $collection);
        $ranges = $collection->getRanges();
        $this->assertCount(1, $ranges);
        
        $range = $ranges[0];
        $this->assertEquals('John', $range->book()->name());
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(16, $range->startVerse());
        $this->assertEquals(3, $range->endChapter());
        $this->assertEquals(17, $range->endVerse());
    }

    public function testWithStringBook(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder->with('john', chapter: 3, verse: 16)->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $this->assertEquals('John', $range->book()->name());
        $this->assertEquals(16, $range->startVerse());
        $this->assertEquals(36, $range->endVerse()); // Last verse of John 3
    }

    public function testMultipleRanges(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder
            ->with('john', chapter: 3, verse: 16, toVerse: 17)
            ->with('matthew', chapter: 5, verse: 1, toVerse: 12)
            ->with(42, chapter: 2, verse: 8, toVerse: 20) // Luke by position
            ->build();
        
        $ranges = $collection->getRanges();
        $this->assertCount(3, $ranges);
        
        $this->assertEquals('John', $ranges[0]->book()->name());
        $this->assertEquals('Matthew', $ranges[1]->book()->name());
        $this->assertEquals('Luke', $ranges[2]->book()->name());
    }

    public function testWithoutRange(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder
            ->with('john', chapter: 3, verse: 1, toVerse: 36)
            ->without('john', chapter: 3, verse: 16, toVerse: 17)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $exclusions = $range->exclusions();
        
        $this->assertCount(1, $exclusions);
        $this->assertEquals(3, $exclusions[0]['startChapter']);
        $this->assertEquals(16, $exclusions[0]['startVerse']);
        $this->assertEquals(17, $exclusions[0]['endVerse']);
    }

    public function testWithoutVerse(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder
            ->with('john', chapter: 3, verse: 1, toVerse: 36)
            ->without('john', chapter: 3, verse: 22)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $exclusions = $range->exclusions();
        
        $this->assertCount(1, $exclusions);
        $this->assertEquals(22, $exclusions[0]['startVerse']);
        $this->assertEquals(22, $exclusions[0]['endVerse']);
    }

    public function testWithoutThrowsExceptionWithoutActiveRange(): void
    {
        $builder = $this->getBuilder();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add exclusion without an active range. Call with() first.');
        
        $builder->without('john', chapter: 3, verse: 16);
    }

    public function testWithoutThrowsExceptionForDifferentBook(): void
    {
        $builder = $this->getBuilder();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exclusion must be in the same book as the current range');
        
        $builder
            ->with('john', chapter: 3, verse: 1, toVerse: 36)
            ->without('matthew', chapter: 5, verse: 1);
    }

    public function testWithDefaults(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder->with('john', chapter: 3)->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $this->assertEquals(1, $range->startVerse());      // Default fromVerse
        $this->assertEquals(36, $range->endVerse());       // Default toVerse (end of chapter)
    }

    public function testWithSingleVerse(): void
    {
        $builder = $this->getBuilder();
        
        $collection = $builder->with('john', chapter: 3, verse: 16)->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $this->assertEquals(16, $range->startVerse());
        $this->assertEquals(36, $range->endVerse());       // Default toVerse (end of chapter)
    }

    public function testWithVerseInterface(): void
    {
        $builder = $this->getBuilder();
        $verse = new \BKuhl\ScriptureRanges\Tests\Mocks\MockVerse(16, 3, MockBook::john());
        
        $collection = $builder->with('john', chapter: 3, verse: $verse, toVerse: 20)->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $this->assertEquals(16, $range->startVerse());
        $this->assertEquals(20, $range->endVerse());
    }

    public function testWithoutVerseInterface(): void
    {
        $builder = $this->getBuilder();
        $verse = new \BKuhl\ScriptureRanges\Tests\Mocks\MockVerse(22, 3, MockBook::john());
        
        $collection = $builder
            ->with('john', chapter: 3, verse: 1, toVerse: 36)
            ->without('john', chapter: 3, verse: $verse)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        $exclusions = $range->exclusions();
        
        $this->assertCount(1, $exclusions);
        $this->assertEquals(22, $exclusions[0]['startVerse']);
    }

    public function testResolverMethods(): void
    {
        $builder = new ScriptureRangeBuilder();
        $resolver = new TestBookResolver();
        
        $collection = $builder
            ->addResolver($resolver)
            ->with('john', chapter: 3, verse: 16)
            ->build();
            
        $this->assertCount(1, $collection->getRanges());
    }

    public function testWithResolversMethod(): void
    {
        $builder = new ScriptureRangeBuilder();
        $resolver = new TestBookResolver();
        
        $collection = $builder
            ->withResolvers([$resolver])
            ->with('john', chapter: 3, verse: 16)
            ->build();
            
        $this->assertCount(1, $collection->getRanges());
    }

    public function testInvalidVerseType(): void
    {
        $builder = $this->getBuilder();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve verse: array. Expected int or VerseInterface.');
        
        $builder->with('john', chapter: 3, verse: ['invalid']);
    }

    public function testThrowsExceptionWhenNoResolverCanHandle(): void
    {
        $builder = new ScriptureRangeBuilder();
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve book: string. No registered resolver can handle this type.');
        
        $builder->with('john', chapter: 3, verse: 16);
    }

    public function testWithChapterRange(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 5);
        
        $collection = $builder
            ->with('john', $chapterRange)
            ->build();
        
        $this->assertInstanceOf(RangeCollection::class, $collection);
        $ranges = $collection->getRanges();
        $this->assertCount(1, $ranges);
        
        $range = $ranges[0];
        $this->assertEquals('John', $range->book()->name());
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(5, $range->endChapter());
        $this->assertEquals(1, $range->startVerse());
        $this->assertEquals(47, $range->endVerse()); // MockBook::john() chapter 5 has 47 verses
    }

    public function testWithChapterRangeAndTraditionalSyntax(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(1, 2);
        
        $collection = $builder
            ->with('john', $chapterRange)                               // Full chapters 1-2
            ->with('john', chapter: 3, verse: 16, toVerse: 17)         // Specific verses
            ->build();
        
        $this->assertInstanceOf(RangeCollection::class, $collection);
        $ranges = $collection->getRanges();
        $this->assertCount(2, $ranges);
        
        // First range: chapters 1-2
        $range1 = $ranges[0];
        $this->assertEquals(1, $range1->startChapter());
        $this->assertEquals(2, $range1->endChapter());
        $this->assertEquals(1, $range1->startVerse());
        $this->assertEquals(25, $range1->endVerse()); // MockBook::john() chapter 2 has 25 verses
        
        // Second range: chapter 3, verses 16-17
        $range2 = $ranges[1];
        $this->assertEquals(3, $range2->startChapter());
        $this->assertEquals(3, $range2->endChapter());
        $this->assertEquals(16, $range2->startVerse());
        $this->assertEquals(17, $range2->endVerse());
    }

    public function testWithoutChapterRange(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $mainRange = \BKuhl\ScriptureRanges\ChapterRange::range(1, 5);
        $exclusionRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 3);
        
        $collection = $builder
            ->with('john', $mainRange)
            ->without('john', $exclusionRange)
            ->build();
        
        $ranges = $collection->getRanges();
        $this->assertCount(1, $ranges);
        
        $range = $ranges[0];
        $this->assertEquals(1, $range->startChapter());
        $this->assertEquals(5, $range->endChapter());
        
        // Should have one exclusion for chapter 3
        $exclusions = $range->exclusions();
        $this->assertCount(1, $exclusions);
        $this->assertEquals(3, $exclusions[0]['startChapter']);
        $this->assertEquals(3, $exclusions[0]['endChapter']);
        $this->assertEquals(1, $exclusions[0]['startVerse']);
        $this->assertEquals(36, $exclusions[0]['endVerse']); // MockBook::john() chapter 3 has 36 verses
    }

    public function testChapterRangeSingleChapter(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 3);
        
        $collection = $builder
            ->with('john', $chapterRange)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(3, $range->endChapter());
        $this->assertEquals(1, $range->startVerse());
        $this->assertEquals(36, $range->endVerse()); // Full chapter 3
    }

    public function testChapterRangeWithVerseParameters(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 5);
        
        $collection = $builder
            ->with('john', $chapterRange, verse: 10, toVerse: 25)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(5, $range->endChapter());
        $this->assertEquals(10, $range->startVerse()); // verse parameter used
        $this->assertEquals(25, $range->endVerse());   // toVerse parameter used
    }

    public function testChapterRangeWithOnlyStartVerse(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 4);
        
        $collection = $builder
            ->with('john', $chapterRange, verse: 5)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(4, $range->endChapter());
        $this->assertEquals(5, $range->startVerse());  // verse parameter used
        $this->assertEquals(54, $range->endVerse());   // Default to end of last chapter (chapter 4 has 54 verses)
    }

    public function testChapterRangeWithOnlyEndVerse(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 3);
        
        $collection = $builder
            ->with('john', $chapterRange, toVerse: 20)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(3, $range->endChapter());
        $this->assertEquals(1, $range->startVerse());   // Default to 1
        $this->assertEquals(20, $range->endVerse());    // toVerse parameter used
    }

    public function testChapterRangeWithVerseParametersInExclusion(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $mainRange = \BKuhl\ScriptureRanges\ChapterRange::range(1, 5);
        $exclusionRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 3);
        
        $collection = $builder
            ->with('john', $mainRange)
            ->without('john', $exclusionRange, verse: 10, toVerse: 20)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Should have one exclusion with specific verse range
        $exclusions = $range->exclusions();
        $this->assertCount(1, $exclusions);
        $this->assertEquals(3, $exclusions[0]['startChapter']);
        $this->assertEquals(3, $exclusions[0]['endChapter']);
        $this->assertEquals(10, $exclusions[0]['startVerse']);  // verse parameter used
        $this->assertEquals(20, $exclusions[0]['endVerse']);    // toVerse parameter used
    }

    public function testChapterRangeVerseAppliesToFirstChapter(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(2, 4);
        
        $collection = $builder
            ->with('john', $chapterRange, verse: 15)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Verify the range spans chapters 2-4
        $this->assertEquals(2, $range->startChapter());
        $this->assertEquals(4, $range->endChapter());
        
        // Starting verse (15) should apply to first chapter (2)
        $this->assertEquals(15, $range->startVerse());
        
        // Should default to end of last chapter (4) - John chapter 4 has 54 verses
        $this->assertEquals(54, $range->endVerse());
    }

    public function testChapterRangeToVerseAppliesToLastChapter(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(2, 4);
        
        $collection = $builder
            ->with('john', $chapterRange, toVerse: 30)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Verify the range spans chapters 2-4
        $this->assertEquals(2, $range->startChapter());
        $this->assertEquals(4, $range->endChapter());
        
        // Should default to verse 1 of first chapter (2)
        $this->assertEquals(1, $range->startVerse());
        
        // Ending verse (30) should apply to last chapter (4)
        $this->assertEquals(30, $range->endVerse());
    }

    public function testChapterRangeBothVerseParametersApplyToRespectiveChapters(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(1, 3);
        
        $collection = $builder
            ->with('john', $chapterRange, verse: 10, toVerse: 25)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Verify the range spans chapters 1-3
        $this->assertEquals(1, $range->startChapter());
        $this->assertEquals(3, $range->endChapter());
        
        // Starting verse (10) applies to first chapter (1)
        $this->assertEquals(10, $range->startVerse());
        
        // Ending verse (25) applies to last chapter (3)
        $this->assertEquals(25, $range->endVerse());
    }

    public function testChapterRangeDefaultsWithNoVerseParameters(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(2, 5);
        
        $collection = $builder
            ->with('john', $chapterRange)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Verify the range spans chapters 2-5
        $this->assertEquals(2, $range->startChapter());
        $this->assertEquals(5, $range->endChapter());
        
        // Should default to verse 1 of first chapter (2)
        $this->assertEquals(1, $range->startVerse());
        
        // Should default to end of last chapter (5) - John chapter 5 has 47 verses
        $this->assertEquals(47, $range->endVerse());
    }

    public function testChapterRangeSingleChapterWithVerseParameters(): void
    {
        $builder = new ScriptureRangeBuilder([new TestBookResolver()]);
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(3, 3);
        
        $collection = $builder
            ->with('john', $chapterRange, verse: 16, toVerse: 17)
            ->build();
        
        $ranges = $collection->getRanges();
        $range = $ranges[0];
        
        // Single chapter range
        $this->assertEquals(3, $range->startChapter());
        $this->assertEquals(3, $range->endChapter());
        
        // Both verses apply to the same chapter (3)
        $this->assertEquals(16, $range->startVerse());
        $this->assertEquals(17, $range->endVerse());
    }

    public function testChapterRangeVersusTraditionalSyntaxBehaviorConsistency(): void
    {
        $builder1 = new ScriptureRangeBuilder([new TestBookResolver()]);
        $builder2 = new ScriptureRangeBuilder([new TestBookResolver()]);
        
        // ChapterRange syntax
        $chapterRange = \BKuhl\ScriptureRanges\ChapterRange::range(2, 4);
        $collection1 = $builder1
            ->with('john', $chapterRange, verse: 10, toVerse: 25)
            ->build();
        
        // Traditional syntax
        $collection2 = $builder2
            ->with('john', chapter: 2, chapterEnd: 4, verse: 10, toVerse: 25)
            ->build();
        
        $range1 = $collection1->getRanges()[0];
        $range2 = $collection2->getRanges()[0];
        
        // Both should produce identical results
        $this->assertEquals($range1->startChapter(), $range2->startChapter());
        $this->assertEquals($range1->endChapter(), $range2->endChapter());
        $this->assertEquals($range1->startVerse(), $range2->startVerse());
        $this->assertEquals($range1->endVerse(), $range2->endVerse());
        $this->assertEquals($range1->reference(), $range2->reference());
    }
}