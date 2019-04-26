<?php declare(strict_types=1);

namespace IceCream;

use ParseError;

function ic() {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0];
    $inside = $backtrace[1] ?? null;
    $values = func_get_args();
    $output = IceCream::getOutputFunction();

    if ($values === []) {
        if (IceCream::isDisabled()) {
            return null;
        }

        $string = basename($caller['file']) . ":{$caller['line']}";

        if (isset($inside['class'])) {
            $class = strpos($inside['class'], 'class@anonymous') === 0 ? 'class@anonymous' : $inside['class'];
            $string .= " in {$class}{$inside['type']}{$inside['function']}()";
        } elseif (isset($inside['function'])) {
            $string .= " in {$inside['function']}()";
        }

        $output($string);

        return null;
    }

    $return = count($values) === 1 ? $values[0] : $values;

    if (IceCream::isDisabled()) {
        return $return;
    }

    $fileContent = file_get_contents($caller['file']);

    if ($fileContent === false) {
        throw UntraceableCall::couldNotOpen($caller['file']);
    }

    try {
        $tokens = token_get_all($fileContent, TOKEN_PARSE);
    } catch (ParseError $e) {
        throw UntraceableCall::couldNotOpen($caller['file']);
    }

    $tokenCount = count($tokens);
    $functionNameIndex = null;

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
            // We've overshot, let's go backwards now
            for (; $i >= 1; --$i) {
                $token = $tokens[$i];

                if (
                    $token[0] === T_STRING
                    && strtolower($token[1]) === 'ic'
                ) {
                    $functionNameIndex = $i;
                    break 2;
                }
            }
        }

        if (
            $token[0] === T_STRING
            && $token[2] === $caller['line']
            && strtolower($token[1]) === 'ic'
        ) {
            $functionNameIndex = $i;
            break;
        }
    }

    if ($functionNameIndex === null) {
        throw UntraceableCall::couldNotReadContentsOfCall($caller['file'], $caller['line']);
    }

    $openBraceIndex = null;

    // STEP 2: Find the function call opening brace
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
    $contents = [];

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

        if (is_array($token)) {
            if ($token[0] === T_WHITESPACE) {
                $contents[] = ' ';
            } else {
                $contents[] = $token[1];
            }
        } else {
            $contents[] = $token;
        }
    }

    if ($contents === []) {
        throw UntraceableCall::couldNotReadContentsOfCall($caller['file'], $caller['line']);
    }

    $string = trim(implode('', $contents)) . ': ';

    foreach ($values as $value) {
        $string .= trim(print_r($value, true));
    }

    $output($string);

    return $return;
}
