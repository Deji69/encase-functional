<?php
namespace Encase\Functional\Tests;

use Mockery as m;
use function Encase\Functional\each;

class EachTest extends TestCase
{
	/** @var \Mockery\MockInterface */
	protected $mock;

	protected function setUp(): void
	{
		parent::setUp();
		$this->mock = m::mock();
	}

	/**
	 * @dataProvider casesBasic
	 */
	public function testBasic($collection)
	{
		$mock = $this->mockCall($collection);
		$result = each($collection, [$mock, 'call']);
		$this->assertNull($result);
	}

	public function testClosure()
	{
		$values = ['a' => 1, 'b' => 2, 'c' => 3];
		$output = [];

		$fn = function ($value, $key, $collection) use (&$output, $values) {
			$output[$key] = $value;
			$this->assertSame($values, $collection);
		};

		$result = each($values, $fn);
		$this->assertSame($values, $output);
		$this->assertNull($result);
	}

	public function testEarlyExit()
	{
		$input = ['a', 'b', 'c', 'd'];
		$output = [];

		$result = each($input, function ($value) use (&$output) {
			$output[] = $value;

			if ($value == 'b') {
				return false;
			}
		});

		$this->assertFalse($result);
		$this->assertSame(['a', 'b'], $output);
	}

	public function testEarlyExitWithReturn()
	{
		$result = each([1, 2, 3], function ($value) {
			if ($value === 2) {
				return $value;
			}
		});
		$this->assertSame(2, $result);
	}

	public function testWithString()
	{
		$output = [];
		$input = 'hello';
		$expect = [
			['h', 0, 'hello'],
			['e', 1, 'hello'],
			['l', 2, 'hello'],
			['l', 3, 'hello'],
			['o', 4, 'hello'],
		];

		each($input, function ($value, $key, $string) use (&$output) {
			$output[] = [$value, $key, $string];
		});

		$this->assertSame($expect, $output);
	}

	public function testWithUnicodeString()
	{
		$output = '';
		$input = 'áëìȯũ';
		each($input, function ($value) use (&$output) {
			$output = $value.$output;
		});
		$this->assertSame('ũȯìëá', $output);
	}

	/**
	 * @dataProvider casesInvalidArgumentExceptions
	 */
	public function testTypeAssertions($value, $type)
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			"Argument 0 (\$iterable) of Encase\\Functional\\each expects iterable, stdClass, string or null, $type given"
		);
		each($value, function () {});
	}

	public function mockCall($collection)
	{
		$mock = m::mock();

		if (empty($collection)) {
			$mock->shouldNotReceive('call');
		} else {
			foreach ($collection as $key => $value) {
				$mock->expects('call')
				     ->with($value, $key, $collection)
				     ->once();
			}
		}
		return $mock;
	}

	public function casesBasic()
	{
		return [
			'With null' => [
				null
			],
			'With empty array' => [
				[]
			],
			'With array' => [
				['first', 'second', 'third']
			],
			'With associative array' => [
				['a' => 'first', 'b' => 'second', 'c' => 'third']
			],
			'With empty object' => [
				(object)[]
			],
			'With object' => [
				(object)['a' => 'first', 'b' => 'second', 'c' => 'third']
			],
			'With empty ArrayObject' => [
				new \ArrayObject([])
			],
			'With ArrayObject' => [
				new \ArrayObject(['a' => 'first', 'b' => 'second', 'c' => 'third'])
			],
			'With Iterator' => [
				(new \ArrayObject(['a' => 'first', 'b' => 'second', 'c' => 'third']))->getIterator()
			],
		];
	}

	public function casesInvalidArgumentExceptions()
	{
		return [
			'With zero number' => [
				'iterable' => 0,
				'type' => 'integer',
			],
			'With number' => [
				'iterable' => 3.14,
				'type' => 'double',
			],
			'With DateTime' => [
				'iterable' => new \DateTime(),
				'type' => 'DateTime',
			],
		];
	}
}