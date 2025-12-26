# Scripture Ranges

A PHP library for handling scripture ranges with exclusions and JSON serialization. Create complex scripture ranges with a clean, fluent API.

## Installation

```bash
composer require bkuhl/scripture-ranges
```

## Creating Ranges (Recommended)

Use the `ScriptureRangeBuilder` with multiple syntax options:

```php
use BKuhl\ScriptureRanges\ScriptureRangeBuilder;
use BKuhl\ScriptureRanges\ChapterRange;

$builder = new ScriptureRangeBuilder([$myBookResolver]);

// 1. Traditional parameter syntax
$collection = $builder
    ->with(BookEnum::JOHN, chapter: 3, verse: 1, toVerse: 36)
    ->without(BookEnum::JOHN, chapter: 3, verse: 16, toVerse: 17)
    ->build();

// 2. Chapter range syntax
$collection = $builder
    ->with(BookEnum::JOHN, chapter: 3, chapterEnd: 5)      // Multiple chapters
    ->with('Matthew', chapter: 5, verse: 1, toVerse: 48)   // Mixed with verse ranges
    ->build();

// 3. ChapterRange object syntax (cleanest for chapter ranges!)
$collection = $builder
    ->with(BookEnum::JOHN, ChapterRange::range(3, 5))      // Chapters 3-5
    ->with('Matthew', chapter: 5, verse: 1, toVerse: 48)   // Use traditional syntax for verses
    ->without(BookEnum::JOHN, chapter: 3, verse: 16)       // Use traditional syntax for single verses
    ->build();

// ChapterRange factory method:
ChapterRange::range(3, 5)             // Chapters 3-5 (full chapters)
```

## Book Resolvers

To use flexible input types (strings, enums, integers), implement a `BookResolverInterface`:

```php
use BKuhl\ScriptureRanges\Interfaces\BookResolverInterface;

class MyBookResolver implements BookResolverInterface
{
    public function resolve(mixed $book): BookInterface
    {
        if (is_string($book)) {
            return $this->getBookFromString($book);
        }
        
        if ($book instanceof MyBookEnum) {
            return $this->getBookFromEnum($book);
        }
        
        throw new InvalidArgumentException('Unable to resolve book');
    }

    public function canResolve(mixed $book): bool
    {
        return is_string($book) || $book instanceof MyBookEnum;
    }
}

// Use with builder
$builder = new ScriptureRangeBuilder([$myResolver]);
```

## Working with Collections

```php
// Create a collection with ID and name
$collection = new RangeCollection('reading-plan-123', 'Daily Reading');

// Or create without metadata
$collection = new RangeCollection();

// Set or update name and ID
$collection->setName('Morning Reading');
$collection->setId('morning-plan-456');

// Get name and ID
echo $collection->getName(); // "Morning Reading"
echo $collection->getId();   // "morning-plan-456"

// Check if a verse is in any range
echo $collection->contains($verse); // true/false

// Get formatted reference
echo $collection->reference(); // "John 3:16-17, Matthew 5:1-12"

// JSON serialization includes name and ID
$json = $collection->toJson();
// {
//   "name": "Morning Reading",
//   "id": "morning-plan-456", 
//   "ranges": [...]
// }
```

## ChapterRange Quick Example

For working with chapter ranges, use the `ChapterRange` class:

```php
use BKuhl\ScriptureRanges\ChapterRange;

// Create a chapter range
$range = ChapterRange::range(3, 5);  // Chapters 3-5

// Get start and end chapters
$start = $range->getStart();  // 3
$end = $range->getEnd();      // 5

// Use with ScriptureRangeBuilder
$collection = $builder
    ->with($book, ChapterRange::range(1, 3))    // Full chapters 1-3
    ->with($book, chapter: 4, verse: 1, toVerse: 10)  // Specific verses
    ->build();
```

## Combining Ranges

Combine multiple ranges in the same book into a single range:

```php
use BKuhl\ScriptureRanges\ScriptureRange;

$range1 = new ScriptureRange($book, 1, 2, 1, 25);
$range2 = new ScriptureRange($book, 3, 4, 1, 24);

$combined = ScriptureRange::combine([$range1, $range2]);
// Result: Genesis 1-4 with gaps between ranges excluded
```

## Checking Consecutive Chapters

Check if ranges contain a minimum number of consecutive full chapters:

```php
// Check a single range
$range = new ScriptureRange($book, 1, 3, 1, 24);
$range->hasConsecutiveChapters(3); // true if chapters 1, 2, 3 are all full

// Check across multiple ranges in a collection
$collection = (new ScriptureRangeBuilder())
    ->with($book, chapter: 1, chapterEnd: 2)
    ->with($book, chapter: 3, chapterEnd: 4)
    ->build();

$collection->hasConsecutiveChapters(4); // true if chapters 1-4 are all full across ranges
```
