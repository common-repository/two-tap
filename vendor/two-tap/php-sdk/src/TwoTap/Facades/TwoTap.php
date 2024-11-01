<?php

namespace TwoTap\Facades;

use Illuminate\Support\Facades\Facade;

class TwoTap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'twotap';
    }
}