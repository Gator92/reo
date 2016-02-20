<?php

use Reo\Collection\Vanilla;
use Reo\Collection\VanillaClassic;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testAccess()
    {
        $collection =  $this->getCollection($vals = array('bob' => 'white', 'jan' => 'blue'));

        //array access
        $this->assertEquals('white', $collection['bob']);
        //getter
        $this->assertEquals('blue', $collection->get('jan'));
        //magic getter
        $this->assertEquals('blue', $collection->jan);
        //countable
        $this->assertEquals(2, count($collection));
        //traversable
        $this->assertInstanceOf('\Traversable', $collection);
        //toArray
        $this->assertSame($vals, $collection->toArray());
    }

    public function testAdd()
    {
        $collection =  $this->getCollection($vals = array('sky' => 'blue', 'grass' => 'green'));
        $collection->add(array('grass' => 'blue', 'snow' => 'yellow'));
        $this->assertSame(array('sky' => 'blue', 'grass' => 'blue', 'snow' => 'yellow'), $collection->toArray());
    }

    private function getCollection($vals)
    {
        return PHP_MINOR_VERSION < 4 ? new VanillaClassic($vals) : new Vanilla($vals);
    }
}
