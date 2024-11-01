<?php

namespace TwoTap;

class TwoTapResponse
{

    function __construct($response, $responseFormat = 'object')
    {
        $this->response = (string)$response;
        $this->responseFormat = $responseFormat;
    }

    public function render()
    {
        switch ($this->responseFormat) {
            case 'object':
                return json_decode($this->response, false);
                break;
            case 'array':
                return json_decode($this->response, true);
                break;
            case 'string':
                return (string)$this->response;
                break;
            default:
                return (string)$this->response;
                break;
        }
    }
}