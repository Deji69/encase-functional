<?php
namespace Tests\Functional;

use Encase\Tests\TestCase;
use function Encase\Functional\join;

class JoinTest extends TestCase
{
	public function testJoinArrayDefault()
	{
		$array = [1, 2, 3];
		$result = join($array);
		$this->assertSame('1,2,3', $result);
	}

	public function testJoinArrayWithSeparator()
	{
		$array = [1, 2, 3];
		$result = join($array, ', ');
		$this->assertSame('1, 2, 3', $result);
	}

	public function testJoinArrayWithSeparatorAndLastSeparator()
	{
		$array = [1, 2, 3];
		$result = join($array, ', ', ' and ');
		$this->assertSame('1, 2 and 3', $result);
	}

	public function testJoinArrayObject()
	{
		$obj = new \ArrayObject([1, 2, 3]);
		$result = join($obj);
		$this->assertSame('1,2,3', $result);
	}

	public function testJoinGenerator()
	{
		$result = join(self::generator());
		$this->assertSame('1,2,3', $result);
	}

	public function testJoinStdClass()
	{
		$obj = new \stdClass;
		$obj->a = 1;
		$obj->b = 2;
		$obj->c = 3;
		$result = join($obj);
		$this->assertSame('1,2,3', $result);
	}

	public static function generator()
	{
		for ($i = 1; $i <= 3; ++$i) {
			yield $i;
		}
	}
}
