<?php

namespace TwoTap\Api;

class Cart
{

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function create($products = null, $finishedUrl = null, $finishedProductAttributesFormat = null, $notes = null, $testMode = null, $cacheTime = null, $destinationCountry = null)
    {

        $body = [];

        if(!is_null($products)){
            $body['products'] = $products;
        }

        if(!is_null($finishedUrl)){
            $body['finished_url'] = $finishedUrl;
        }

        if(!is_null($finishedProductAttributesFormat)){
            $body['finished_product_attributes_format'] = $finishedProductAttributesFormat;
        }

        if(!is_null($notes)){
            $body['notes'] = $notes;
        }

        if(!is_null($testMode)){
            $body['test_mode'] = $testMode;
        }

        if(!is_null($cacheTime)){
            $body['cache_time'] = $cacheTime;
        }

        if(!is_null($destinationCountry)){
            $body['destination_country'] = $destinationCountry;
        }

        return $this->client->post('cart', $body);
    }

    public function status($cartId = null, $productAttributesFormat = null, $testMode = null, $destinationCountry = null)
    {

        $params = [];

        if(!is_null($cartId)){
            $params['cart_id'] = $cartId;
        }

        if(!is_null($productAttributesFormat)){
            $params['product_attributes_format'] = $productAttributesFormat;
        }

        if(!is_null($testMode)){
            $params['test_mode'] = $testMode;
        }

        if(!is_null($destinationCountry)){
            $params['destination_country'] = $destinationCountry;
        }

        return $this->client->get('cart/status', $params);
    }

    public function estimates($cartId = null, $fieldsInput = null, $products = null, $destinationCountry = null)
    {

        $body = [];

        if(!is_null($cartId)){
            $body['cart_id'] = $cartId;
        }

        if(!is_null($fieldsInput)){
            $body['fields_input'] = $fieldsInput;
        }

        if(!is_null($products)){
            $body['products'] = $products;
        }

        if(!is_null($destinationCountry)){
            $body['destination_country'] = $destinationCountry;
        }

        return $this->client->post('cart/estimates', $body);
    }

    public function discounts($cart_id = null, $discounts = null)
    {

        $body = [];

        if(!is_null($cart_id)){
            $body['cart_id'] = $cart_id;
        }

        if(!is_null($discounts)){
            $body['discounts'] = $discounts;
        }

        return $this->client->post('cart/discounts', $body);
    }
}