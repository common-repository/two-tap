<?php

namespace TwoTap\Tests\Api;

use TwoTap\Tests\TestCase;

class ProductTest extends TestCase
{
    /**
     * @test
     */
    function it_shoud_return_a_product()
    {
        $response = $this->twoTap->product()->get(getenv('SAMPLE_PRODUCT_SITE_ID'), getenv('SAMPLE_PRODUCT_MD5'));
        $product = $response->product;

        $this->assertEquals('done', $response->message);
        $this->assertTrue(isset($product->md5));
    }

    /**
     * @test
     */
    function it_should_return_a_simple_search()
    {
        $filter = [
          'keywords' => 'apple watch',
        ];

        $response = $this->twoTap->product()->search($filter);

        $this->assertEquals('done', $response->message);
        $this->assertTrue(isset($response->total));
        $this->assertTrue(isset($response->page));
        $this->assertTrue(isset($response->per_page));
        $this->assertTrue(is_array($response->products));
        // dump($response);
    }

    /**
     * @test
     */
    function it_should_test_different_filters()
    {
        $filter = [];
        $response = $this->twoTap->product()->search($filter, null, 1, 20, '', 'Romania');

        $this->assertEquals('done', $response->message);
        $this->assertTrue(isset($response->total));
        $this->assertTrue(isset($response->page));
        $this->assertTrue(isset($response->per_page));
        $this->assertEquals(20, $response->per_page);
        $this->assertTrue(is_array($response->products));
        // $this->assertEquals($response->products));
    }

    /**
     * @test
     */
    function it_should_test_scroll_method()
    {
        $response = $this->twoTap->product()->scroll();

        $this->assertEquals('done', $response->message);
        $this->assertTrue(isset($response->total));
        $this->assertTrue(is_array($response->products));
        $this->assertTrue(isset($response->scroll_id));
    }

    /**
     * @test
     */
    function it_should_test_filters_method()
    {

        $response = $this->twoTap->product()->filters();

        $this->assertEquals('done', $response->message);
        $this->assertTrue(is_array($response->categories));
        $this->assertTrue(is_array($response->genders));
        $this->assertTrue(is_array($response->sizes));
        $this->assertTrue(is_array($response->site_ids));
        $this->assertTrue(is_array($response->promotions));
    }
}