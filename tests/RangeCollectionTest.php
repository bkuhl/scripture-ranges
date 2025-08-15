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

        $this->assertArrayHasKey('ranges', $array);
        $this->assertCount(2, $array['ranges']);
        $this->assertEquals(1, $array['ranges'][0]['start']['book']); // Genesis ID is 1
        $this->assertEquals(42, $array['ranges'][1]['start']['book']); // Luke ID is 42
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
        $this->assertArrayHasKey('ranges', $decoded);
        $this->assertCount(2, $decoded['ranges']);
        $this->assertEquals(1, $decoded['ranges'][0]['start']['book']); // Genesis ID
        $this->assertEquals(42, $decoded['ranges'][1]['start']['book']); // Luke ID
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
        $this->assertArrayHasKey('ranges', $decoded);
        $this->assertCount(3, $decoded['ranges']);
        
        // Check that different book IDs are used
        $this->assertEquals(1, $decoded['ranges'][0]['start']['book']); // Genesis ID
        $this->assertEquals(42, $decoded['ranges'][1]['start']['book']); // Luke ID
        $this->assertEquals(43, $decoded['ranges'][2]['start']['book']); // John ID
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

    public function testConstructorWithNameAndId(): void
    {
        $collection = new RangeCollection('collection-123', 'My Collection');
        
        $this->assertEquals('My Collection', $collection->getName());
        $this->assertEquals('collection-123', $collection->getId());
    }

    public function testConstructorWithoutNameAndId(): void
    {
        $collection = new RangeCollection();
        
        $this->assertNull($collection->getName());
        $this->assertNull($collection->getId());
    }

    public function testSettersAndGetters(): void
    {
        $collection = new RangeCollection();
        
        $collection->setName('Test Collection');
        $collection->setId('test-id-456');
        
        $this->assertEquals('Test Collection', $collection->getName());
        $this->assertEquals('test-id-456', $collection->getId());
        
        // Test setting to null
        $collection->setName(null);
        $collection->setId(null);
        
        $this->assertNull($collection->getName());
        $this->assertNull($collection->getId());
    }

    public function testToArrayIncludesNameAndId(): void
    {
        $collection = new RangeCollection('plan-789', 'Reading Plan');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 10);
        $collection->addRange($range);
        
        $array = $collection->toArray();
        
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('ranges', $array);
        
        $this->assertEquals('Reading Plan', $array['name']);
        $this->assertEquals('plan-789', $array['id']);
        $this->assertIsArray($array['ranges']);
        $this->assertCount(1, $array['ranges']);
    }

    public function testToArrayWithoutNameAndId(): void
    {
        $collection = new RangeCollection();
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 10);
        $collection->addRange($range);
        
        $array = $collection->toArray();
        
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayHasKey('ranges', $array);
        $this->assertIsArray($array['ranges']);
        $this->assertCount(1, $array['ranges']);
    }

    public function testToJsonIncludesNameAndId(): void
    {
        $collection = new RangeCollection('daily-123', 'Daily Reading');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        $this->assertArrayHasKey('name', $decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertArrayHasKey('ranges', $decoded);
        
        $this->assertEquals('Daily Reading', $decoded['name']);
        $this->assertEquals('daily-123', $decoded['id']);
    }

    public function testToJsonWithPartialMetadata(): void
    {
        // Test with only name
        $collection1 = new RangeCollection(null, 'Only Name');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection1->addRange($range);
        
        $json1 = $collection1->toJson();
        $decoded1 = json_decode($json1, true);
        
        $this->assertArrayHasKey('name', $decoded1);
        $this->assertArrayNotHasKey('id', $decoded1);
        $this->assertEquals('Only Name', $decoded1['name']);
        
        // Test with only ID
        $collection2 = new RangeCollection('only-id');
        $collection2->addRange($range);
        
        $json2 = $collection2->toJson();
        $decoded2 = json_decode($json2, true);
        
        $this->assertArrayNotHasKey('name', $decoded2);
        $this->assertArrayHasKey('id', $decoded2);
        $this->assertEquals('only-id', $decoded2['id']);
    }

    public function testJsonOmitsNullValues(): void
    {
        // Test completely null values
        $collection = new RangeCollection();
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        // Ensure keys are completely absent, not just null
        $this->assertArrayNotHasKey('name', $decoded);
        $this->assertArrayNotHasKey('id', $decoded);
        $this->assertArrayHasKey('ranges', $decoded);
        
        // Test that JSON string doesn't contain name or id keys at all
        $this->assertStringNotContainsString('"name"', $json);
        $this->assertStringNotContainsString('"id"', $json);
    }

    public function testJsonOmitsNullNameButIncludesId(): void
    {
        $collection = new RangeCollection('test-id-123');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        // Name should be completely absent
        $this->assertArrayNotHasKey('name', $decoded);
        $this->assertStringNotContainsString('"name"', $json);
        
        // ID should be present
        $this->assertArrayHasKey('id', $decoded);
        $this->assertEquals('test-id-123', $decoded['id']);
        $this->assertStringContainsString('"id"', $json);
    }

    public function testJsonOmitsNullIdButIncludesName(): void
    {
        $collection = new RangeCollection(null, 'Test Collection');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        // ID should be completely absent
        $this->assertArrayNotHasKey('id', $decoded);
        $this->assertStringNotContainsString('"id"', $json);
        
        // Name should be present
        $this->assertArrayHasKey('name', $decoded);
        $this->assertEquals('Test Collection', $decoded['name']);
        $this->assertStringContainsString('"name"', $json);
    }

    public function testJsonOmitsAfterSettingToNull(): void
    {
        // Start with both values set
        $collection = new RangeCollection('initial-id', 'Initial Name');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        // Verify both are present initially
        $json1 = $collection->toJson();
        $decoded1 = json_decode($json1, true);
        $this->assertArrayHasKey('name', $decoded1);
        $this->assertArrayHasKey('id', $decoded1);
        
        // Set to null
        $collection->setName(null);
        $collection->setId(null);
        
        // Verify both are now completely absent
        $json2 = $collection->toJson();
        $decoded2 = json_decode($json2, true);
        
        $this->assertArrayNotHasKey('name', $decoded2);
        $this->assertArrayNotHasKey('id', $decoded2);
        $this->assertStringNotContainsString('"name"', $json2);
        $this->assertStringNotContainsString('"id"', $json2);
        
        // But ranges should still be present
        $this->assertArrayHasKey('ranges', $decoded2);
        $this->assertCount(1, $decoded2['ranges']);
    }

    public function testJsonDoesNotIncludeEmptyStringAsNull(): void
    {
        // Test that empty strings are treated as values, not omitted
        $collection = new RangeCollection('', '');
        $range = new ScriptureRange($this->genesisBook, 1, 1, 1, 5);
        $collection->addRange($range);
        
        $json = $collection->toJson();
        $decoded = json_decode($json, true);
        
        // Empty strings should be included (they're not null)
        $this->assertArrayHasKey('name', $decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertEquals('', $decoded['name']);
        $this->assertEquals('', $decoded['id']);
    }
} 