<?php

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

    public static function isDisabled(): bool
    {
        return ! self::$enabled;
    }

    public static function setOutputFunction(?callable $function): void
    {
        self::$outputFunction = $function;
    }

    public static function getOutputFunction(): callable
    {
        return self::$outputFunction ?? static function (string $output): void {
            echo self::getPrefix() . $output . PHP_EOL;
        };
    }

    public static function setPrefix($prefix): void
    {
        self::$prefix = $prefix;
    }

    public static function getPrefix(): string
    {
        return is_callable(self::$prefix) ? (self::$prefix)() : self::$prefix;
    }
}
