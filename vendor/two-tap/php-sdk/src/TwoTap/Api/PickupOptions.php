<?php

namespace TwoTap\Api;

class PickupOptions
{

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function create($cartId = null, $fieldsInput = null, $products = null, $finishedUrl = null)
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

        if(!is_null($finishedUrl)){
            $body['finished_url'] = $finishedUrl;
        }

        return $this->client->post('pickup_options', $body);
    }

    public function status($cartId = null)
    {
        $params = [];

        if(!is_null($cartId)){
            $params['cart_id'] = $cartId;
        }

        return $this->client->get('pickup_options/status', $params);
    }
}
