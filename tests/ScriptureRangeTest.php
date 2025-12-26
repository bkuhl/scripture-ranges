<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges\Tests;

use BKuhl\ScriptureRanges\ScriptureRange;
use BKuhl\ScriptureRanges\ScriptureRangeBuilder;
use BKuhl\ScriptureRanges\Tests\Mocks\MockBook;
use BKuhl\ScriptureRanges\Tests\Mocks\MockVerse;
use PHPUnit\Framework\TestCase;

class ScriptureRangeTest extends TestCase
{

    public function testBasicRange(): void
    {
        $collection = (new ScriptureRangeBuilder())
            ->with(MockBook::genesis(), chapter: 1, chapterEnd: 3, toVerse: 15)
            ->build();
        
        $range = $collection->getRanges()[0];
        $this->assertEquals(MockBook::genesis(), $range->book());
        $this->assertEquals(1, $range->startChapter());
        $this->assertEquals(3, $range->endChapter());
        $this->assertEquals(1, $range->startVerse());
        $this->assertEquals(15, $range->endVerse());
        $this->assertEquals('Genesis 1-3:15', $range->reference());
    }

    public function testSingleChapterRange(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);
        $this->assertEquals('Luke 1:10', $range->reference());
    }

    public function testSingleVerseRange(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 1);
        $this->assertEquals('Genesis 1:1', $range->reference());
    }

    public function testRangeWithNonDefaultStartVerse(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 5, 15);
        $this->assertEquals('Genesis 1:5-15', $range->reference());
    }

    public function testRangeWithNonDefaultEndVerse(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 15);
        $this->assertEquals('Genesis 1:15', $range->reference());
    }

    public function testRangeWithBothNonDefaultVerses(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 5, 15);
        $this->assertEquals('Genesis 1:5-15', $range->reference());
    }

    public function testMultiChapterRangeWithDefaultVerses(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24); // Genesis 3 has 24 verses
        $this->assertEquals('Genesis 1-3', $range->reference());
    }

    public function testMultiChapterRangeWithNonDefaultVerses(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 5, 15);
        $this->assertEquals('Genesis 1:5-3:15', $range->reference());
    }

    public function testRangeWithEndAtChapterEnd(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 31); // Genesis 1 has 31 verses
        $this->assertEquals('Genesis 1', $range->reference());
    }

    public function testRangeWithStartAtChapterStartAndEndAtChapterEnd(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 2, 2, 1, 25); // Genesis 2 has 25 verses
        $this->assertEquals('Genesis 2', $range->reference());
    }

    public function testContainsVerse(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 15);
        
        $verse1 = MockVerse::genesis(1, 1);
        $verse2 = MockVerse::genesis(5, 2);
        $verse3 = MockVerse::genesis(15, 3);
        $verse4 = MockVerse::genesis(16, 3); // Outside range
        $verse5 = MockVerse::genesis(1, 4); // Outside range
        $verse6 = MockVerse::luke(1, 1); // Different book

        $this->assertTrue($range->contains($verse1));
        $this->assertTrue($range->contains($verse2));
        $this->assertTrue($range->contains($verse3));
        $this->assertFalse($range->contains($verse4));
        $this->assertFalse($range->contains($verse5));
        $this->assertFalse($range->contains($verse6));
    }

    public function testAddExclusion(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);

        // Add exclusion for Luke 1:7-8
        $range->addExclusion(1, 1, 7, 8);

        $this->assertCount(1, $range->exclusions());
        
        // Test verses in exclusion range
        $excludedVerse1 = MockVerse::luke(7, 1);
        $excludedVerse2 = MockVerse::luke(8, 1);
        
        $this->assertFalse($range->contains($excludedVerse1));
        $this->assertFalse($range->contains($excludedVerse2));
        
        // Other verses should still be included
        $verse1 = MockVerse::luke(1, 1);
        $verse10 = MockVerse::luke(10, 1);
        
        $this->assertTrue($range->contains($verse1));
        $this->assertTrue($range->contains($verse10));
    }

    public function testAddExclusionOutsideRange(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);

        $this->expectException(\InvalidArgumentException::class);
        $range->addExclusion(2, 2, 1, 5); // Outside the range
    }

    public function testRemoveExclusion(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);

        $range->addExclusion(1, 1, 7, 8);
        $this->assertCount(1, $range->exclusions());

        $range->removeExclusion(1, 1, 7, 8);
        $this->assertCount(0, $range->exclusions());
        
        // Verse should now be included
        $verse = new MockVerse(7, 1, MockBook::luke());
        $this->assertTrue($range->contains($verse));
    }

    public function testInvalidRangeStartChapterGreaterThanEnd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ScriptureRange(MockBook::genesis(), 3, 1, 1, 15);
    }

    public function testInvalidRangeStartVerseGreaterThanEndInSameChapter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ScriptureRange(MockBook::genesis(), 1, 1, 15, 1);
    }

    public function testToArray(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);
        $range->addExclusion(1, 1, 7, 8);

        $array = $range->toArray();

        $this->assertEquals(42, $array['start']['book']); // Luke ID is 42
        $this->assertEquals(1, $array['start']['chapter']);
        $this->assertArrayNotHasKey('verse', $array['start']); // verse should be omitted when it's 1
        $this->assertEquals(42, $array['end']['book']); // Luke ID is 42
        $this->assertEquals(1, $array['end']['chapter']);
        $this->assertEquals(10, $array['end']['verse']);
        $this->assertArrayHasKey('exclude', $array);
        $this->assertCount(1, $array['exclude']);
        $this->assertEquals(1, $array['exclude'][0]['start']['chapter']);
        $this->assertEquals(7, $array['exclude'][0]['start']['verse']);
        $this->assertEquals(1, $array['exclude'][0]['end']['chapter']);
        $this->assertEquals(8, $array['exclude'][0]['end']['verse']);
    }

    public function testToArrayWithNonDefaultStartVerse(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 5, 10);

        $array = $range->toArray();

        $this->assertEquals(5, $array['start']['verse']); // verse should be included when not 1
    }

    public function testToArrayWithoutExclusions(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);

        $array = $range->toArray();

        $this->assertArrayNotHasKey('exclude', $array); // exclude should be omitted when empty
    }

    public function testFromArray(): void
    {
        $data = [
            'start' => [
                'book' => 42, // Luke
                'chapter' => 1
            ],
            'end' => [
                'book' => 42, // Luke
                'chapter' => 1,
                'verse' => 10
            ],
            'exclude' => [
                [
                    'start' => [
                        'chapter' => 1,
                        'verse' => 7
                    ],
                    'end' => [
                        'chapter' => 1,
                        'verse' => 8
                    ]
                ]
            ]
        ];

        $range = ScriptureRange::fromArray($data, MockBook::luke());

        $this->assertEquals(MockBook::luke(), $range->book());
        $this->assertEquals(1, $range->startChapter());
        $this->assertEquals(1, $range->endChapter());
        $this->assertEquals(1, $range->startVerse()); // should default to 1
        $this->assertEquals(10, $range->endVerse());
        $this->assertCount(1, $range->exclusions());
    }

    public function testFromArrayWithExplicitStartVerse(): void
    {
        $data = [
            'start' => [
                'book' => 42, // Luke
                'chapter' => 1,
                'verse' => 5
            ],
            'end' => [
                'book' => 42, // Luke
                'chapter' => 1,
                'verse' => 10
            ]
        ];

        $range = ScriptureRange::fromArray($data, MockBook::luke());

        $this->assertEquals(5, $range->startVerse());
        $this->assertEquals(10, $range->endVerse());
    }

    public function testFromArrayWithMissingStartVerse(): void
    {
        $data = [
            'start' => [
                'book' => 42, // Luke
                'chapter' => 1
            ],
            'end' => [
                'book' => 42, // Luke
                'chapter' => 1,
                'verse' => 10
            ]
        ];

        $range = ScriptureRange::fromArray($data, MockBook::luke());

        $this->assertEquals(1, $range->startVerse()); // Should default to 1
        $this->assertEquals(10, $range->endVerse());
    }

    public function testFromArrayWithMissingEndVerse(): void
    {
        $data = [
            'start' => [
                'book' => 42, // Luke
                'chapter' => 1,
                'verse' => 5
            ],
            'end' => [
                'book' => 42, // Luke
                'chapter' => 1
            ]
        ];

        $range = ScriptureRange::fromArray($data, MockBook::luke());

        $this->assertEquals(5, $range->startVerse());
        $this->assertEquals(80, $range->endVerse()); // Should default to chapter verse count (80 for Luke 1)
    }

    public function testFromArrayWithMissingStartAndEndVerse(): void
    {
        $data = [
            'start' => [
                'book' => 1, // Genesis
                'chapter' => 1
            ],
            'end' => [
                'book' => 1, // Genesis
                'chapter' => 2
            ]
        ];

        $range = ScriptureRange::fromArray($data, MockBook::genesis());
        
        $this->assertEquals(1, $range->startVerse());
        $this->assertEquals(25, $range->endVerse()); // Should default to Genesis 2 verse count (25)
    }

    // Validation tests from RangeValidationTest
    public function testSingleChapterRangeValidation(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);
        
        $this->assertTrue($range->contains(MockVerse::luke(1, 1)));
        $this->assertTrue($range->contains(MockVerse::luke(5, 1)));
        $this->assertTrue($range->contains(MockVerse::luke(10, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(11, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(1, 2)));
    }

    public function testMultiChapterRangeValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 15);
        
        $this->assertTrue($range->contains(MockVerse::genesis(1, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(31, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(1, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(25, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(1, 3)));
        $this->assertTrue($range->contains(MockVerse::genesis(15, 3)));
        $this->assertFalse($range->contains(MockVerse::genesis(16, 3)));
        $this->assertFalse($range->contains(MockVerse::genesis(1, 4)));
    }

    public function testRangeWithExclusionsValidation(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);
        $range->addExclusion(1, 1, 5, 7);
        
        $this->assertTrue($range->contains(MockVerse::luke(1, 1)));
        $this->assertTrue($range->contains(MockVerse::luke(4, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(5, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(6, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(7, 1)));
        $this->assertTrue($range->contains(MockVerse::luke(8, 1)));
        $this->assertTrue($range->contains(MockVerse::luke(10, 1)));
    }

    public function testMultipleExclusionsValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 2, 1, 25);
        $range->addExclusion(1, 1, 5, 7);
        $range->addExclusion(2, 2, 10, 12);
        
        $this->assertFalse($range->contains(MockVerse::genesis(5, 1)));
        $this->assertFalse($range->contains(MockVerse::genesis(7, 1)));
        $this->assertFalse($range->contains(MockVerse::genesis(10, 2)));
        $this->assertFalse($range->contains(MockVerse::genesis(12, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(4, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(8, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(9, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(13, 2)));
    }

    public function testCrossChapterExclusionValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 25);
        $range->addExclusion(1, 2, 20, 5);
        
        $this->assertFalse($range->contains(MockVerse::genesis(20, 1)));
        $this->assertFalse($range->contains(MockVerse::genesis(31, 1)));
        $this->assertFalse($range->contains(MockVerse::genesis(1, 2)));
        $this->assertFalse($range->contains(MockVerse::genesis(5, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(19, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(6, 2)));
        $this->assertTrue($range->contains(MockVerse::genesis(1, 3)));
    }

    public function testDifferentBookRejectionValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 10);
        $this->assertFalse($range->contains(MockVerse::luke(1, 1)));
    }

    public function testSingleVerseRangeValidation(): void
    {
        $range = new ScriptureRange(MockBook::luke(), 1, 1, 5, 5);
        
        $this->assertTrue($range->contains(MockVerse::luke(5, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(4, 1)));
        $this->assertFalse($range->contains(MockVerse::luke(6, 1)));
    }

    public function testRangeBoundaryConditionsValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 15);
        
        // Test boundary conditions
        $this->assertTrue($range->contains(MockVerse::genesis(1, 1))); // Start
        $this->assertTrue($range->contains(MockVerse::genesis(15, 3))); // End
        $this->assertFalse($range->contains(MockVerse::genesis(0, 1))); // Before start
        $this->assertFalse($range->contains(MockVerse::genesis(16, 3))); // After end
    }

    public function testRangeWithDefaultStartVerseValidation(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 15);
        
        // Test that default start verse (1) works correctly
        $this->assertTrue($range->contains(MockVerse::genesis(1, 1)));
        $this->assertTrue($range->contains(MockVerse::genesis(5, 1)));
        $this->assertFalse($range->contains(MockVerse::genesis(0, 1)));
    }

    public function testRangeWithDefaultEndVerseValidation(): void
    {
        // Create a range that ends at the end of a chapter
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24); // Genesis 3 has 24 verses
        
        $this->assertTrue($range->contains(MockVerse::genesis(24, 3))); // Last verse
        $this->assertFalse($range->contains(MockVerse::genesis(25, 3))); // Beyond end
    }

    public function testRangeWithBothDefaultVersesValidation(): void
    {
        // Create a range that starts at verse 1 and ends at chapter end
        $range = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 31); // Genesis 1 has 31 verses
        
        $this->assertTrue($range->contains(MockVerse::genesis(1, 1))); // First verse
        $this->assertTrue($range->contains(MockVerse::genesis(31, 1))); // Last verse
        $this->assertFalse($range->contains(MockVerse::genesis(32, 1))); // Beyond end
    }

    // Tests for consecutive chapters validation
    public function testHasConsecutiveChaptersWithFullChapters(): void
    {
        // Genesis 1 has 31 verses, Genesis 2 has 25 verses, Genesis 3 has 24 verses
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24);
        
        $this->assertTrue($range->hasConsecutiveChapters(3));
        $this->assertTrue($range->hasConsecutiveChapters(2));
        $this->assertTrue($range->hasConsecutiveChapters(1));
        $this->assertFalse($range->hasConsecutiveChapters(4));
    }

    public function testHasConsecutiveChaptersWithPartialChapter(): void
    {
        // Range doesn't include all of chapter 2
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 15);
        
        // Chapter 1 is full, chapter 2 is partial (ends at verse 15 but chapter has 25 verses), chapter 3 is partial (ends at verse 15)
        $this->assertFalse($range->hasConsecutiveChapters(2));
        $this->assertTrue($range->hasConsecutiveChapters(1));
    }

    public function testHasConsecutiveChaptersWithExclusion(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24);
        
        // Exclude one verse from chapter 2, making it not fully included
        $range->addExclusion(2, 2, 10, 10);
        
        // Chapter 1 is full, chapter 2 is not (has exclusion), chapter 3 is full
        $this->assertFalse($range->hasConsecutiveChapters(2));
        $this->assertTrue($range->hasConsecutiveChapters(1));
    }

    public function testHasConsecutiveChaptersWithExclusionInMiddle(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 4, 1, 25);
        
        // Exclude verses from chapter 2, breaking the sequence
        $range->addExclusion(2, 2, 5, 5);
        
        // Chapters 1, 3, 4 are full but not consecutive due to chapter 2
        $this->assertTrue($range->hasConsecutiveChapters(1));
        $this->assertFalse($range->hasConsecutiveChapters(2));
    }

    public function testHasConsecutiveChaptersWithZeroCount(): void
    {
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24);
        
        $this->assertFalse($range->hasConsecutiveChapters(0));
    }

    public function testHasConsecutiveChaptersWithGapInMiddle(): void
    {
        // Genesis 1 (31 verses), Genesis 2 (25 verses), Genesis 3 (24 verses)
        $range = new ScriptureRange(MockBook::genesis(), 1, 3, 1, 24);
        
        // Exclude all of chapter 2
        $range->addExclusion(2, 2, 1, 25);
        
        // Chapters 1 and 3 are full but not consecutive
        $this->assertTrue($range->hasConsecutiveChapters(1));
        $this->assertFalse($range->hasConsecutiveChapters(2));
    }

    public function testCombineRanges(): void
    {
        // Combine Genesis 1-2 and Genesis 3-4
        $range1 = new ScriptureRange(MockBook::genesis(), 1, 2, 1, MockBook::genesis()->chapterVerseCount(2));
        $range2 = new ScriptureRange(MockBook::genesis(), 3, 4, 1, MockBook::genesis()->chapterVerseCount(4));

        $combined = ScriptureRange::combine([$range1, $range2]);

        $this->assertEquals(MockBook::genesis(), $combined->book());
        $this->assertEquals(1, $combined->startChapter());
        $this->assertEquals(4, $combined->endChapter());
        $this->assertEquals(1, $combined->startVerse());
        $this->assertEquals(MockBook::genesis()->chapterVerseCount(4), $combined->endVerse());
        $this->assertEmpty($combined->exclusions()); // No gaps between ranges
    }

    public function testCombineOverlappingRanges(): void
    {
        // Combine Genesis 1:1-20 and Genesis 1:15-30
        $range1 = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 20);
        $range2 = new ScriptureRange(MockBook::genesis(), 1, 1, 15, 30);

        $combined = ScriptureRange::combine([$range1, $range2]);

        $this->assertEquals(1, $combined->startChapter());
        $this->assertEquals(1, $combined->endChapter());
        $this->assertEquals(1, $combined->startVerse());
        $this->assertEquals(30, $combined->endVerse());
        // No exclusions - verses 1-30 are all covered
        $this->assertEmpty($combined->exclusions());
    }

    public function testCombineRangesWithGaps(): void
    {
        // Combine Genesis 1:1-10 and Genesis 1:20-30 (gap at verses 11-19)
        $range1 = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 10);
        $range2 = new ScriptureRange(MockBook::genesis(), 1, 1, 20, 30);

        $combined = ScriptureRange::combine([$range1, $range2]);

        $this->assertEquals(1, $combined->startChapter());
        $this->assertEquals(1, $combined->endChapter());
        $this->assertEquals(1, $combined->startVerse());
        $this->assertEquals(30, $combined->endVerse());
        // Should have exclusion for verses 11-19
        $exclusions = $combined->exclusions();
        $this->assertCount(1, $exclusions);
        $this->assertEquals(1, $exclusions[0]['startChapter']);
        $this->assertEquals(1, $exclusions[0]['endChapter']);
        $this->assertEquals(11, $exclusions[0]['startVerse']);
        $this->assertEquals(19, $exclusions[0]['endVerse']);
    }

    public function testCombineRangesWithExclusions(): void
    {
        // Range 1: Genesis 1:1-50 with exclusion of 1:10-15
        $range1 = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 50);
        $range1->addExclusion(1, 1, 10, 15);

        // Range 2: Genesis 1:20-30
        $range2 = new ScriptureRange(MockBook::genesis(), 1, 1, 20, 30);

        $combined = ScriptureRange::combine([$range1, $range2]);

        $this->assertEquals(1, $combined->startChapter());
        $this->assertEquals(1, $combined->endChapter());
        $this->assertEquals(1, $combined->startVerse());
        $this->assertEquals(50, $combined->endVerse());
        // Should have exclusion for verses 10-15 and 16-19 (gap)
        $exclusions = $combined->exclusions();
        $this->assertGreaterThanOrEqual(1, count($exclusions));
    }

    public function testCombineRangesDifferentBooksThrowsException(): void
    {
        $range1 = new ScriptureRange(MockBook::genesis(), 1, 1, 1, 10);
        $range2 = new ScriptureRange(MockBook::luke(), 1, 1, 1, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All ranges must be in the same book');
        ScriptureRange::combine([$range1, $range2]);
    }

    public function testCombineEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot combine empty array of ranges');
        ScriptureRange::combine([]);
    }
} 