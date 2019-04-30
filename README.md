# IceCream PHP

A PHP port of Python's [IceCream](https://github.com/gruns/icecream).

## Usage

```php
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

ic(foo(123), 1 + 5);
// Outputs:
// ic| foo(123): 456, 1 + 5: 6

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

## Configuration

If you want to disable the output, you can call `IceCream::disable()`.
If you want to re-enable the output, you can call `IceCream::enable()`.

If you want to change the prefix of the output, you can call `IceCream::setPrefix('myPrefix: ')` (by default the prefix is `ic| `).

If you want to change how the result is outputted, you can call `IceCream::setOutputFunction()`.
For example, if you want to log your messages to a file:
```php
IceCream::setOutputFunction(function (string $message): void {
    file_put_contents('log.txt', $message . PHP_EOL, FILE_APPEND);
});
```
You can reset to the default output function by calling `IceCream::resetOutputFunction()`.

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
