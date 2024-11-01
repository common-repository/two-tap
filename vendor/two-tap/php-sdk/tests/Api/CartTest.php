<?php

namespace TwoTap\Tests\Api;

use TwoTap\Tests\TestCase;

class CartTest extends TestCase
{
    /**
     * @test
     */
    function it_tests_the_creation_of_a_cart()
    {
        $productUrls = [getenv('SAMPLE_PRODUCT_URL_1'), getenv('SAMPLE_PRODUCT_URL_1')];

        $response = $this->twoTap->cart()->create($productUrls);

        $this->assertTrue(isset($response->cart_id));
        $this->assertTrue(isset($response->message));
        $this->assertEquals('still_processing', $response->message);
        $this->assertTrue(isset($response->description));
    }

    /**
     * @test
     */
    function it_should_get_the_cart_status()
    {
        $productUrls = [getenv('SAMPLE_PRODUCT_URL_1'), getenv('SAMPLE_PRODUCT_URL_1')];
        $response = $this->twoTap->cart()->create($productUrls);

        $cartId = $response->cart_id;

        $response = $this->twoTap->cart()->status($cartId, null, null, 'Romania');
        $this->assertTrue(is_object($response->sites));
        $this->assertEquals('still_processing', $response->message);
        $this->assertEquals('Romania', $response->destination_country);
        $this->assertEquals($cartId, $response->cart_id);
    }

    /**
     * @test
     */
    function it_should_return_cart_estimates()
    {
        $productUrls = [getenv('SAMPLE_PRODUCT_URL_1'), getenv('SAMPLE_PRODUCT_URL_1')];

        $response = $this->twoTap->cart()->create($productUrls);

        $cartId = $response->cart_id;
        $response = $this->twoTap->cart()->status($cartId);

        $response = $this->twoTap->cart()->estimates($cartId);
        $this->assertEquals('failed', $response->message);
        $this->assertEquals("Cart request with id {$cartId} has not finished yet.", $response->description);
        sleep(10);
        $response = $this->twoTap->cart()->estimates($cartId);

        $this->assertTrue(is_object($response->estimates));
        $this->assertEquals('done', $response->message);
        $this->assertEquals('domestic', $response->destination);
    }
}