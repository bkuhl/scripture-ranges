<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges;

/**
 * Combines multiple ScriptureRange objects into a single range.
 * 
 * Handles boundary merging and calculates exclusions by identifying gaps in coverage
 * across all source ranges. The combined range represents the union of all input ranges.
 */
class ScriptureRangeCombiner
{
    /**
     * Combine multiple ranges into a single range.
     * 
     * All ranges must be in the same book. The resulting range covers the union of all ranges.
     * A verse is included in the combined range if it's included in at least one of the source ranges.
     * 
     * @param ScriptureRange[] $ranges Array of ranges to combine (must all be same book)
     * @return ScriptureRange A new combined range
     * @throws \InvalidArgumentException If ranges are empty or not all same book
     */
    public function combine(array $ranges): ScriptureRange
    {
        if (empty($ranges)) {
            throw new \InvalidArgumentException('Cannot combine empty array of ranges');
        }

        // Validate all ranges are in the same book
        $firstBook = $ranges[0]->book();
        foreach ($ranges as $range) {
            if ($range->book()->name() !== $firstBook->name()) {
                throw new \InvalidArgumentException('All ranges must be in the same book');
            }
        }

        // Determine combined boundaries (union of all ranges)
        $startChapter = PHP_INT_MAX;
        $startVerse = PHP_INT_MAX;
        $endChapter = 0;
        $endVerse = 0;

        foreach ($ranges as $range) {
            if ($range->startChapter() < $startChapter) {
                $startChapter = $range->startChapter();
                $startVerse = $range->startVerse();
            } elseif ($range->startChapter() === $startChapter) {
                $startVerse = min($startVerse, $range->startVerse());
            }

            if ($range->endChapter() > $endChapter) {
                $endChapter = $range->endChapter();
                $endVerse = $range->endVerse();
            } elseif ($range->endChapter() === $endChapter) {
                $endVerse = max($endVerse, $range->endVerse());
            }
        }

        // Create the combined range
        $combined = new ScriptureRange($firstBook, $startChapter, $endChapter, $startVerse, $endVerse);

        // Calculate exclusions by finding gaps in coverage
        // A verse is excluded if it's within the combined boundary but not included in any source range
        $this->calculateExclusionsFromRanges($combined, $ranges);

        return $combined;
    }

    /**
     * Calculate exclusions by finding verses that are in the combined boundary but not included in any source range.
     * Uses an efficient approach that identifies gaps rather than checking every verse.
     */
    private function calculateExclusionsFromRanges(ScriptureRange $combined, array $ranges): void
    {
        $book = $combined->book();
        
        // For each chapter in the combined range, determine which verses are included
        for ($chapter = $combined->startChapter(); $chapter <= $combined->endChapter(); $chapter++) {
            $chapterStartVerse = ($chapter === $combined->startChapter()) ? $combined->startVerse() : 1;
            $chapterEndVerse = ($chapter === $combined->endChapter()) ? $combined->endVerse() : $book->chapterVerseCount($chapter);

            // Collect all included verse ranges for this chapter
            $includedRanges = [];
            foreach ($ranges as $range) {
                if ($chapter >= $range->startChapter() && $chapter <= $range->endChapter()) {
                    // This range covers this chapter
                    $rangeStartVerse = ($chapter === $range->startChapter()) ? $range->startVerse() : 1;
                    $rangeEndVerse = ($chapter === $range->endChapter()) ? $range->endVerse() : $book->chapterVerseCount($chapter);
                    
                    // Subtract exclusions from this range
                    $actualIncluded = $this->subtractExclusionsFromVerseRange(
                        $book,
                        $chapter,
                        $rangeStartVerse,
                        $rangeEndVerse,
                        $range->exclusions()
                    );
                    
                    foreach ($actualIncluded as $includedRange) {
                        $includedRanges[] = $includedRange;
                    }
                }
            }

            // Merge overlapping included ranges
            $mergedIncluded = $this->mergeVerseRanges($includedRanges);

            // Find gaps (verses not in any included range) and add them as exclusions
            $currentVerse = $chapterStartVerse;
            foreach ($mergedIncluded as $includedRange) {
                if ($currentVerse < $includedRange['start']) {
                    // Gap before this included range
                    $this->addExclusionToRange($combined, $chapter, $chapter, $currentVerse, $includedRange['start'] - 1);
                }
                $currentVerse = max($currentVerse, $includedRange['end'] + 1);
            }

            // Check for gap after last included range
            if ($currentVerse <= $chapterEndVerse) {
                $this->addExclusionToRange($combined, $chapter, $chapter, $currentVerse, $chapterEndVerse);
            }
        }
    }

    /**
     * Subtract exclusions from a verse range, returning the remaining included ranges
     */
    private function subtractExclusionsFromVerseRange(
        \BKuhl\ScriptureRanges\Interfaces\BookInterface $book,
        int $chapter,
        int $startVerse,
        int $endVerse,
        array $exclusions
    ): array {
        $included = [['start' => $startVerse, 'end' => $endVerse]];

        foreach ($exclusions as $exclusion) {
            if ($exclusion['startChapter'] <= $chapter && $exclusion['endChapter'] >= $chapter) {
                $exclStart = ($exclusion['startChapter'] === $chapter) ? $exclusion['startVerse'] : 1;
                $exclEnd = ($exclusion['endChapter'] === $chapter) ? $exclusion['endVerse'] : $book->chapterVerseCount($chapter);

                $newIncluded = [];
                foreach ($included as $range) {
                    if ($exclEnd < $range['start'] || $exclStart > $range['end']) {
                        // Exclusion doesn't overlap this range
                        $newIncluded[] = $range;
                    } else {
                        // Exclusion overlaps - split the range
                        if ($range['start'] < $exclStart) {
                            $newIncluded[] = ['start' => $range['start'], 'end' => $exclStart - 1];
                        }
                        if ($range['end'] > $exclEnd) {
                            $newIncluded[] = ['start' => $exclEnd + 1, 'end' => $range['end']];
                        }
                    }
                }
                $included = $newIncluded;
            }
        }

        return $included;
    }

    /**
     * Merge overlapping verse ranges
     */
    private function mergeVerseRanges(array $ranges): array
    {
        if (empty($ranges)) {
            return [];
        }

        // Sort by start verse
        usort($ranges, fn($a, $b) => $a['start'] <=> $b['start']);

        $merged = [$ranges[0]];
        for ($i = 1; $i < count($ranges); $i++) {
            $last = &$merged[count($merged) - 1];
            if ($ranges[$i]['start'] <= $last['end'] + 1) {
                // Overlaps or adjacent - merge
                $last['end'] = max($last['end'], $ranges[$i]['end']);
            } else {
                // No overlap - add new range
                $merged[] = $ranges[$i];
            }
        }

        return $merged;
    }

    /**
     * Add an exclusion to a range
     */
    private function addExclusionToRange(
        ScriptureRange $range,
        int $startChapter,
        int $endChapter,
        int $startVerse,
        int $endVerse
    ): void {
        $range->addExclusionUnsafe($startChapter, $endChapter, $startVerse, $endVerse);
    }
}

