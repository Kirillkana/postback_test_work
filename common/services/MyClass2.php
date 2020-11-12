<?php

namespace common\services;

class MyClass2
{
    public $param;

    function getParam(){
        return $this->param;
    }

    public function __construct($param)
    {
        $this->param = $param;
    }
}