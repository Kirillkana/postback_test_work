<?php

namespace frontend\controllers\api;

use common\models\User;
use mysqli;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\QueryParamAuth;
use yii\rest\ActiveController;
use yii\web\Response;

class StatisticsController extends ActiveController
{
    public $modelClass = 'frontend\models\Statistics';


    public function behaviors()
    {

        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBasicAuth::className(),
            'auth' => function ($username, $password){

                if ($user = User::find()->where(['username' => $username])->one() and
                $user->validatePassword($password)){
                    return $user;
                }
                return null;
            }
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' =>[
                [
                    'allow' =>true,
                    'roles' => ['@']
                ]
            ]
        ];

        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'getDataApi'];
        return  $actions; // TODO: Change the autogenerated stub
    }

    public  function  actionAdd(){
       // die("asd");
    }

    public function getDataApi(){
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;
        if ($requestParams['group_campaign_id'] == 1){
            if ($requestParams['campaign_id'] && ($requestParams['date1'] && $requestParams['date2'])){

                $query = $modelClass::find()
                    ->Where(['>=','time', $requestParams['date1']])
                    ->andWhere(['<=','time', $requestParams['date2']])
                    ->andWhere(['campaign_id'=> $requestParams['campaign_id']])
                    ->select([new \yii\db\Expression( "campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs, 
                    (round((SUM(IF(event = 'install', 1, 0))*100) / (SUM(IF(event = 'click', 1, 0))),2)) as CRi,
                    (round((SUM(IF(event = 'trial_started', 1, 0))*100) / (SUM(IF(event = 'install', 1, 0))),2)) as CRti")]);
            }else
                if ($requestParams['campaign_id'] && (!$requestParams['date1'] || !$requestParams['date2'])){
               //$query = $modelClass::findBySql("SELECT campaign_id, SUM(IF(event = 'trial', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs,
               // (round((SUM(IF(event = 'install', 1, 0))) / (SUM(IF(event = 'click', 1, 0))),2)) as CRti FROM statistics GROUP BY campaign_id");
                    $query = $modelClass::find()
                        ->Where(['campaign_id'=> $requestParams['campaign_id']])
                        ->select([new \yii\db\Expression( "campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs, 
                    (round((SUM(IF(event = 'install', 1, 0))*100) / (SUM(IF(event = 'click', 1, 0))),2)) as CRi,
                    (round((SUM(IF(event = 'trial_started', 1, 0))*100) / (SUM(IF(event = 'install', 1, 0))),2)) as CRti")]);
                }
                else{

                    $query = $modelClass::find()
                        ->select([new \yii\db\Expression( "campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs, 
                    (round((SUM(IF(event = 'install', 1, 0))*100) / (SUM(IF(event = 'click', 1, 0))),2)) as CRi,
                    (round((SUM(IF(event = 'trial_started', 1, 0))*100) / (SUM(IF(event = 'install', 1, 0))),2)) as CRti")])
                        ->groupBy('campaign_id');
                }
        }
        else{
            if ($requestParams['campaign_id'] && $requestParams['date1'] && $requestParams['date2']){
                $query = $modelClass::find()
                    ->Where(['campaign_id'=> $requestParams['campaign_id']])
                    ->andWhere(['>=','time', $requestParams['date1']])
                    ->andWhere(['<=','time', $requestParams['date2']])
                    ->select([new \yii\db\Expression( "campaign_id, cid")]);
            }
            else
                if ($requestParams['campaign_id'] && (!$requestParams['date1'] || !$requestParams['date2'])){
                    $query = $modelClass::find()
                        ->Where(['campaign_id'=> $requestParams['campaign_id']])
                        ->select([new \yii\db\Expression( "campaign_id, cid")]);
                }
                    else{
                        $query = $modelClass::find()
                            ->select([new \yii\db\Expression( "campaign_id, cid")]);
                    }
        }

        if (!empty($filter)) {
            $query->andWhere($filter);
        }

        return Yii::createObject([
            'class' => ActiveDataProvider::className(),
            'query' => $query,
            'pagination' => [
                'params' => $requestParams,
            ],
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }
}