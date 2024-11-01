<?php

namespace TwoTap;

use League\Container\ServiceProvider\AbstractServiceProvider;

class TwoTapServiceProviderLeague extends AbstractServiceProvider
{
    /**
     * @var array $config
     */
    protected $config;
    /**
     * @var array $provides
     */
    protected $provides = [
        'TwoTap\Api'
    ];
    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->config = $config;
    }
    /**
     * Register the server provider.
     *
     * @return void
     */
    public function register()
    {
        $this->getContainer()->share('TwoTap\Api', function () {
            return new Api($this->config);
        });
    }
}