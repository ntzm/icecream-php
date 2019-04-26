<?php

namespace IceCream;

use RuntimeException;

final class UntraceableCall extends RuntimeException
{
    public static function couldNotOpen(string $path): self
    {
        return new self("Could not open {$path}");
    }

    public static function couldNotParse(string $path): self
    {
        return new self("Could not parse file at {$path}");
    }

    public static function couldNotReadContentsOfCall(string $path, int $line): self
    {
        return new self("Could not read contents of call at {$path}:{$line}");
    }
}
