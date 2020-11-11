<?php
namespace frontend\controllers;

use frontend\models\ResendVerificationEmailForm;
use frontend\models\SortForm;
use frontend\models\Statistics;
use frontend\models\Statistics2;
use frontend\models\VerifyEmailForm;
use mysqli;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAdd_statistics()
    {
        $action = "add_statistics";

        //if (!Yii::$app->user->isGuest) {
            // $get_params = Yii::$app->request->queryParams;
            $cid = Yii::$app->request->get('cid');

            $campaign_id = Yii::$app->request->get('campaign_id');
            $event_name = Yii::$app->request->get('event');
            $time = Yii::$app->request->get('time');
            $sub1 = Yii::$app->request->get('sub1');

            $message_error = null;

            $model_save = new Statistics();
            $model_save->cid = $cid;
            $model_save->campaign_id = $campaign_id;
            $model_save->event = $event_name;
            $model_save->time = time();
            $model_save->sub1 = $sub1;

            switch ($event_name) {
                case 'click':
                    // проверка уникального cid для click
                    $model = Statistics::find()->where([
                        'cid' => $cid,
                        'event' => $event_name
                        ])
                        ->one();
                    if ($model) {
                        $message_error = "Уникальный cid для события 'click' уже существует.";
                    }
                    else {

                        $model_save->save();
                    }
                    break;
                case 'trial_started':
                case 'install':
                    $model_save->save();
                    break;
                default:
                    $message_error = "Неверный запрос";
            }
      //  }
       // else{
         //   $message_error = "Добавление в БД под учетной записью гостя не разрешено!";
        //}

        return $this->render('index',
            [
                'message_error'=> $message_error,
                'action' => $action
            ]
        );
    }

    public function actionGet_statistics(){
        $action = "get_statistics";
        $model_camp_id = Statistics::find()->all();

        if (!Yii::$app->user->isGuest) {
            $mysqli = new mysqli("localhost", "root", "", "test_postback");
            $sort_form_model = new SortForm();
            $sort_form_model->group_at_campaign = 1;
            if($sort_form_model->load(Yii::$app->request->get())){
                if ($sort_form_model->group_at_campaign){
                  //  var_dump($sort_form_model->group_at_campaign);
                    if($sort_form_model->campaign_id && $sort_form_model->date1 && $sort_form_model->date2 ){
                        $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics
                        WHERE time >=" . strtotime($sort_form_model->date1) . " AND time <=" . strtotime($sort_form_model->date2) . " AND campaign_id =" . $sort_form_model->campaign_id . " GROUP BY campaign_id");
                    }
                    else
                        if ($sort_form_model->campaign_id && (!$sort_form_model->date1 || !$sort_form_model->date2)){
                            $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics WHERE campaign_id =   " . $sort_form_model->campaign_id );
                        }
                        else
                            if (!$sort_form_model->campaign_id && (!$sort_form_model->date1 || !$sort_form_model->date2)){
                                $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics GROUP BY campaign_id");
                            }
                            else
                                if (!$sort_form_model->campaign_id && ($sort_form_model->date1 && $sort_form_model->date2)){
                                    $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics
                                    WHERE time >=" . strtotime($sort_form_model->date1) . " AND time <=" . strtotime($sort_form_model->date2) . " GROUP BY campaign_id");
                                }
                }
                else{
                    if($sort_form_model->campaign_id && $sort_form_model->date1 && $sort_form_model->date2 ){
                        $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics
                        WHERE time >=" . strtotime($sort_form_model->date1) . " AND time <=" . strtotime($sort_form_model->date2) . " AND campaign_id =" . $sort_form_model->campaign_id );
                    }
                    else
                        if ($sort_form_model->campaign_id && (!$sort_form_model->date1 || !$sort_form_model->date2)){
                            $res = $mysqli->query("SELECT * FROM statistics WHERE campaign_id =   " . $sort_form_model->campaign_id );
                        }
                        else
                            if (!$sort_form_model->campaign_id && (!$sort_form_model->date1 || !$sort_form_model->date2)){
                                $res = $mysqli->query("SELECT * FROM statistics ");
                            }
                            else
                                if (!$sort_form_model->campaign_id && ($sort_form_model->date1 && $sort_form_model->date2)){
                                    $res = $mysqli->query("SELECT * FROM statistics
                                    WHERE time >=" . strtotime($sort_form_model->date1) . " AND time <=" . strtotime($sort_form_model->date2) );
                                }
                }
                //echo (date('d.m.y H:i:s',1604743746));
                //echo strtotime($sort_form_model->date2);

            }
            else{
                $res = $mysqli->query("SELECT campaign_id, SUM(IF(event = 'trial_started', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics  GROUP BY campaign_id");
            }
           // $sql = "SELECT campaign_id, SUM(IF(event = 'trial', 1, 0)) as trials, SUM(IF(event = 'click', 1, 0)) as clicks, SUM(IF(event = 'install', 1, 0)) as installs FROM statistics GROUP BY campaign_id";
           // $model = Statistics::findBySql($sql)->all();
        }
        else{
            $message_error = "Просмотр под учетной записью гостя не разрешен!";
        }

        return $this->render('index', compact('message_error', 'action', 'res', 'sort_form_model', 'model_camp_id'));
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }
}
