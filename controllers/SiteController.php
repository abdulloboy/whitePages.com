<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use app\components\ebilim\WhitePages;
use app\components\ebilim\WhitePagesExport;
use app\models\LoginForm;
use app\models\Contact;

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
                'only' => ['logout'],
                'rules' => [
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
     * @return string
     */
    public function actionIndex()
    {
        $model = new Contact();

        if (Yii::$app->request->isPost) {
            $model->csvFile=UploadedFile::
                getInstance($model,'csvFile');
            if($model->upload()) {
                $aCsv = array_map('str_getcsv', 
                    file('uploads/'.$model->csvFile->name));
                $sCookieDirName = Yii::getAlias('@app/fayl/');
                $sCookieFileName = 'WhitePagesCookies.json';
                $aWPCookies=$model->loadWhitePagesSession(
                    $sCookieDirName . $sCookieFileName);
                file_put_contents($sCookieDirName . $sCookieFileName,
                    $aWPCookies['json']);
                if($aWPCookies!=NULL){
                    $WP=new WhitePages(NULL,NULL,[
                        //'aCustomCookies' => $aWPCookies['array'],
                        'sCookieDirName' => $sCookieDirName,
                        'sCookieFileName' => $sCookieFileName,
                    ]);

                    $aContacts1=[];
                    Contact::deleteAll();
                    for($i1=0;$i1<1 && $i1<count($aCsv);$i1++){
                        $aContacts=$WP->request(
                            implode(' ',$aCsv[$i1]),$i1);
                        
                        $WP->oExternalData->oCookieStorage->save(
                            $sCookieDirName.$sCookieFileName);
                        $aWPCookies2=$model->loadWhitePagesSession(
                            $sCookieDirName.$sCookieFileName);
                        $Cookies1=json_decode($aWPCookies['json'],true);
                        $Cookies2=json_decode($aWPCookies2['json'],true);
                        print_r('<br>COOKIES DIFF ');
                        for($i2=0;$i2<min(count($Cookies1),
                            count($Cookies2));$i2++){
                            print_r(array_diff_assoc(
                                $Cookies1[$i2],$Cookies2[$i2]));
                        }
                        $aWPCookies=$aWPCookies2;


                        if(count($aContacts))
                            $aContacts1[]=$aContacts;
                        print_r(' Page-');
                        print_r($i1.' Count-');
                        print_r(count($aContacts).'<br>');
                        flush();  
                    }

                    foreach($aContacts1 as &$aContacts){
                        foreach($aContacts as &$aContact){
                            $aContact1=$WP->requestContact(
                                $aContact['URL']);

                            $WP->oExternalData->oCookieStorage->save(
                            $sCookieDirName.$sCookieFileName);
                            $aWPCookies2=$model->loadWhitePagesSession(
                                $sCookieDirName.$sCookieFileName);
                            $Cookies1=json_decode($aWPCookies['json'],true);
                            $Cookies2=json_decode($aWPCookies2['json'],true);
                            print_r('<br>COOKIES DIFF ');
                            for($i2=0;$i2<min(count($Cookies1),
                                count($Cookies2));$i2++){
                                $aDiff=array_diff_assoc(
                                    $Cookies1[$i2],$Cookies2[$i2]);
                                if(count($aDiff)){
                                    print_r($Cookies1[$i2]['Name'].
                                        ' '.$aDiff);
                                }
                            }
                            $aWPCookies=$aWPCookies2;

                            $aContact=array_merge($aContact,$aContact1);
                            print_r($aContact);
                            flush();
                            //break;
                        }
                    }
                             
                    print_r(count($aContacts1).'<br>');
                    print_r($aContacts1);
                    $WPExport=new WhitePagesExport();
                    $WPExport->generateReport(Yii::getAlias('@app/fayl/').
                        '/WhitePages.xlsx',$aContacts1,$aCsv);
                    return;
                    //return $this->redirect(['about']);
                }
                return;
            }
        }

        return $this->render('index',[
            'model' => $model,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new Contact();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('ContactSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
