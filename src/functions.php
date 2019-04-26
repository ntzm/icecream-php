<?php

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

        $string = basename($caller['file']) . ':' . $caller['line'];

        if (isset($inside['class'])) {
            $class = strpos($inside['class'], 'class@anonymous') === 0 ? 'class@anonymous' : $inside['class'];
            $string .= ' in ' . $class . $inside['type'] . $inside['function'] . '()';
        } elseif (isset($inside['function'])) {
            $string .= ' in ' . $inside['function'] . '()';
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

    $functionNameIndex = null;
    $insideOpenBrace = false;
    $braceDepth = 0;
    $contents = [];

    foreach ($tokens as $i => $token) {
        if ($functionNameIndex === null) {
            if (is_array($token) && $token[0] === T_STRING && $token[2] === $caller['line'] && strtolower($token[1]) === 'ic') {
                $functionNameIndex = $i;
                continue;
            }
        } elseif (! $insideOpenBrace) {
            if ($token === '(') {
                $insideOpenBrace = true;
                continue;
            }
        } else {
            if ($token === '(') {
                ++$braceDepth;
            }

            if ($token === ')') {
                if ($braceDepth === 0) {
                    break;
                }

                --$braceDepth;
            }

            $contents[] = is_array($token) ? $token[1] : $token;
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
