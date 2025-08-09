# Scripture Ranges

A PHP library for handling scripture ranges with exclusions and JSON serialization. This package provides a clean, object-oriented interface for working with Bible verse ranges, checking if verses are within ranges, and managing exclusions. It uses interfaces for `Book` and `Verse` objects, allowing you to integrate with your own Bible data sources.

## Installation

```bash
composer require bkuhl/scripture-ranges
```

## Quick Start

```php
use BKuhl\ScriptureRanges\ScriptureRange;
use BKuhl\ScriptureRanges\RangeCollection;
use BKuhl\ScriptureRanges\Interfaces\BookInterface;
use BKuhl\ScriptureRanges\Interfaces\VerseInterface;

// Simple implementations for demonstration
class GenesisBook implements BookInterface
{
    public function name(): string 
    { 
        return 'Genesis'; 
    }
    
    public function position(): int 
    { 
        return 1; 
    }
    
    public function chapterVerseCount(int $chapter): int 
    {
        return [1 => 31, 2 => 25, 3 => 24][$chapter] ?? 30;
    }
}

class LukeBook implements BookInterface
{
    public function name(): string 
    { 
        return 'Luke'; 
    }
    
    public function position(): int 
    { 
        return 42; 
    }
    
    public function chapterVerseCount(int $chapter): int 
    {
        return [1 => 80, 2 => 52][$chapter] ?? 30;
    }
}

class SimpleVerse implements VerseInterface
{
    public function number(): int 
    { 
        return 5; 
    }
    
    public function chapterNumber(): int 
    { 
        return 2; 
    }
    
    public function book(): BookInterface 
    { 
        return new GenesisBook(); 
    }
}

// Create a book and range
$genesis = new GenesisBook();
$range = new ScriptureRange($genesis, 1, 3, 1, 15);

// Check if a verse is in the range
$verse = new SimpleVerse();
echo $range->contains($verse); // true

// Get the reference (concise format)
echo $range->reference(); // "Genesis 1-3:15"
```

## Examples

### Basic Range Operations

```php
// Create a range with exclusions
$range = new ScriptureRange($genesis, 1, 3, 1, 31);
$range->addExclusion(2, 2, 4, 7); // Exclude Genesis 2:4-7

// Check verse containment
$verse1 = new SimpleVerse();
$verse2 = new SimpleVerse();

echo $range->contains($verse1); // true
echo $range->contains($verse2); // false (excluded)
```

### Range Collections

```php
$collection = new RangeCollection();

// Add ranges from different books
$genesisRange = new ScriptureRange($genesis, 1, 3, 1, 15);
$luke = new LukeBook();
$lukeRange = new ScriptureRange($luke, 1, 1, 1, 10);

$collection->addRange($genesisRange);
$collection->addRange($lukeRange);

// Check if a verse is in any range in the collection
$verse = new SimpleVerse();
echo $collection->contains($verse); // true

// Get a concise reference for the entire collection
echo $collection->reference(); // "Genesis 1-3:15, Luke 1:10"
```

### JSON Serialization

```php
// Serialize a range to JSON
$range = new ScriptureRange($genesis, 1, 3, 1, 15);
$range->addExclusion(2, 2, 4, 7);

$json = json_encode($range->toArray(), JSON_PRETTY_PRINT);
echo $json;
// Output:
// {
//     "start": {
//         "book": 1,
//         "chapter": 1
//     },
//     "end": {
//         "book": 1,
//         "chapter": 3,
//         "verse": 15
//     },
//     "exclude": [
//         {
//             "start": {
//                 "chapter": 2,
//                 "verse": 4
//             },
//             "end": {
//                 "chapter": 2,
//                 "verse": 7
//             }
//         }
//     ]
// }

// Serialize a collection
$collection = new RangeCollection();
$collection->addRange($range);
$json = $collection->toJson();
```

## API Reference

### BookInterface

```php
interface BookInterface
{
    public function name(): string;
    public function position(): int;
    public function chapterVerseCount(int $chapter): int;
}
```

### VerseInterface

```php
interface VerseInterface
{
    public function number(): int;
    public function chapterNumber(): int;
    public function book(): BookInterface;
}
```

## JSON Format

The JSON format uses a clean, compact structure:

```json
[
    {
        "start": {
            "book": 1,
            "chapter": 1
        },
        "end": {
            "book": 1,
            "chapter": 3,
            "verse": 15
        },
        "exclude": [
            {
                "start": {
                    "chapter": 2,
                    "verse": 4
                },
                "end": {
                    "chapter": 2,
                    "verse": 7
                }
            }
        ]
    }
]
```
