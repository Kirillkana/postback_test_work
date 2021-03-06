<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log','common\Bootstrap'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [

        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' =>true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                'add_statistics/postback.php' => 'site/add_statistics',
                'get_statistics/postback.php' => 'site/get_statistics',
                '<controller:\w+>/<action:\w+>/' => '<controller>/<action>',
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'api/statistics',
                    'pluralize' => false
                ],
                'api/<controller:\w+>/postback'=> 'api/<controller>',
                'api/<controller:\w+>/<action:\w+>' => 'api/<controller>/<action>'
            ],
        ]
    ],
    'params' => $params,
];
