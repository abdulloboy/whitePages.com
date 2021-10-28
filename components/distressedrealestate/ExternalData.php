<?php
/**
 *
 * Created by PhpStorm.
 * User: Rustam
 * Date: 24.05.2019
 * Time: 08:35
 *
 * Функции для работы с внешними данными
 *
 * пример вызова в методах контроллера:
 *   $this->oExternalData = new ExternalData();
 *   $this->oExternalData->bDebugMode = $this->bDebugMode;
 *
 * пример вызова в методах контроллера с использованием Cookie:
 *
 *
 * $this->aCustomCookies    =>
 * [
 *      'user_id'                   => '987654321',
 *      'spokeo_sessions_rails4'    => 'aF123ASFDas1223FDASAsd12312SDFdsf',
 * ],
 *
 * ВАЖНО! 'bUseCookies' => true
 * $this->oExternalData = new ExternalData
 * (
 *      NULL,
 *      NULL,
 *      [
 *          'bUseCookies'       => true,
 *          'bDebugMode'        => $this->bDebugMode,
 *          'sCookieDirName'    => $this->sCookieDirName,
 *          'sCookieFileName'   => $this->sCookieFileName,
 *          'aCustomCookies'    => $this->aCustomCookies,
 *          'sCookiesDomain'    => $this->sSpokeoURLs['spokeo']
 *      ]
 * );
 *
 * Пример Запроса:
 *
 * $sParams =
 * [
 *      'address'           => $sAddress,
 *      // 'components'        => 'components=administrative_area:NY|country:US',
 *      'language'          => 'en',
 *      'key'               => \Yii::$app->params['GoogleAPIKey']
 * ];
 *
 * $sURL = "maps.googleapis.com/maps/api/geocode/json?" . http_build_query($sParams);
 *
 * $oResponse = $this->oExternalData->request($sURL);
 *
 *
 *
 *
 * Пример запроса с POST данными:
 *
 * $aParams =
 * [
 *      'varQuery' => $this->prepareQuery($iPageNum, $sDatePeriod),
 * ];
 *
 * $oResponse = $this->oExternalData->request($this->sDignityMemorialURLs['obituariesSearch'], 'POST', ['json'  => $aParams]);
 *
 * private function prepareQuery($iPageNum = 0, $sDatePeriod = NULL)
 * {
 *     $sQuery = NULL;
 *
 *     $sDateFormat = "Y-m-d\T00:00:00\Z";
 *
 *     if(isset($sDatePeriod) && !empty($sDatePeriod) && is_string($sDatePeriod))
 *     {
 *         switch($sDatePeriod)
 *         {
 *             case    'last24hours':
 *                     $date = new \DateTime('-1 days');
 *                     $sControlDate = $date->format($sDateFormat);
 *                     $sQuery = "q=(and (and '') (or locationstate:'NY')   (or cmicreationdate:['{$sControlDate}',}))&start={$iPageNum}&size=10&filtergroup=cmicreationdate&filtervalue=['{$sControlDate}',}&filterchecked=true";
 *                     break;
 *
 *             case    'last7days':
 *                     $date = new \DateTime('-7 days');
 *                     $sControlDate = $date->format($sDateFormat);
 *                     $sQuery = "q=(and (and '') (or locationstate:'NY')   (or cmicreationdate:['{$sControlDate}',}))&start={$iPageNum}&size=10&filtergroup=cmicreationdate&filtervalue=['{$sControlDate}',}&filterchecked=true";
 *                     break;
 *
 *          }
 *     }
 *
 *     return $sQuery;
 * }
 *
 *
 *
 * Пример обработки ответа:
 *
 * $aRetValue = $this->oExternalData->parseResponse($oResponse, 'HTML');
 * $aRetValue = $this->oExternalData->parseResponse($oResponse, 'JSON');
 *
 *
 */

namespace app\components\distressedrealestate;

use yii\base\BaseObject;
use yii\helpers\HtmlPurifier;
// use app\components\distressedrealestate\misc;


/**
 * @noinspection    PhpUndefinedClassInspection
 * @property        \GuzzleHttp\Client                  $oGuzzleClient
 * @property        array                               $aGuzzleConfig
 * @property        array                               $aRequestHeaders
 * @property        string                              $sUserAgent
 * @property        integer                             $iLastStatusCode
 * @property        boolean                             $bSSLVerification
 * @property        boolean                             $bUseHTTPS
 * @property        boolean                             $bDebugMode
 * @property        boolean                             $bUseCookies
 * @property        string                              $sCookieDirName
 * @property        string                              $sCookieFileName
 * @property        array                               $aCustomCookies
 * @property        string                              $sCookiesDomain
 * @property        \GuzzleHttp\Cookie\FileCookieJar    $oCookieStorage
 * @property        string                              $sDefaultOrigin
 * @property        string                              $sDefaultReferer
 * @property        string                              $sDebugDirName
 * @property        string                              $sDebugFileName
 * @property        integer                             $iDebugMessageNumber
 * @property        string                              $sLastExceptionMessage
 */
class ExternalData extends BaseObject
{
    const sDateTimeFormat = 'Y-m-d H:i:s';

    public $aGuzzleConfig;
    public $oGuzzleClient;
    public $aRequestHeaders;
    public $sUserAgent;
    public $iLastStatusCode;

    public $sDefaultOrigin;
    public $sDefaultReferer;

    public $bSSLVerification    = true;

    public $bUseHTTPS           = true;

    public $bDebugMode          = false;

    public $bUseCookies         = false;

    public $sCookieDirName;
    public $sCookieFileName;
    public $aCustomCookies;
    public $sCookiesDomain;
    public $oCookieStorage;

    public $sDebugDirName;
    public $sDebugFileName;
    public $iDebugMessageNumber = 1;

    public $sLastExceptionMessage;


    public function __construct($param1 = NULL, $param2 = NULL, $config = [])
    {
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
        
        set_time_limit(20);
        clearstatcache();

        $this->sDefaultOrigin   = 'https://www.realtor.com';
        $this->sDefaultReferer  = 'https://www.realtor.com/myhome';

        $this->sDebugDirName    = \Yii::getAlias('@runtime') . '/logs/';
        $this->sDebugFileName   = 'external-connections.log';

        $this->oCookieStorage = false;
        // $xCookies = false;
        if($this->bUseCookies == true)
        {
            if(empty($this->sCookieDirName) && empty($this->sCookieFileName))
            {
                // var_dump($this->sCookieDirName);
                die("sCookieDirName AND sCookieFileName must be declared.");
            }

            if(isset($this->aCustomCookies) && is_array($this->aCustomCookies) && count($this->aCustomCookies) && isset($this->sCookiesDomain))
            {
                /** @noinspection PhpUndefinedClassInspection */
                // $xCookies = \GuzzleHttp\Cookie\FileCookieJar::fromArray($this->aCustomCookies, $this->sCookiesDomain);
                $this->oCookieStorage = \GuzzleHttp\Cookie\FileCookieJar::fromArray($this->aCustomCookies, $this->sCookiesDomain);
            }
            else
            {
                /** @noinspection PhpUndefinedClassInspection */
                // $xCookies = new \GuzzleHttp\Cookie\FileCookieJar($cookieFile, true);
                $this->oCookieStorage = new \GuzzleHttp\Cookie\FileCookieJar($this->sCookieDirName . $this->sCookieFileName,true);
            }
        }


        // https://github.com/composer/ca-bundle
        /** @noinspection PhpUndefinedClassInspection */
        $this->aGuzzleConfig =
            [
                \GuzzleHttp\RequestOptions::VERIFY  => $this->bSSLVerification ? \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() : false,
                'cookies'                           => $this->oCookieStorage // $xCookies
            ];

        /** @noinspection PhpUndefinedClassInspection */
        $this->oGuzzleClient = new \GuzzleHttp\Client($this->aGuzzleConfig);

        $this->aRequestHeaders =
            [
                'Accept'            => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding'   => 'gzip, deflate, br',
                'Accept-Language'   => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Connection'        => 'keep-alive',
                'Host'              => 'www.realtor.com',
                'Referer'           => 'https://www.realtor.com/',
                'User-Agent'        => \Yii::$app->request->userAgent ? \Yii::$app->request->userAgent : $this->sUserAgent,
                'X-Requested-With'  => 'XMLHttpRequest',
            ];

        $this->randomize_user_agent();

    }


    /**
     * @return string
     *
     * Generate real random user-agents
     * https://github.com/joecampo/random-user-agent
     */
    public function randomize_user_agent()
    {
        try
        {
            $this->sUserAgent = \Campo\UserAgent::random
            (
                [
                    // 'os_type' => 'Windows',
                    'device_type' => 'Desktop'
                ]
            );
        }
        catch (\Exception $e)
        {
        }

        return $this->sUserAgent;
    }


    public function request($sURL = NULL, $sMethod = 'GET', $aParams = NULL)
    {
        $response = NULL;
        $fp = NULL;

        $aRequestOptions =
            [
                'headers' => $this->aRequestHeaders,
            ];

        if(isset($aParams) && is_array($aParams) && count($aParams))
        {
            foreach($aParams as $xParamKey => $xParamValue)
            {
                switch($xParamKey)
                {
                    case    'form_params':
                    case    'json':

                            $aRequestOptions = array_merge($aRequestOptions, [$xParamKey => $xParamValue]);

                            // print_r($aRequestOptions);
                            // die;

                            break;
                }
            }
        }

        if(isset($sURL) && !empty($sURL))
        {
            $sURL = $this->schemeCheck($sURL);

            // Если включен режим отладки
            if($this->bDebugMode == true)
            {
                $fp = fopen($this->sDebugDirName . $this->sDebugFileName, 'a');
                fwrite($fp, "" . date("Y-m-d H:i:s") . "\r\n");
                fwrite($fp, "===================\r\n");
                fwrite($fp, "URL: " . $sURL . "\r\n");
                fwrite($fp, "\r\n");
                fwrite($fp, "\r\n");

                $aRequestOptions = array_merge($aRequestOptions, ['debug' => $fp]);
            }

            set_time_limit(0);

            try
            {
                $this->sLastExceptionMessage    = NULL;
                $this->iLastStatusCode          = NULL;

                $response = $this->oGuzzleClient->request
                    (
                        $sMethod,
                        $sURL,
                        $aRequestOptions
                    );

                $this->iLastStatusCode = $response->getStatusCode();
            }
            /** @noinspection PhpUndefinedClassInspection */
            catch (\GuzzleHttp\Exception\ClientException $e)
            {
                $this->iLastStatusCode = $e->getResponse()->getStatusCode();

                $aStatus = $e->getResponse()->getHeader('Status');

                $sMessage = NULL;
                if(isset($aStatus[0]) && is_string($aStatus[0]) && !empty($aStatus[0]))
                {
                    $sMessage = $aStatus[0];
                }
                else
                {
                    $sMessage = $e->getMessage();
                    $sMessage = HtmlPurifier::process($sMessage);
                }

                $sMessage = strip_tags($sMessage);

                $this->sLastExceptionMessage = $sMessage;

                $messageLog =
                    [
                        'status' => 'Request Error',
                        'content' => $e
                    ];

                \Yii::info($messageLog, 'connection.log'); //запись в лог
            }
            /** @noinspection PhpUndefinedClassInspection */
            catch (\GuzzleHttp\Exception\ConnectException $e)
            {
                $this->sLastExceptionMessage = $e->getMessage();

                $messageLog =
                    [
                        'status' => 'Request Error',
                        'content' => $e
                    ];

                \Yii::info($messageLog, 'connection.log'); //запись в лог
            }
            /** @noinspection PhpUndefinedClassInspection */
            catch (\GuzzleHttp\Exception\RequestException $e)
            {
                $this->sLastExceptionMessage = $e->getMessage();

                $messageLog =
                    [
                        'status' => 'Request Error',
                        'content' => $e
                    ];

                \Yii::info($messageLog, 'connection.log'); //запись в лог
            }
            catch (\Exception $e)
            {
                $this->sLastExceptionMessage = $e->getMessage();

                $messageLog =
                    [
                        'status' => 'Request Error',
                        'content' => $e
                    ];

                \Yii::info($messageLog, 'connection.log'); //запись в лог
            }

            // Если включен режим отладки
            if($this->bDebugMode == true)
            {
                fwrite($fp, "\r\n");
                fclose($fp);
            }
        }

        return $response;
    }



    public function schemeCheck($sURL)
    {
        $sRetValue = NULL;

        if(isset($sURL) && !empty($sURL))
        {
            $iHTTPSPos  = strpos($sURL, 'https://');
            $iHTTPPos   = strpos($sURL, 'http://');

            // Проверка использования протокола (http или https)
            if($this->bUseHTTPS == true)
            {
                if($iHTTPSPos !== false)
                {
                    $sURL = substr($sURL, 8);
                }
                else if($iHTTPPos !== false)
                {
                    $sURL = substr($sURL, 7);
                }

                $sRetValue = 'https://' . $sURL;
            }
            else
            {
                $sRetValue = 'http://' . $sURL;
            }
        }

        return $sRetValue;
    }





    /**
     * @noinspection PhpUndefinedClassInspection
     * @param \GuzzleHttp\Psr7\Response     $oResponse
     * @param string                        $sResponseType
     * @return null
     */
    public function parseResponse($oResponse = NULL, $sResponseType = 'HTML')
    {
        $sRetValue = NULL;

        if(isset($oResponse) && $oResponse !== NULL)
        {
            // $iStatusCode = $oResponse->getStatusCode();
            $iStatusCode = $this->iLastStatusCode;

            // $this->iLastStatusCode = $iStatusCode;

            switch ($iStatusCode)
            {
                case        200:
                            switch($sResponseType)
                            {
                                case    'HTML':
                                        $sRetValue = $oResponse->getBody()->getContents();
                                        break;

                                case    'JSON':
                                        $sRetValue = json_decode(strip_tags($oResponse->getBody()->getContents()), true);

                                        if(json_last_error() != 0)
                                        {
                                            $aRetValue = NULL;
                                        }
                                        break;
                            }
                            break;

                default:
                            $sRetValue = NULL;
                            break;
            }
        }

        return $sRetValue;
    }




    /**
     * Запись отладочной информации [ $sDebugMessage ] в файле [ $sDebugFileName ]
     * @param string $sDebugMessage
     * @param null $sDebugFileName
     * @return bool
     */
    public function debugger($sDebugMessage = '', $sDebugFileName = NULL)
    {
        // Если включен режим отладки сохраняем отладочную информацию [ $sDebugMessage ] в файле [ $this->sDebugFileName ],
        // который расположен в папке [ $this->sDebugDirName ]
        if($this->bDebugMode == true)
        {
            if(!isset($sDebugFileName) || empty($sDebugFileName))
            {
                $sDebugFileName = $this->sDebugFileName;
            }

            $fp = fopen($this->sDebugDirName . $sDebugFileName, 'a');
            fwrite($fp, sprintf('%08d', $this->iDebugMessageNumber) . " | " . date("Y-m-d H:i:s") . " | " . trim(mb_convert_encoding($sDebugMessage, 'UTF-8', 'CP866')) . "\r\n");
            fclose($fp);

            $this->iDebugMessageNumber++;
        }

        return true;
    }


    public function ping($sHostName = '')
    {
        $bRetValue = false;

        $bOldUseHTTPS = $this->bUseHTTPS;

        if(isset($sHostName) && !empty($sHostName))
        {
            $this->aRequestHeaders =
                [
                    'Connection' => 'Close',                // Reinitialize connection: https://github.com/guzzle/guzzle/issues/113
                    'User-Agent' => $this->sUserAgent
                ];

            // print_r($this->aRequestHeaders);
            // die;

            $this->bUseHTTPS = false;
            $response = $this->request($sHostName, 'GET');
            $this->bUseHTTPS = $bOldUseHTTPS;

            if(isset($response) && $response !== NULL)
            {
                $iStatusCode = $response->getStatusCode();

                if($iStatusCode >= 200 && $iStatusCode < 300)
                {
                    $bRetValue = true;
                }
            }
        }

        return $bRetValue;
    }

    public function getPublicIP($sHostName = 'api.ipify.org?format=json')
    {
        $sResolvedIPAddress = false;

        if(isset($sHostName) && !empty($sHostName))
        {
            $this->aRequestHeaders =
                [
                    'Connection' => 'Close',                // Reinitialize connection: https://github.com/guzzle/guzzle/issues/113
                    'User-Agent' => $this->sUserAgent
                ];

            $response = $this->request($sHostName, 'GET');

            if(isset($response) && $response !== NULL)
            {
                $iStatusCode = $response->getStatusCode();

                switch ($iStatusCode)
                {
                    case    200:
                            $sIP = json_decode(strip_tags($response->getBody()->getContents()), true);

                            if(json_last_error() != 0)
                            {
                                $sResolvedIPAddress = false;
                            }
                            else
                            {
                                if($sIP !== NULL && isset($sIP['ip']))
                                {
                                    $sResolvedIPAddress = $sIP['ip'];
                                }
                            }
                            break;
                }
            }
        }

        return $sResolvedIPAddress;
    }
}
