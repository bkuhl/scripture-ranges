<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Tests;

use BKuhl\ScriptureRanges\RangeCollection;
use BKuhl\ScriptureRanges\ScriptureRange;
use BKuhl\ScriptureRanges\Tests\Mocks\MockBook;
use BKuhl\ScriptureRanges\Tests\Mocks\MockVerse;
use PHPUnit\Framework\TestCase;

class RangeCollectionTest extends TestCase
{
    private MockBook $genesisBook;
    private MockBook $lukeBook;
    private MockBook $samuelBook;

    protected function setUp(): void
    {
        $this->genesisBook = MockBook::genesis();
        $this->lukeBook = MockBook::luke();
        $this->samuelBook = MockBook::samuel();
    }

    public function testAddAndGetRanges(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);

        $collection->addRange($range1);
        $collection->addRange($range2);

        $ranges = $collection->getRanges();
        $this->assertCount(2, $ranges);
        $this->assertContains($range1, $ranges);
        $this->assertContains($range2, $ranges);
    }

    public function testRemoveRange(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);

        $collection->addRange($range1);
        $collection->addRange($range2);
        $this->assertCount(2, $collection->getRanges());

        $collection->removeRange($range1);
        $this->assertCount(1, $collection->getRanges());
        $this->assertContains($range2, $collection->getRanges());
    }

    public function testContainsVerse(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        
        $verse1 = MockVerse::genesis(1, 1);
        $verse2 = MockVerse::luke(7, 1);
        $verse3 = MockVerse::luke(20, 1); // Outside range
        
        $this->assertTrue($collection->contains($verse1));
        $this->assertTrue($collection->contains($verse2));
        $this->assertFalse($collection->contains($verse3));
    }

    public function testGetRangesContaining(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->genesisBook, 4, 6, 1, 20);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        
        $verse1 = MockVerse::genesis(1, 1);
        $verse2 = MockVerse::genesis(1, 5);
        
        $ranges1 = $collection->getRangesContaining($verse1);
        $ranges2 = $collection->getRangesContaining($verse2);
        
        $this->assertCount(1, $ranges1);
        $this->assertContains($range1, $ranges1);
        $this->assertCount(1, $ranges2);
        $this->assertContains($range2, $ranges2);
    }

    public function testToArray(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);

        $collection->addRange($range1);
        $collection->addRange($range2);

        $array = $collection->toArray();

        $this->assertCount(2, $array);
        $this->assertEquals(1, $array[0]['start']['book']); // Genesis ID is 1
        $this->assertEquals(42, $array[1]['start']['book']); // Luke ID is 42
    }

    public function testToJson(): void
    {
        $collection = new RangeCollection();
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);

        $collection->addRange($range1);
        $collection->addRange($range2);

        $json = $collection->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals(1, $decoded[0]['start']['book']); // Genesis ID
        $this->assertEquals(42, $decoded[1]['start']['book']); // Luke ID
    }

    public function testGetReference(): void
    {
        $collection = new RangeCollection();
        
        // Empty collection
        $this->assertEquals('', $collection->reference());
        
        // Single range
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $collection->addRange($range1);
        $reference = $collection->reference();
        $this->assertEquals('Genesis 1-3:15', $reference);
        
        // Multiple ranges in same book
        $range2 = new ScriptureRange($this->genesisBook, 5, 7, 1, 25);
        $collection->addRange($range2);
        $this->assertEquals('Genesis (1-3:15, 5-7:25)', $collection->reference());
        
        // Multiple ranges in different books
        $range3 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $collection->addRange($range3);
        $this->assertEquals('Genesis (1-3:15, 5-7:25), Luke 1:10', $collection->reference());
    }

    public function testGetReferenceEmptyCollection(): void
    {
        $collection = new RangeCollection();
        $this->assertEquals('', $collection->reference());
    }

    public function testGetReferenceWithConsecutiveChapters(): void
    {
        $collection = new RangeCollection();
        
        // Add consecutive chapters
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 31);
        $range2 = new ScriptureRange($this->genesisBook, 4, 6, 1, 25);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        
        $this->assertEquals('Genesis (1-3:31, 4-6:25)', $collection->reference());
    }

    public function testGetReferenceWithGaps(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges with gaps
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 31);
        $range2 = new ScriptureRange($this->genesisBook, 5, 7, 1, 25);
        $range3 = new ScriptureRange($this->genesisBook, 10, 12, 1, 20);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        $collection->addRange($range3);
        
        $this->assertEquals('Genesis (1-3:31, 5-7:25, 10-12)', $collection->reference());
    }

    public function testGetReferenceWithMixedRanges(): void
    {
        $collection = new RangeCollection();
        
        // Add mixed ranges (some full chapters, some partial)
        $range1 = new ScriptureRange($this->genesisBook, 1, 1, 1, 31);
        $range2 = new ScriptureRange($this->genesisBook, 2, 2, 5, 25);
        $range3 = new ScriptureRange($this->genesisBook, 3, 3, 1, 24);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        $collection->addRange($range3);
        
        $this->assertEquals('Genesis (1, 2:5-25, 3)', $collection->reference());
    }

    public function testGetReferenceWithMultipleBooks(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books
        $range1 = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $range2 = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        
        $this->assertEquals('Genesis 1-3:15, Luke 1:10', $collection->reference());
    }

    public function testGetReferenceWithSingleRange(): void
    {
        $collection = new RangeCollection();
        
        // Add single range
        $range = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $collection->addRange($range);
        
        $this->assertEquals('Genesis 1-3:15', $collection->reference());
    }

    public function testGetReferenceWithOverlappingChapters(): void
    {
        $collection = new RangeCollection();
        
        // Add overlapping chapters
        $range1 = new ScriptureRange($this->genesisBook, 1, 1, 1, 31);
        $range2 = new ScriptureRange($this->genesisBook, 1, 1, 5, 25);
        $range3 = new ScriptureRange($this->genesisBook, 2, 2, 1, 25);
        
        $collection->addRange($range1);
        $collection->addRange($range2);
        $collection->addRange($range3);
        
        $this->assertEquals('Genesis (1, 1:5-25, 2)', $collection->reference());
    }

    public function testGetReference1SamuelScenario(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges for 1 Samuel chapters 16-24 and 26-31
        for ($i = 16; $i <= 24; $i++) {
            $verseCount = $this->samuelBook->chapterVerseCount($i);
            $range = new ScriptureRange($this->samuelBook, $i, $i, 1, $verseCount);
            $collection->addRange($range);
        }
        
        for ($i = 26; $i <= 31; $i++) {
            $verseCount = $this->samuelBook->chapterVerseCount($i);
            $range = new ScriptureRange($this->samuelBook, $i, $i, 1, $verseCount);
            $collection->addRange($range);
        }
        
        $this->assertEquals('1 Samuel (16-24, 26-31)', $collection->reference());
    }

    // Multi-book tests from MultiBookRangeTest
    public function testRangeCollectionWithMultipleBooks(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $johnBook = MockBook::john();
        $johnRange = new ScriptureRange($johnBook, 1, 2, 1, 25);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        $collection->addRange($johnRange);
        
        $this->assertCount(3, $collection->getRanges());
        $this->assertEquals('Genesis 1-3:15, Luke 1:10, John 1-2', $collection->reference());
    }

    public function testVerseContainmentAcrossMultipleBooks(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $johnBook = MockBook::john();
        $johnRange = new ScriptureRange($johnBook, 1, 2, 1, 25);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        $collection->addRange($johnRange);
        
        // Test verses from different books
        $genesisVerse = MockVerse::genesis(5, 2);
        $lukeVerse = MockVerse::luke(5, 1);
        $johnVerse = MockVerse::john(10, 1);
        $exodusVerse = MockVerse::exodus(1, 1);
        
        $this->assertTrue($collection->contains($genesisVerse));
        $this->assertTrue($collection->contains($lukeVerse));
        $this->assertTrue($collection->contains($johnVerse));
        $this->assertFalse($collection->contains($exodusVerse));
    }

    public function testMultiBookRangeSerialization(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $johnBook = MockBook::john();
        $johnRange = new ScriptureRange($johnBook, 1, 2, 1, 25);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        $collection->addRange($johnRange);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertCount(3, $decoded);
        
        // Check that different book IDs are used
        $this->assertEquals(1, $decoded[0]['start']['book']); // Genesis ID
        $this->assertEquals(42, $decoded[1]['start']['book']); // Luke ID
        $this->assertEquals(43, $decoded[2]['start']['book']); // John ID
    }

    public function testGetRangesContainingVerseFromMultipleBooks(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $johnBook = MockBook::john();
        $johnRange = new ScriptureRange($johnBook, 1, 2, 1, 25);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        $collection->addRange($johnRange);
        
        // Test getting ranges containing specific verses
        $genesisVerse = MockVerse::genesis(5, 2);
        $lukeVerse = MockVerse::luke(5, 1);
        $johnVerse = MockVerse::john(10, 1);
        
        $genesisRanges = $collection->getRangesContaining($genesisVerse);
        $lukeRanges = $collection->getRangesContaining($lukeVerse);
        $johnRanges = $collection->getRangesContaining($johnVerse);
        
        $this->assertCount(1, $genesisRanges);
        $this->assertContains($genesisRange, $genesisRanges);
        
        $this->assertCount(1, $lukeRanges);
        $this->assertContains($lukeRange, $lukeRanges);
        
        $this->assertCount(1, $johnRanges);
        $this->assertContains($johnRange, $johnRanges);
    }

    public function testMultiBookRangeWithExclusions(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges with exclusions from different books
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 2, 1, 25);
        $genesisRange->addExclusion(1, 1, 5, 7);
        
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $lukeRange->addExclusion(1, 1, 3, 5);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        
        // Test verses with exclusions
        $this->assertFalse($collection->contains(MockVerse::genesis(5, 1))); // Excluded
        $this->assertTrue($collection->contains(MockVerse::genesis(4, 1))); // Not excluded
        $this->assertFalse($collection->contains(MockVerse::luke(3, 1))); // Excluded
        $this->assertTrue($collection->contains(MockVerse::luke(6, 1))); // Not excluded
    }

    public function testMultiBookRangeReferenceFormatting(): void
    {
        $collection = new RangeCollection();
        
        // Add ranges from different books with various formats
        $genesisRange = new ScriptureRange($this->genesisBook, 1, 3, 1, 15);
        $lukeRange = new ScriptureRange($this->lukeBook, 1, 1, 1, 10);
        $johnBook = MockBook::john();
        $johnRange = new ScriptureRange($johnBook, 1, 2, 1, 25);
        
        $collection->addRange($genesisRange);
        $collection->addRange($lukeRange);
        $collection->addRange($johnRange);
        
        $reference = $collection->reference();
        
        // Should group by book and format appropriately
        $this->assertStringContainsString('Genesis', $reference);
        $this->assertStringContainsString('Luke', $reference);
        $this->assertStringContainsString('John', $reference);
    }
} 