<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Contact is the model behind the contact form.
 */
class Contact extends ActiveRecord
{
    public $csvFile;
    //public $name;
    //public $URL;
    //public $email;
    //public $subject;
    //public $body;
    //public $verifyCode;

    public function __construct($param1 = NULL, $param2 = NULL, $config = [])
    {
        parent::__construct($config);
    }

    public function init(){
        parent::init();
    }

    public static function tableName(){
        return 'contact';
    }
    /**
     * @return array the validation rules.
     */
    
    public function rules(){
        $ar1=parent::rules();
        $ar1[]=[['csvFile'], 'file'];//, 'skipOnEmpty' => false, 'extensions' => 'csv'];
        $ar1[]=[['fullName','age','birthDate','name','givenName','familyName','additionalName','address','URL','type',],'safe'];
        return $ar1;
    }
    
    /**
     * @return array customized attribute labels
     */
    /*
    public function attributeLabels()
    {
        return [
            'verifyCode' => 'Verification Code',
        ];
    }
*/
    /**
     * Sends an email to the specified email address using the information collected by this model.
     * @param string $email the target email address
     * @return bool whether the model passes validation
     */
    public function contact($email)
    {
        if ($this->validate()) {
            Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo([$this->email => $this->name])
                ->setSubject($this->subject)
                ->setTextBody($this->body)
                ->send();

            return true;
        }
        return false;
    }

    public function loadCsvFile($csvFile){
    
    }

    public function loadWhitePagesSession($sCookieFileName)
    {
        $aRetValue = NULL;
        $aCookiesReady = NULL;

        if(file_exists($sCookieFileName))
        {            
            $sCookiesData = file_get_contents($sCookieFileName);

            if(is_string($sCookiesData) && !empty($sCookiesData))
            {                
                $aCookiesData = json_decode($sCookiesData, true);

                if(json_last_error() !== 0)
                {
                    $aRetValue = NULL;
                }
                else if(is_array($aCookiesData) && count($aCookiesData))
                {                    
                    $iCurrentTime = microtime(true);

                    foreach($aCookiesData as $iCookieKey => &$aCookieItem)
                    {   
                        $aKeys=array_keys($aCookieItem); 
                        array_walk($aKeys,function(&$v){
                            $v=ucfirst($v);
                        });
                        $aCookieItem=array_combine($aKeys,$aCookieItem);
                        $aCookiesReady[$aCookieItem['name']] = $aCookieItem['value'];
                    }

                    if(isset($aCookiesReady) && is_array($aCookiesReady) && count($aCookiesReady))
                    {
                        $aRetValue =
                            [
                                'array' => $aCookiesReady,
                                'json'  => json_encode($aCookiesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                            ];
                    }
                }
            }
        }

        return $aRetValue;
    }

    public function upload()
    {
        if ($this->validate()) {
            $this->csvFile->saveAs('uploads/'.
                $this->csvFile->baseName.'.'.$this->csvFile->extension);
            return true;
        } else {
            return false;
        }
    }

}
