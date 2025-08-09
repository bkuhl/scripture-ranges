# Scripture Ranges

A PHP library for handling scripture ranges with exclusions and JSON serialization. Create complex scripture ranges with a clean, fluent API.

## Installation

```bash
composer require bkuhl/scripture-ranges
```

## Creating Ranges (Recommended)

Use the `ScriptureRangeBuilder` for a clean, readable API:

```php
use BKuhl\ScriptureRanges\ScriptureRangeBuilder;

// Create multiple ranges with exclusions
$builder = new ScriptureRangeBuilder([$myBookResolver]);
$collection = $builder
    ->with(BookEnum::JOHN, chapter: 3, verse: 1, toVerse: 36)
    ->without(BookEnum::JOHN, chapter: 3, verse: 16, toVerse: 17)
    ->without(BookEnum::JOHN, chapter: 3, verse: 22)
    ->with('Matthew', chapter: 5, verse: 1, toVerse: 48)     // String book
    ->without('Matthew', chapter: 5, verse: 10, toVerse: 15)
    ->with(42, chapter: 2, verse: 8, toVerse: 20)           // Book by position
    ->build(); // Returns RangeCollection

// Single range with defaults
$collection = $builder
    ->with('John', chapter: 3)                              // Verse 1 to end of chapter
    ->build();

// Specific verse range
$collection = $builder
    ->with($johnBook, chapter: 3, verse: 16, toVerse: 17)
    ->build();
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
// Check if a verse is in any range
echo $collection->contains($verse); // true/false

// Get formatted reference
echo $collection->reference(); // "John 3:16-17, Matthew 5:1-12"

// JSON serialization
$json = $collection->toJson();
```
