<?php

namespace TwoTap\Tests;

use TwoTap\Api;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TwoTap instance
     */
    protected $twoTap;

    /**
     * @before
     */
    public function before()
    {
        $this->twoTap = new Api([
            'public_token' => getenv('TWOTAP_PUBLIC_TOKEN'),
            'private_token' => getenv('TWOTAP_PRIVATE_TOKEN'),
        ]);
    }

}