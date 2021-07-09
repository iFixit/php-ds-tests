<?php
namespace Ds\Tests;

use Ds\Collection;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;

abstract class CollectionTest extends TestCase
{
    /**
     * Sample sizes.
     */
    const MANY = 65;
    const SOME = 17;

    /**
     * Generic mixed value sample array.
     */
    public function sample()
    {
        return array_merge(
            [[]],                               // empty
            [['a']],                            // 1 value
            [['a', 'b']],                       // 2 values
            [['a', 'b', 'c']],                  // 3 values
            ['#', '1', 1, 1.0, true],           // == true
            ['',  '0', 0, 0.0, false, null],    // == false
            ['a', 'A', new \stdClass()],        // string cases, stdClass
            range(2, self::SOME)
        );
    }

    /**
     * @return array provides two equal values for each test.
     */
    public function basicDataProvider()
    {
        $values = [
            [],
            ['a'],
            ['a', 'b'],
            ['a', 'b', 'c'],
            $this->sample(),
        ];

        return array_map(function($a) { return [$a, $a]; }, $values);
    }

    /**
     * @return array a data provider for Sequence and Set to test out of range.
     */
    public function outOfRangeDataProvider()
    {
        return [
            [[ ], -1],
            [[ ],  1],
            [[1],  2],
            [[1], -1],
        ];
    }

    public function badIndexDataProvider()
    {
        return [
            [[], 'a'],
        ];
    }

    public function assertInstanceToString($instance)
    {
        $this->assertEquals("object(" . get_class($instance) . ')', "$instance");
    }

    public function assertToArray(array $expected, $instance)
    {
        $actual = $instance->toArray();

        // We have to make separate assertions here because PHPUnit considers an
        // array to be equal if the keys match the values even if the order is
        // not the same, ie. [a => 1, b => 2] equals [b => 2, a => 1].
        $this->assertEquals(array_values($expected), array_values($actual), "!!! ARRAY VALUE MISMATCH");
        $this->assertEquals(array_keys  ($expected), array_keys  ($actual), "!!! ARRAY KEY MISMATCH");
    }

    public function expectAccessByReferenceHasNoEffect()
    {
        static::expectNotice(Notice::class);
    }

    public function expectPropertyDoesNotExistException()
    {
        static::expectNotice(Notice::class);
    }

    public function expectReconstructionNotAllowedException()
    {
        static::expectException(\Error::class);
    }

    public function expectImmutableException()
    {
        static::expectException(\Error::class);
    }

    public function expectAccessByReferenceNotAllowedException()
    {
        static::expectException(\Error::class);
    }

    public function expectListNotSupportedException()
    {
        static::expectException(\Error::class);
    }

    public function expectIterateByReferenceException()
    {
        static::expectException(\Error::class);
    }

    public function expectWrongIndexTypeException()
    {
        static::expectException(\TypeError::class);
    }

    public function expectOutOfBoundsException()
    {
        static::expectException(\OutOfBoundsException::class);
    }

    public function expectArrayAccessUnsupportedException()
    {
        static::expectException(\Error::class);
    }

    public function expectKeyNotFoundException()
    {
        static::expectException(\OutOfBoundsException::class);
    }

    public function expectIndexOutOfRangeException()
    {
        static::expectException(\OutOfRangeException::class);
    }

    public function expectEmptyNotAllowedException()
    {
        static::expectException(\UnderflowException::class);
    }

    public function expectNotIterableOrArrayException()
    {
        static::expectException(\TypeError::class);
    }

    public function expectInternalIllegalOffset()
    {
        if (PHP_MAJOR_VERSION === 7) {
            static::expectException(Warning::class);
        } else {
            static::expectException(\TypeError::class);
        }
    }

    public function assertInstanceDump(array $expected, $instance)
    {
        $actual = $this->cleanVarDump($instance);

        $expected = $this->cleanVarDump($expected);
        $class = get_class($instance);
        $expected = preg_replace('/^Array/m', "$class Object", $expected);

        $this->assertEquals($expected, $actual);
    }

    public function assertSerialized(array $expected, $instance)
    {
        $unserialized = unserialize(serialize($instance));

        // Check that the instance is an instance of the correct class and that
        // its values reflect the original values.
        $this->assertEquals(get_class($instance), get_class($unserialized));
        $this->assertEquals($instance->toArray(), $unserialized->toArray());
        $this->assertTrue($instance !== $unserialized);
    }

    public function assertForEach(array $expected, $instance)
    {
        /**
         * foreach instance
         */
        $data = [];

        foreach ($instance as $key => $value) {
            $data[$key] = $value;
        }

        $this->assertEquals($expected, $data);

        /**
         * foreach on iterator
         */
        $data = [];

        foreach ($instance->getIterator() as $key => $value) {
            $data[$key] = $value;
        }

        $this->assertEquals($expected, $data);

        /**
         * foreach implicit
         *
         * @see https://github.com/php-ds/extension/issues/82
         */
        $producer = new Producer($this);
        $iterated = [];

        foreach ($producer->getInstance($expected) as $key => $value) {
            $iterated[$key] = $value;
        }

        $this->assertEquals($expected, $iterated);
    }

    public function assertForEachByReferenceThrowsException($instance)
    {
        $this->expectIterateByReferenceException();
        foreach ($instance as &$value);
    }

    /**
     * Perform a clean var dump disabling xdebug overload if set.
     *
     * @param mixed $expression
     */
    protected function cleanVarDump($expression)
    {
        $cli_color = ini_get('xdebug.cli_color');
        ini_set('xdebug.cli_color', 0);
        $dump = print_r($expression, true);
        ini_set('xdebug.cli_color', $cli_color);
        return $dump;
    }

    /**
     * @see https://github.com/php-ds/extension/issues/62
     */
    public function testConvertingToBoolean()
    {
        $instance = $this->getInstance();
        $this->assertTrue((bool) $instance);
    }
}

/**
 * @internal
 * @see assertForEach
 * @see https://github.com/php-ds/extension/issues/82
 */
class Producer {

    private $test;

    public function __construct($test) {
        $this->test = $test;
    }

    public function getInstance(array $values = null)
    {
        return $this->test->getInstance($values);
    }
}

