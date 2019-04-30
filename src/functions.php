<?php declare(strict_types=1);

namespace IceCream;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_STRING;
use const T_WHITESPACE;
use const TOKEN_PARSE;
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
    $tokens = token_get_all($fileContent, TOKEN_PARSE);

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

    $braceDepth = 0;
    $bracketDepth = 0;
    $curlyDepth = 0;
    $contents = [''];
    $current = 0;

    // STEP 2: Find all the tokens between the opening brace and the closing brace
    // e.g. ic('foo')
    //         ^^^^^
    for ($i = $openBraceIndex + 1; $i < $tokenCount; ++$i) {
        $token = $tokens[$i];

        if ($token === '[') {
            ++$bracketDepth;
        }

        if ($token === ']') {
            ++$bracketDepth;
        }

        if ($token === '{') {
            ++$curlyDepth;
        }

        if ($token === '}') {
            ++$curlyDepth;
        }

        if ($token === '(') {
            ++$braceDepth;
        }

        if ($token === ')') {
            if ($braceDepth === 0) {
                break;
            }

            --$braceDepth;
        }

        if ($braceDepth === 0 && $bracketDepth === 0 && $curlyDepth === 0 && $token === ',') {
            ++$current;
            $contents[$current] = '';
            continue;
        }

        if (! is_array($token)) {
            $contents[$current] .= $token;
            continue;
        }

        $type = $token[0];

        if ($type === T_COMMENT || $type === T_DOC_COMMENT) {
            continue;
        }

        if ($type === T_WHITESPACE) {
            $contents[$current] .= ' ';
            continue;
        }

        $contents[$current] .= $token[1];
    }

    $strings = [];

    foreach ($contents as $i => $content) {
        $strings[] = trim($content) . ': ' . trim(print_r($values[$i], true));
    }

    $output(implode(', ', $strings));

    return count($values) === 1 ? $values[0] : $values;
}
