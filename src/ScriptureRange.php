<?php

declare(strict_types=1);

namespace BKuhl\BibleRanges;

use BKuhl\BibleRanges\Interfaces\BookInterface;
use BKuhl\BibleRanges\Interfaces\VerseInterface;

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
} 