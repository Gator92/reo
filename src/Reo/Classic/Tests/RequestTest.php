<?php

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Reo\Http\Request::hasQueryString
     */
    public function testHasQueryString()
    {
        $request = Reo_Classic_Request::create('/cheese');
        $this->assertFalse($request->hasQueryString());
        $request = Reo_Classic_Request::create('/cheese?flv=cheddar');
        $this->assertTrue($request->hasQueryString());
        $request->server['QUERY_STRING'] = null;
        $this->assertFalse($request->hasQueryString());
    }
}
