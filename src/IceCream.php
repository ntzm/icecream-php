<?php declare(strict_types=1);

namespace IceCream;

final class IceCream
{
    /** @var bool */
    private static $enabled = true;

    /** @var callable|null */
    private static $outputFunction;

    /** @var callable|string */
    private static $prefix = 'ic| ';

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function setOutputFunction(callable $function): void
    {
        self::$outputFunction = $function;
    }

    public static function resetOutputFunction(): void
    {
        self::$outputFunction = null;
    }

    public static function setPrefix($prefix): void
    {
        self::$prefix = $prefix;
    }

    /** @internal */
    public static function isDisabled(): bool
    {
        return ! self::$enabled;
    }

    /** @internal */
    public static function getOutputFunction(): callable
    {
        return self::$outputFunction ?? static function (string $output): void {
            echo self::getPrefix() . $output . PHP_EOL;
        };
    }

    /** @internal */
    public static function getPrefix(): string
    {
        return is_callable(self::$prefix) ? (self::$prefix)() : self::$prefix;
    }
}
