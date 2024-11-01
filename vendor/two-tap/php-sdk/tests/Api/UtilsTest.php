<?php

namespace TwoTap\Tests\Api;

use TwoTap\Tests\TestCase;

class UtilsTest extends TestCase
{

    /**
     * @test
     */
    function it_should_test_fields_input_true()
    {
        $fields = [
            "shipping_first_name" => "first name",
            "shipping_last_name" => "last name",
        ];
        $cartId = getenv('SAMPLE_CART_ID_DONE');

        $response = $this->twoTap->utils()->fieldsInputValidate($cartId, $fields);

        $this->assertEquals('done', $response->message);
        $this->assertEquals('Input is OK.', $response->description);
    }

    /**
     * @test
     */
    function it_should_test_fields_input_false()
    {
        $fields = [
            "shipping_first_name" => "a first name longer than 15 characters",
            "shipping_last_name" => "last name",
        ];
        $cartId = getenv('SAMPLE_CART_ID_DONE');

        $response = $this->twoTap->utils()->fieldsInputValidate($cartId, $fields);

        $this->assertEquals('bad_required_fields', $response->message);
        $this->assertEquals('Please enter a first name that has between 2 and 15 characters.', $response->description);
        $this->assertTrue(is_array($response->bad_field_keys));
        $this->assertEquals('shipping_first_name', $response->bad_field_keys[0]);
    }

    /**
     * @test
     */
    function it_should_test_a_failed_quicky()
    {
        $productUrls = [getenv('SAMPLE_PRODUCT_URL_1'), getenv('SAMPLE_PRODUCT_URL_1')];

        $response = $this->twoTap->utils()->quicky($productUrls, 'http://google.com/test', '2025550109');

        $this->assertEquals('failed', $response->message);
        $this->assertEquals('Please specify a public_token, a list of products, a sms_confirm_url, and a US phone number.', $response->description);
    }

    /**
     * @test
     */
    function it_should_return_the_supported_sites()
    {
        $response = $this->twoTap->utils()->supportedSites();

        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response[0]->id));
    }

    /**
     * @test
     */
    function it_should_return_the_coupons()
    {
        $response = $this->twoTap->utils()->coupons();

        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response[0]->site_id));
    }

}