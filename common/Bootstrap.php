<?php


namespace common;


use common\services\MyClass2;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $container = \Yii::$container;
        $container->setSingleton(MyClass2::class, [], ['any_parameters']);
    }
}