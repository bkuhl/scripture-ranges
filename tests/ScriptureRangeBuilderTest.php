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
}