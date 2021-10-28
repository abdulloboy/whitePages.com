<?php
namespace app\components\ebilim;

use yii\base\BaseObject;
use yii\helpers\Url;
//use electrolinux\phpQuery;
//use PhpQuery\PhpQuery;
use app\components\distressedrealestate\ExternalData;
use app\models\Contact;

class WhitePages extends BaseObject
{
    public $aConnectionExcecption;
    public $aCustomCookies;
    public $bDebugMode=true;
    public $oExternalData;
    public $sCookieDirName;
    public $sCookiesDomain = "premium.whitepages.com";
    public $sCookieFileName;
    public $sURL="https://www.whitepages.com/";
    public $sPremURL="https://premium.whitepages.com/";
    
    public function __construct($param1 = NULL, $param2 = NULL, $config = [])
    {
        parent::__construct($config);
    }

    public function init(){
        parent::init();

        set_time_limit(0);
        clearstatcache();
        //date_default_timezone_set('UTC');
        
        $this->oExternalData=new ExternalData(NULL,NULL,
            [
                'bDebugMode' => $this->bDebugMode,
                'bUseCookies' => true,
                'bUseHTTPS' => true,
                'sCookieDirName' => $this->sCookieDirName,
                'sCookieFileName' => $this->sCookieFileName,
                'aCustomCookies' => $this->aCustomCookies,
                'sCookiesDomain' => $this->sCookiesDomain,
            ]);
        
        $this->oExternalData->aRequestHeaders =
            [
                'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language'   => 'uz,ru;q=0.8,en;q=0.5,en-US;q=0.3',
                'Accept-Encoding'   => 'gzip, deflate, br',
                'Connection'        => 'keep-alive',
                'Host'              => $this->sCookiesDomain,
                'Referer'           => $sURL . '/*',//\Yii::$app->request->getHostName() . "/*",
                'User-Agent'        => \Yii::$app->request->userAgent ? \Yii::$app->request->userAgent : $this->oExternalData->randomize_user_agent(),
            ];
    }

    public function parseContact($sHTMLPage){
        $aData=[];
        $oPhpQuery=\phpQuery::newDocument($sHTMLPage);
        $oHtmlPL=$oPhpQuery->find("p[class*='birth-date']");
        if(count($oHtmlPL))
            $aData['birthDate']=trim($oHtmlPL[0]->text());
        
        /*    
        $oHtmlDL=$oPhpQuery->find("div[id*='main_profile_home']");
        if(count($oHtmlDL)){
            $oHtmlAL=$oHtmlDL->find("address");
            if(count($oHtmlAL)){
                $oHtmlPL=$oHtmlAL->find("p");
                $sAddress2=trim($oHtmlPL->next()->text());
                $oHtmlPL->next()->empty();
                $aData['address'][]=trim($oHtmlPL->text()).
                    ' '.$sAddress2;
            }
        }*/
        $oHtmlDL=$oPhpQuery->find("div[id*='main_profile_properties']");
        if(count($oHtmlDL)){
            $oHtmlAL=$oHtmlDL->find("address");
            if(count($oHtmlAL))
                foreach($oHtmlAL as $oHtmlA){
                    $oHtmlPL=$oHtmlAL->find("p",$oHtmlA);
                    $sAddress2=trim($oHtmlPL->next()->text());
                    $oHtmlPL->next()->empty();
                    $aData['address'][]=trim($oHtmlPL->text()).
                        ' '.$sAddress2;
                }
        }
        print_r($aData);
        return $aData;
    }

    public function parsePage($sHTMLPage){
        $oPhpQuery=\phpQuery::newDocument($sHTMLPage);
        $oHtmlAL=$oPhpQuery->find("a[class*='person-result-link']");

        foreach($oHtmlAL as $oHtmlA){
            $aData=[];
            $oHtmlDiv=pq("div[class*='main-data']",$oHtmlA);
            $aData['age']=intval(trim(
                $oHtmlDiv->children()[0]->text(),", \t\n\r\0\x0B"));
            $oHtmlDiv->children()->empty();
            $aData['fullName']=trim($oHtmlDiv->text());
            $aData['URL']=$oHtmlA->attributes['href']->value;
            $aData['address'][]=trim($oHtmlDiv->next("div")->text());
            $aRetValue[]=$aData;
        }
        return $aRetValue;
    }

    public function request($sAddress,$iPageNumber=1){
        $aContacts=[];
        $sURL=Url::to($this->sPremURL.
            'results/address/?type=person_address_query&address='.
            urlencode($sAddress));
        //goto GOTO1;
        $oResponse=$this->oExternalData->request($sURL);
        if(!empty($s1=$this->oExternalData->sLastExceptionMessage)){
            $this->aConnectionExcecption[]=$s1;
            print_r($s1);
        }
        if($oResponse!==NULL) {
            $sHTMLPage=$this->oExternalData->
                parseResponse($oResponse,'HTML');
     /*       file_put_contents($this->sCookieDirName.
                'WhitePages.html',$sHTMLPage);
GOTO1:
            $sHTMLPage=file_get_contents($this->sCookieDirName.
                'WhitePages.html');*/
            $aContacts=$this->parsePage($sHTMLPage);
            $oContact=new Contact();
            if(count($aContacts))
            foreach($aContacts as $aContact){
                //$aContact2=$this->requestContact($aContact['URL']);
                //print_r($aContact2);
                $oContact->loadDefaultValues();
                $oContact->attributes=$aContact;
                $oContact->type=$aContact['@type'];
                $oContact->insert();
            }
        }
        return $aContacts;
    }

    public function requestContact($sURL_){
        $aContact=[];
        $sURL=$sURL_;
        //goto GOTO2;
        $oResponse=$this->oExternalData->request($sURL);
        if(!empty($s1=$this->oExternalData->sLastExceptionMessage)){
            $this->aConnectionExcecption[]=$s1;
            print_r($s1);
        }
        if($oResponse!==NULL) {
            $sHTMLPage=$this->oExternalData->
                parseResponse($oResponse,'HTML');
         /*   file_put_contents($this->sCookieDirName.
                'WPContact.html',$sHTMLPage);
GOTO2:
            $sHTMLPage=file_get_contents($this->sCookieDirName.
                'WPContact.html');*/
            $aContact=$this->parseContact($sHTMLPage);
        }
        return $aContact;
    }
}
?>