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

- You should not call `ic` more than once per line, otherwise you will get incorrect output
```php
// Don't do this
ic('foo'); ic('bar');
```
- You should not alias the `ic` function
```php
// Don't do this
use function IceCream\ic as debug;
debug();
```
- You should not use the `ic` function dynamically
```php
// Don't do this
$fn = 'IceCream\ic';
$fn();
```
