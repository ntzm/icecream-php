# IceCream PHP

A PHP port of Python's [IceCream](https://github.com/gruns/icecream).

## Usage

```php
<?php

use function IceCream\ic;

function foo($i) {
    return $i + 333;
}

ic(foo(123));
// Outputs:
// ic| foo(123): 456

ic(1 + 5);
// Outputs:
// ic| 1 + 5: 6

function bar() {
    ic();
}
bar();
// Outputs:
// ic| example.php:18 in bar()
```

## Installation

```bash
$ composer require --dev ntzm/icecream
```

## Caveats

- You should only have one call to `ic` on one line, otherwise you will get malformed output
- All the arguments of `ic` should be on one line, e.g.
```php
// GOOD
ic('foo', 'bar', 'baz');
// BAD
ic(
    'foo',
    'bar',
    'baz'
);
```
- You should not alias the `ic` function
