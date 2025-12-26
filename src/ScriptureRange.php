<?php

declare(strict_types=1);

namespace BKuhl\ScriptureRanges;

use BKuhl\ScriptureRanges\Interfaces\BookInterface;
use BKuhl\ScriptureRanges\Interfaces\VerseInterface;

class ScriptureRange
{
    /**
     * @var array Array of exclusion ranges, each with startChapter, endChapter, startVerse, endVerse
     */
    private array $exclusions = [];

    public function __construct(
        private readonly BookInterface $book,
        private readonly int $startChapter,
        private readonly int $endChapter,
        private readonly int $startVerse,
        private readonly int $endVerse
    ) {
        if ($startChapter > $endChapter) {
            throw new \InvalidArgumentException('Start chapter cannot be greater than end chapter');
        }

        if ($startChapter === $endChapter && $startVerse > $endVerse) {
            throw new \InvalidArgumentException('Start verse cannot be greater than end verse in same chapter');
        }
    }

    public function contains(VerseInterface $verse): bool
    {
        // Check if verse is in the same book
        if ($verse->book()->name() !== $this->book->name()) {
            return false;
        }

        $chapter = $verse->chapterNumber();
        $verseNumber = $verse->number();

        // Check if verse is within the chapter range
        if ($chapter < $this->startChapter || $chapter > $this->endChapter) {
            return false;
        }

        // Check verse boundaries for start and end chapters
        if ($chapter === $this->startChapter && $verseNumber < $this->startVerse) {
            return false;
        }

        if ($chapter === $this->endChapter && $verseNumber > $this->endVerse) {
            return false;
        }

        // Check if verse is in any exclusion range
        foreach ($this->exclusions as $exclusion) {
            if ($this->isVerseInRange($chapter, $verseNumber, $exclusion)) {
                return false;
            }
        }

        return true;
    }

    public function addExclusion(int $startChapter, int $endChapter, int $startVerse, int $endVerse): void
    {
        // Validate exclusion range
        if ($startChapter > $endChapter) {
            throw new \InvalidArgumentException('Exclusion start chapter cannot be greater than end chapter');
        }

        if ($startChapter === $endChapter && $startVerse > $endVerse) {
            throw new \InvalidArgumentException('Exclusion start verse cannot be greater than end verse in same chapter');
        }

        // Check if exclusion is within this range
        if ($startChapter < $this->startChapter || $endChapter > $this->endChapter) {
            throw new \InvalidArgumentException('Exclusion must be within this range');
        }

        $this->exclusions[] = [
            'startChapter' => $startChapter,
            'endChapter' => $endChapter,
            'startVerse' => $startVerse,
            'endVerse' => $endVerse
        ];
    }

    /**
     * Add an exclusion without validation (used internally for combine operation)
     * @internal
     */
    public function addExclusionUnsafe(int $startChapter, int $endChapter, int $startVerse, int $endVerse): void
    {
        $this->exclusions[] = [
            'startChapter' => $startChapter,
            'endChapter' => $endChapter,
            'startVerse' => $startVerse,
            'endVerse' => $endVerse
        ];
    }

    public function removeExclusion(int $startChapter, int $endChapter, int $startVerse, int $endVerse): void
    {
        $this->exclusions = array_filter(
            $this->exclusions,
            fn($exclusion) => !(
                $exclusion['startChapter'] === $startChapter &&
                $exclusion['endChapter'] === $endChapter &&
                $exclusion['startVerse'] === $startVerse &&
                $exclusion['endVerse'] === $endVerse
            )
        );
    }

    public function exclusions(): array
    {
        return $this->exclusions;
    }

    public function book(): BookInterface
    {
        return $this->book;
    }

    public function startChapter(): int
    {
        return $this->startChapter;
    }

    public function endChapter(): int
    {
        return $this->endChapter;
    }

    public function startVerse(): int
    {
        return $this->startVerse;
    }

    public function endVerse(): int
    {
        return $this->endVerse;
    }

    public function reference(): string
    {
        $startVerse = $this->startVerse;
        $endVerse = $this->endVerse;
        
        // Check if end verse is the last verse of the chapter
        $isEndVerseLastOfChapter = $endVerse === $this->book->chapterVerseCount($this->endChapter);
        
        if ($this->startChapter === $this->endChapter) {
            // Same chapter
            if ($startVerse === 1 && $isEndVerseLastOfChapter) {
                // Start at beginning and end at end of chapter
                return sprintf('%s %d', $this->book->name(), $this->startChapter);
            } elseif ($startVerse === 1) {
                // Start at beginning of chapter
                return sprintf('%s %d:%d', $this->book->name(), $this->startChapter, $endVerse);
            } elseif ($isEndVerseLastOfChapter) {
                // End at end of chapter
                return sprintf('%s %d:%d', $this->book->name(), $this->startChapter, $startVerse);
            } else {
                // Both verses specified
                if ($startVerse === $endVerse) {
                    return sprintf('%s %d:%d', $this->book->name(), $this->startChapter, $startVerse);
                } else {
                    return sprintf('%s %d:%d-%d', $this->book->name(), $this->startChapter, $startVerse, $endVerse);
                }
            }
        } else {
            // Different chapters
            $startPart = $startVerse === 1 ? sprintf('%s %d', $this->book->name(), $this->startChapter) : sprintf('%s %d:%d', $this->book->name(), $this->startChapter, $startVerse);
            $endPart = $isEndVerseLastOfChapter ? sprintf('%d', $this->endChapter) : sprintf('%d:%d', $this->endChapter, $endVerse);
            
            return sprintf('%s-%s', $startPart, $endPart);
        }
    }

    public function toArray(): array
    {
        $result = [
            'start' => [
                'book' => $this->book->position(),
                'chapter' => $this->startChapter
            ],
            'end' => [
                'book' => $this->book->position(),
                'chapter' => $this->endChapter
            ]
        ];

        // Only add verse to start if it's not 1 (default)
        if ($this->startVerse !== 1) {
            $result['start']['verse'] = $this->startVerse;
        }

        // Only add verse to end if it's not the last verse of the chapter
        $isEndVerseLastOfChapter = $this->endVerse === $this->book->chapterVerseCount($this->endChapter);
        if (!$isEndVerseLastOfChapter) {
            $result['end']['verse'] = $this->endVerse;
        }

        // Only add exclusions if they exist
        if (!empty($this->exclusions)) {
            $result['exclude'] = [];
            foreach ($this->exclusions as $exclusion) {
                $exclusionData = [
                    'start' => [
                        'chapter' => $exclusion['startChapter']
                    ],
                    'end' => [
                        'chapter' => $exclusion['endChapter']
                    ]
                ];

                // Add start verse if it's not 1
                if ($exclusion['startVerse'] !== 1) {
                    $exclusionData['start']['verse'] = $exclusion['startVerse'];
                }

                // Add end verse if it's not the last verse of the chapter
                $isExclusionEndVerseLastOfChapter = $exclusion['endVerse'] === $this->book->chapterVerseCount($exclusion['endChapter']);
                if (!$isExclusionEndVerseLastOfChapter) {
                    $exclusionData['end']['verse'] = $exclusion['endVerse'];
                }

                $result['exclude'][] = $exclusionData;
            }
        }

        return $result;
    }

    public static function fromArray(array $data, BookInterface $book): self
    {
        $startVerse = $data['start']['verse'] ?? 1;
        
        // For end verse, use the actual verse count of the chapter if not specified
        $endVerse = $data['end']['verse'] ?? $book->chapterVerseCount($data['end']['chapter']);

        $range = new self(
            $book,
            $data['start']['chapter'],
            $data['end']['chapter'],
            $startVerse,
            $endVerse
        );

        // Add exclusions if they exist
        if (isset($data['exclude'])) {
            foreach ($data['exclude'] as $exclusionData) {
                $exclusionStartVerse = $exclusionData['start']['verse'] ?? 1;
                $exclusionEndVerse = $exclusionData['end']['verse'] ?? $book->chapterVerseCount($exclusionData['end']['chapter']);
                
                $range->addExclusion(
                    $exclusionData['start']['chapter'],
                    $exclusionData['end']['chapter'],
                    $exclusionStartVerse,
                    $exclusionEndVerse
                );
            }
        }

        return $range;
    }

    /**
     * Combine multiple ranges into a single range.
     * 
     * All ranges must be in the same book. The resulting range covers the union of all ranges.
     * A verse is included in the combined range if it's included in at least one of the source ranges.
     * 
     * @param ScriptureRange[] $ranges Array of ranges to combine (must all be same book)
     * @return self A new combined range
     * @throws \InvalidArgumentException If ranges are empty or not all same book
     */
    public static function combine(array $ranges): self
    {
        $combiner = new ScriptureRangeCombiner();
        return $combiner->combine($ranges);
    }

    /**
     * Check if a verse is included in a range (considering boundaries and exclusions)
     */
    private function isVerseIncludedInRange(int $chapter, int $verse, self $range): bool
    {
        // Check if verse is within range boundaries
        if ($chapter < $range->startChapter() || $chapter > $range->endChapter()) {
            return false;
        }

        if ($chapter === $range->startChapter() && $verse < $range->startVerse()) {
            return false;
        }

        if ($chapter === $range->endChapter() && $verse > $range->endVerse()) {
            return false;
        }

        // Check if verse is in any exclusion of this range
        foreach ($range->exclusions() as $exclusion) {
            if ($this->isVerseInRange($chapter, $verse, $exclusion)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if a verse is within a given range
     */
    private function isVerseInRange(int $chapter, int $verse, array $range): bool
    {
        // Check if verse is within the chapter range
        if ($chapter < $range['startChapter'] || $chapter > $range['endChapter']) {
            return false;
        }

        // Check verse boundaries for start and end chapters
        if ($chapter === $range['startChapter'] && $verse < $range['startVerse']) {
            return false;
        }

        if ($chapter === $range['endChapter'] && $verse > $range['endVerse']) {
            return false;
        }

        return true;
    }

    /**
     * Check if this range has at least N consecutive full chapters.
     * 
     * A chapter is considered "full" if all verses from 1 to the chapter's 
     * total verse count are included in the range (accounting for exclusions).
     * 
     * @param int $minimumCount Minimum number of consecutive full chapters required
     * @return bool True if at least N consecutive full chapters are found
     */
    public function hasConsecutiveChapters(int $minimumCount): bool
    {
        $validator = new ConsecutiveChapterValidator(
            $this->book,
            $this->startChapter,
            $this->endChapter,
            $this->startVerse,
            $this->endVerse,
            $this->exclusions
        );
        
        return $validator->hasConsecutiveChapters($minimumCount);
    }

} 