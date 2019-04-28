<?php declare(strict_types=1);

namespace IceCream;

use ParseError;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_STRING;
use const T_WHITESPACE;
use const TOKEN_PARSE;
use function array_map;
use function basename;
use function count;
use function debug_backtrace;
use function end;
use function file_get_contents;
use function implode;
use function is_array;
use function print_r;
use function strpos;
use function strtolower;
use function token_get_all;
use function trim;

function ic(...$values) {
    if (IceCream::isDisabled()) {
        if ($values === []) {
            return null;
        }

        return count($values) === 1 ? $values[0] : $values;
    }

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0];
    $inside = $backtrace[1] ?? null;
    $output = IceCream::getOutputFunction();

    if ($values === []) {
        $string = basename($caller['file']) . ":{$caller['line']}";

        if (isset($inside['class'])) {
            $class = strpos($inside['class'], 'class@') === 0 ? 'class@anonymous' : $inside['class'];
            $string .= " in {$class}{$inside['type']}{$inside['function']}()";
        } elseif (isset($inside['function'])) {
            $string .= " in {$inside['function']}()";
        }

        $output($string);

        return null;
    }

    $fileContent = file_get_contents($caller['file']);

    if ($fileContent === false) {
        throw UntraceableCall::couldNotOpen($caller['file']);
    }

    try {
        $tokens = token_get_all($fileContent, TOKEN_PARSE);
    } catch (ParseError $e) {
        throw UntraceableCall::couldNotParse($caller['file']);
    }

    $tokenCount = count($tokens);
    $functionNameIndex = null;
    $functionUsageIndexes = [];

    // STEP 1: Find the function name
    // e.g. ic('foo')
    //      ^^
    // The first token will always be the opening tag, or HTML before the opening tag, so we can safely skip it
    for ($i = 1; $i < $tokenCount; ++$i) {
        $token = $tokens[$i];

        if (! is_array($token)) {
            continue;
        }

        if ($token[2] > $caller['line']) {
            $functionNameIndex = end($functionUsageIndexes);
            break;
        }

        if ($token[0] === T_STRING && strtolower($token[1]) === 'ic') {
            $functionUsageIndexes[] = $i;
        }
    }

    if ($functionNameIndex === null) {
        throw UntraceableCall::couldNotReadContentsOfCall($caller['file'], $caller['line']);
    }

    $openBraceIndex = null;

    // STEP 3: Find the function call opening brace
    // e.g. ic('foo')
    //        ^
    for ($i = $functionNameIndex + 1; $i < $tokenCount; ++$i) {
        if ($tokens[$i] === '(') {
            $openBraceIndex = $i;
            break;
        }
    }

    if ($openBraceIndex === null) {
        throw UntraceableCall::couldNotReadContentsOfCall($caller['file'], $caller['line']);
    }

    $braceDepth = 0;
    $contents = '';

    // STEP 2: Find all the tokens between the opening brace and the closing brace
    // e.g. ic('foo')
    //         ^^^^^
    for ($i = $openBraceIndex + 1; $i < $tokenCount; ++$i) {
        $token = $tokens[$i];

        if ($token === '(') {
            ++$braceDepth;
        }

        if ($token === ')') {
            if ($braceDepth === 0) {
                break;
            }

            --$braceDepth;
        }

        if (! is_array($token)) {
            $contents .= $token;
            continue;
        }

        $type = $token[0];

        if ($type === T_COMMENT || $type === T_DOC_COMMENT) {
            continue;
        }

        if ($type === T_WHITESPACE) {
            $contents .= ' ';
            continue;
        }

        $contents .= $token[1];
    }

    if ($contents === '') {
        throw UntraceableCall::couldNotReadContentsOfCall($caller['file'], $caller['line']);
    }

    $output(
        trim($contents) . ': ' . implode(
            ', ',
            array_map(static function ($value): string {
                return trim(print_r($value, true));
            }, $values)
        )
    );

    return count($values) === 1 ? $values[0] : $values;
}
