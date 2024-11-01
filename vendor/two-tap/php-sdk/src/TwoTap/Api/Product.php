<?php

namespace TwoTap\Api;

class Product
{

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function get($siteId = null, $md5 = null, $destinationCountry = null, $attributesFormat = null)
    {
        $params = [];

        if(!is_null($siteId)){
            $params['site_id'] = $siteId;
        }

        if(!is_null($md5)){
            $params['product_md5'] = $md5;
        }

        if(!is_null($destinationCountry)){
            $params['destination_country'] = $destinationCountry;
        }

        if(!is_null($attributesFormat)){
            $params['attributes_format'] = $attributesFormat;
        }

        return $this->client->get('product', $params);
    }

    public function search($filter = [], $sort = null, $page = null, $perPage = null, $productAttributesFormat = null, $destinationCountry = null)
    {
        $body = [];

        if(count($filter) > 0){
            $body["filter"] = $filter;
        }

        if(!is_null($page)){
            $body['page'] = $page;
        }

        if(!is_null($perPage)){
            $body['per_page'] = $perPage;
        }

        if(!is_null($sort)){
            $body['sort'] = $sort;
        }

        if(!is_null($destinationCountry)){
            $body['destination_country'] = $destinationCountry;
        }

        if(!is_null($productAttributesFormat)){
            $body['product_attributes_format'] = $productAttributesFormat;
        }

        return $this->client->post('product/search', $body);
    }

    public function scroll($filter = null, $size = null, $scrollId = null, $productAttributesFormat = null, $destinationCountry = null)
    {
        $body = [];

        if(!is_null($filter)){
            $body["filter"] = $filter;
        }

        if(!is_null($size)){
            $body['size'] = $size;
        }

        if(!is_null($scrollId)){
            $body['scroll_id'] = $scrollId;
        }

        if(!is_null($destinationCountry)){
            $body['destination_country'] = $destinationCountry;
        }

        if(!is_null($productAttributesFormat)){
            $body['product_attributes_format'] = $productAttributesFormat;
        }

        return $this->client->post('product/scroll', $body);
    }

    public function filters($filter = null)
    {
        $body = [];

        if(!is_null($filter)){
            $body["filter"] = $filter;
        }

        return $this->client->post('product/filters', $body);
    }

    public function taxonomy()
    {
        return $this->client->get('product/taxonomy');
    }
}