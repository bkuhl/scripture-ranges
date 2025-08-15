<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Tests;

use BKuhl\ScriptureRanges\ChapterRange;
use PHPUnit\Framework\TestCase;

class ChapterRangeTest extends TestCase
{
    public function testConstructorWithValidRange(): void
    {
        $range = new ChapterRange(3, 5);
        
        $this->assertEquals(3, $range->getStart());
        $this->assertEquals(5, $range->getEnd());
    }

    public function testConstructorWithSameStartAndEnd(): void
    {
        $range = new ChapterRange(3, 3);
        
        $this->assertEquals(3, $range->getStart());
        $this->assertEquals(3, $range->getEnd());
    }

    public function testConstructorThrowsExceptionWhenStartGreaterThanEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start chapter cannot be greater than end chapter');
        
        new ChapterRange(5, 3);
    }

    public function testRangeFactoryMethod(): void
    {
        $range = ChapterRange::range(2, 7);
        
        $this->assertInstanceOf(ChapterRange::class, $range);
        $this->assertEquals(2, $range->getStart());
        $this->assertEquals(7, $range->getEnd());
    }

    public function testRangeFactoryMethodWithSameStartAndEnd(): void
    {
        $range = ChapterRange::range(4, 4);
        
        $this->assertEquals(4, $range->getStart());
        $this->assertEquals(4, $range->getEnd());
    }

    public function testRangeFactoryMethodThrowsExceptionWhenStartGreaterThanEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start chapter cannot be greater than end chapter');
        
        ChapterRange::range(8, 3);
    }

    public function testGetStart(): void
    {
        $range = new ChapterRange(1, 10);
        
        $this->assertEquals(1, $range->getStart());
    }

    public function testGetEnd(): void
    {
        $range = new ChapterRange(1, 10);
        
        $this->assertEquals(10, $range->getEnd());
    }

    public function testGettersWithSingleChapter(): void
    {
        $range = ChapterRange::range(5, 5);
        
        $this->assertEquals(5, $range->getStart());
        $this->assertEquals(5, $range->getEnd());
    }

    public function testPrivatePropertiesAccessibleViaGetters(): void
    {
        $range = new ChapterRange(2, 8);
        
        // Properties should be accessible via getters
        $this->assertEquals(2, $range->getStart());
        $this->assertEquals(8, $range->getEnd());
        
        // This test verifies the getter methods work correctly
        // The private readonly nature is enforced by PHP itself
    }

    public function testMultipleInstancesAreIndependent(): void
    {
        $range1 = ChapterRange::range(1, 3);
        $range2 = ChapterRange::range(5, 7);
        
        $this->assertEquals(1, $range1->getStart());
        $this->assertEquals(3, $range1->getEnd());
        
        $this->assertEquals(5, $range2->getStart());
        $this->assertEquals(7, $range2->getEnd());
        
        // Verify they don't affect each other
        $this->assertNotEquals($range1->getStart(), $range2->getStart());
        $this->assertNotEquals($range1->getEnd(), $range2->getEnd());
    }

    public function testChapterRangeWithMinimumValues(): void
    {
        $range = ChapterRange::range(1, 1);
        
        $this->assertEquals(1, $range->getStart());
        $this->assertEquals(1, $range->getEnd());
    }

    public function testChapterRangeWithLargeValues(): void
    {
        $range = ChapterRange::range(100, 150);
        
        $this->assertEquals(100, $range->getStart());
        $this->assertEquals(150, $range->getEnd());
    }
} 