<?php declare(strict_types=1);

namespace IceCreamTest;

use function IceCream\ic;
use function IceCream\ic as alias;
use IceCream\IceCream;
use PHPUnit\Framework\TestCase;

final class IcTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ob_start();

        IceCream::enable();
        IceCream::resetOutputFunction();
        IceCream::setPrefix('ic| ');
    }

    public function testBasic(): void
    {
        $output = ic('foo');

        $this->assertSame('foo', $output);
        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testMultiple(): void
    {
        $output = ic('foo', 'bar');

        $this->assertSame(['foo', 'bar'], $output);
        $this->assertSame("ic| 'foo', 'bar': foo, bar" . PHP_EOL, ob_get_clean());
    }

    public function testNotUse(): void
    {
        \IceCream\ic('foo');

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testAliased(): void
    {
        $this->markTestIncomplete();

        alias('foo');

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testArray(): void
    {
        ic([1, 2, 3]);

        $this->assertSame('ic| [1, 2, 3]: Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)
', ob_get_clean());
    }

    public function testFunctionCallOnDifferentLineToFirstArgument(): void
    {
        ic(
            'foo'
        );

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testMultipleOnDifferentLines(): void
    {
        ic(
            'foo',
            'bar',
            'baz'
        );

        $this->assertSame("ic| 'foo', 'bar', 'baz': foo, bar, baz" . PHP_EOL, ob_get_clean());
    }

    public function testCommentInBetweenFunctionNameAndOpeningBrace(): void
    {
        ic/**/('foo');

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testUsedIndirectly(): void
    {
        $this->markTestIncomplete();

        $function = 'IceCream\\ic';

        $function('foo');

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testNestedBraces(): void
    {
        ic(strtolower(strtolower(strtolower('a'))));

        $this->assertSame("ic| strtolower(strtolower(strtolower('a'))): a" . PHP_EOL, ob_get_clean());
    }

    public function testUsedInInternalCallback(): void
    {
        array_map(function ($a) {
            ic($a);
        }, [1]);

        $this->assertSame('ic| $a: 1' . PHP_EOL, ob_get_clean());
    }

    public function testMultipleOnOneLine(): void
    {
        $this->markTestIncomplete();

        ic('a'); ic('b');

        $this->assertSame("ic| 'a': a" . PHP_EOL . "ic| 'b': b" . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsInClass(): void
    {
        $output = ic(); $line = __LINE__;

        $this->assertNull($output);
        $this->assertSame('ic| IcTest.php:' . $line . ' in IceCreamTest\\IcTest->testWithoutArgumentsInClass()' . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsInClosure(): void
    {
        $ic = function () {
            ic(); return __LINE__;
        };

        $line = $ic();

        $this->assertSame('ic| IcTest.php:' . $line . ' in IceCreamTest\\IcTest->IceCreamTest\\{closure}()' . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsInAnonClass(): void
    {
        $c = new class {
            /** @var int */
            public $line;

            public function __construct()
            {
                ic(); $this->line = __LINE__;
            }
        };

        $this->assertSame('ic| IcTest.php:' . $c->line . ' in class@anonymous->__construct()' . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsInEval(): void
    {
        $this->markTestIncomplete();

        $line = eval('\\IceCream\\ic(); return __LINE__;');

        $this->assertSame('ic| IcTest.php:' . $line . ' in eval()' . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsTopLevel(): void
    {
        system('php ' . __DIR__ . '/top-level.php');

        $this->assertSame('ic| top-level.php:5' . PHP_EOL, ob_get_clean());
    }

    public function testWithoutArgumentsInFunction(): void
    {
        system('php ' . __DIR__ . '/in-function.php');

        $this->assertSame('ic| in-function.php:6 in foo()' . PHP_EOL, ob_get_clean());
    }

    public function testDifferentCase(): void
    {
        iC('foo');

        $this->assertSame("ic| 'foo': foo" . PHP_EOL, ob_get_clean());
    }

    public function testDoesNotOutputWhenDisabledWithoutArguments(): void
    {
        IceCream::disable();

        ic();

        $this->assertSame('', ob_get_clean());
    }

    public function testDoesNotOutputWhenDisabledWithArguments(): void
    {
        IceCream::disable();

        $result = ic('foo');

        $this->assertSame('', ob_get_clean());
        $this->assertSame('foo', $result);
    }

    public function testCustomOutputFunction(): void
    {
        IceCream::setOutputFunction(function (string $message): void {
            $this->assertSame("'foo': foo", $message);
        });

        ic('foo');

        ob_end_clean();
    }

    public function testPrefix(): void
    {
        IceCream::setPrefix('foo');

        $this->assertSame('foo', IceCream::getPrefix());

        ic('foo');

        $this->assertSame("foo'foo': foo" . PHP_EOL, ob_get_clean());
    }
}
