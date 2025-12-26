<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges;

use BKuhl\ScriptureRanges\Interfaces\VerseInterface;

class RangeCollection
{
    /**
     * @var ScriptureRange[]
     */
    private array $ranges = [];

    public function __construct(
        private ?string $id = null,
        private ?string $name = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function addRange(ScriptureRange $range): void
    {
        $this->ranges[] = $range;
    }

    public function removeRange(ScriptureRange $range): void
    {
        $this->ranges = array_filter(
            $this->ranges,
            fn($r) => $r !== $range
        );
    }

    public function getRanges(): array
    {
        return $this->ranges;
    }

    public function contains(VerseInterface $verse): bool
    {
        foreach ($this->ranges as $range) {
            if ($range->contains($verse)) {
                return true;
            }
        }

        return false;
    }

    public function getRangesContaining(VerseInterface $verse): array
    {
        return array_filter(
            $this->ranges,
            fn($range) => $range->contains($verse)
        );
    }

    /**
     * Check if this collection has at least N consecutive full chapters across all ranges.
     * 
     * Checks for consecutive chapters within ranges and across ranges if they're in the same
     * book and the ranges are consecutive (e.g., Genesis 1-2 in one range, Genesis 3-4 in another).
     * 
     * @param int $minimumCount Minimum number of consecutive full chapters required
     * @return bool True if at least N consecutive full chapters are found across ranges
     */
    public function hasConsecutiveChapters(int $minimumCount): bool
    {
        if ($minimumCount <= 0 || empty($this->ranges)) {
            return false;
        }

        // Group ranges by book
        $rangesByBook = [];
        foreach ($this->ranges as $range) {
            $bookName = $range->book()->name();
            if (!isset($rangesByBook[$bookName])) {
                $rangesByBook[$bookName] = [];
            }
            $rangesByBook[$bookName][] = $range;
        }

        // Check each book's ranges for consecutive chapters
        foreach ($rangesByBook as $bookRanges) {
            if ($this->checkBookRangesForConsecutiveChapters($bookRanges, $minimumCount)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if ranges in the same book have consecutive chapters across them.
     * 
     * Uses ScriptureRangeCombiner to combine ranges first, then checks for consecutive chapters.
     * This approach is preferred because:
     * - It ensures correctness by properly handling overlapping ranges, gaps, and exclusions
     * - It reuses well-tested combination logic
     * - It's simpler than implementing custom multi-range chapter checking
     * - Performance is comparable since both approaches iterate through chapters
     */
    private function checkBookRangesForConsecutiveChapters(array $ranges, int $minimumCount): bool
    {
        if (count($ranges) === 1) {
            // Single range - use its own method
            return $ranges[0]->hasConsecutiveChapters($minimumCount);
        }

        // Combine all ranges into a single range, then check for consecutive chapters
        // The combiner properly handles overlaps, gaps, and exclusions
        $combined = ScriptureRange::combine($ranges);
        return $combined->hasConsecutiveChapters($minimumCount);
    }

    public function toArray(): array
    {
        $data = [
            'ranges' => array_map(fn($range) => $range->toArray(), $this->ranges)
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function reference(): string
    {
        if (empty($this->ranges)) {
            return '';
        }

        // Group ranges by book
        $bookRanges = [];
        foreach ($this->ranges as $range) {
            $bookName = $range->book()->name();
            if (!isset($bookRanges[$bookName])) {
                $bookRanges[$bookName] = [];
            }
            $bookRanges[$bookName][] = $range;
        }

        $references = [];
        foreach ($bookRanges as $bookName => $ranges) {
            $references[] = $this->formatBookReference($bookName, $ranges);
        }

        return implode(', ', $references);
    }

    private function formatBookReference(string $bookName, array $ranges): string
    {
        if (count($ranges) === 1) {
            return $ranges[0]->reference();
        }

        // Sort ranges by start chapter and verse
        usort($ranges, function ($a, $b) {
            if ($a->startChapter() !== $b->startChapter()) {
                return $a->startChapter() <=> $b->startChapter();
            }
            return $a->startVerse() <=> $b->startVerse();
        });

        // Group consecutive chapters
        $chapterGroups = $this->groupConsecutiveChapters($ranges);
        
        $parts = [];
        foreach ($chapterGroups as $group) {
            $parts[] = $this->formatChapterGroup($group);
        }

        return $bookName . ' (' . implode(', ', $parts) . ')';
    }

    private function groupConsecutiveChapters(array $ranges): array
    {
        $groups = [];
        $currentGroup = [];

        foreach ($ranges as $range) {
            if (empty($currentGroup)) {
                $currentGroup = [$range];
            } else {
                $lastRange = end($currentGroup);
                
                // Check if this range is consecutive with the last one
                if ($this->isConsecutive($lastRange, $range)) {
                    $currentGroup[] = $range;
                } else {
                    $groups[] = $currentGroup;
                    $currentGroup = [$range];
                }
            }
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    private function isConsecutive(ScriptureRange $range1, ScriptureRange $range2): bool
    {
        // Check if ranges are in the same book and consecutive
        if ($range1->book()->name() !== $range2->book()->name()) {
            return false;
        }

        // Check if the end of range1 is consecutive with the start of range2
        if ($range1->endChapter() === $range2->startChapter()) {
            // Same chapter - check if end verse of range1 is consecutive with start verse of range2
            $range1EndVerse = $range1->endVerse();
            $range2StartVerse = $range2->startVerse();
            
            // Check if range1 ends at the last verse of its chapter and range2 starts at verse 1
            $isRange1EndAtChapterEnd = $range1EndVerse === $range1->book()->chapterVerseCount($range1->endChapter());
            $isRange2StartAtChapterStart = $range2StartVerse === 1;
            
            return $isRange1EndAtChapterEnd && $isRange2StartAtChapterStart;
        } elseif ($range1->endChapter() + 1 === $range2->startChapter()) {
            // Consecutive chapters - check if range1 ends at chapter end and range2 starts at chapter start
            $isRange1EndAtChapterEnd = $range1->endVerse() === $range1->book()->chapterVerseCount($range1->endChapter());
            $isRange2StartAtChapterStart = $range2->startVerse() === 1;
            
            return $isRange1EndAtChapterEnd && $isRange2StartAtChapterStart;
        }

        return false;
    }

    private function formatChapterGroup(array $ranges): string
    {
        if (count($ranges) === 1) {
            return $this->formatSingleRange($ranges[0]);
        }

        $firstRange = $ranges[0];
        $lastRange = end($ranges);

        // Check if all ranges are full chapters
        $allFullChapters = true;
        foreach ($ranges as $range) {
            $isStartAtChapterStart = $range->startVerse() === 1;
            $isEndAtChapterEnd = $range->endVerse() === $range->book()->chapterVerseCount($range->endChapter());
            if (!$isStartAtChapterStart || !$isEndAtChapterEnd) {
                $allFullChapters = false;
                break;
            }
        }

        if ($allFullChapters) {
            // All ranges are full chapters - use chapter range format
            return $firstRange->startChapter() . '-' . $lastRange->endChapter();
        } else {
            // Mixed ranges - format each range individually
            $parts = [];
            foreach ($ranges as $range) {
                $parts[] = $this->formatSingleRange($range);
            }
            return implode(', ', $parts);
        }
    }

    private function formatSingleRange(ScriptureRange $range): string
    {
        $startChapter = $range->startChapter();
        $endChapter = $range->endChapter();
        $startVerse = $range->startVerse();
        $endVerse = $range->endVerse();

        // Check if it's a full chapter
        $isStartAtChapterStart = $startVerse === 1;
        $isEndAtChapterEnd = $endVerse === $range->book()->chapterVerseCount($endChapter);

        if ($startChapter === $endChapter) {
            // Same chapter
            if ($isStartAtChapterStart && $isEndAtChapterEnd) {
                return (string) $startChapter;
            } elseif ($isStartAtChapterStart) {
                return $startChapter . ':' . $endVerse;
            } elseif ($isEndAtChapterEnd) {
                // Even if it ends at chapter end, show the full range if it doesn't start at chapter start
                return $startChapter . ':' . $startVerse . '-' . $endVerse;
            } else {
                if ($startVerse === $endVerse) {
                    return $startChapter . ':' . $startVerse;
                } else {
                    return $startChapter . ':' . $startVerse . '-' . $endVerse;
                }
            }
        } else {
            // Different chapters
            $startPart = $isStartAtChapterStart ? (string) $startChapter : $startChapter . ':' . $startVerse;
            $endPart = $isEndAtChapterEnd ? (string) $endChapter : $endChapter . ':' . $endVerse;
            
            return $startPart . '-' . $endPart;
        }
    }
} 