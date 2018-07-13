<?php
/**
 * Created by PhpStorm.
 * User: caitong
 * Date: 2018/7/13
 * Time: 上午10:03
 */

namespace DI;

use PHPUnit\Framework\TestCase;

/**
 * Class DITest
 * @package DI
 * @covers DI
 */
class DITest extends TestCase
{
    private $di;

    public function __construct()
    {
        parent::__construct();
        $this->di = new DI();
    }

    public function testSimpleValue()
    {
        $key = 'simpleValue';
        $value = 'test';
        $this->di->bind($key, $value);
        $this->assertEquals($value, $this->di->get($key));
    }

    public function testEmptyValue ()
    {
        $key = 'nonvalue';
        $value = '';
        $this->di->bind($key, $value);
        $this->assertEquals($value, $this->di->get($key));
    }

    public function testInstance ()
    {
        $key = 'instance';
        $value = new \StdClass();
        $this->di->instance($key, $value);
        $this->assertEquals($value, $this->di->get($key));

        $di = new DI();
        $this->assertEquals($value, $di->get($key));
    }

    public function testAbstractAndImplement () {
        $abstract = A::class;
        $implement = B::class;
        $this->di->bind($abstract, $implement);
        $instance = $this->di->get($abstract);
        $this->assertEquals($instance->a(), 'a');
        $this->assertInstanceOf($implement, $instance);
    }

    public function testNotBind () {
        $key = microtime(true);
        try {
            $this->di->get($key);
            $this->fail('no exception throw');
        } catch (\DI\Exceptions\NotFoundException $e) {
            $this->assertTrue(true);
        }
    }

}

interface A {
    public function a ();
}

class B implements A {
    public function a () {
        return 'a';
    }
}
