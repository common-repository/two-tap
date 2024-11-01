<?php

namespace TwoTap\Tests;

use TwoTap\Tests\TestCase;
use TwoTap\Api;

class ApiTest extends TestCase
{
    /**
     * @test
     */
    public function instatiating_an_app_is_ok()
    {
        $this->assertEquals('v1.0', $this->twoTap->apiVersion);
    }

    /**
     * @test
     */
    public function it_should_return_a_product_instance()
    {
        $this->assertInstanceOf('TwoTap\API\Product', $this->twoTap->product());
    }
}