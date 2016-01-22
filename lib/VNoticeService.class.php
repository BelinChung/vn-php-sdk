<?php
/**
 * @file VNoticeService.class.php
 * @brief 微通知发送消息SDK（依赖curl扩展）
 * @author sdk@vtongzhi.com
 * @copyright www.vtongzhi.com
 * @version 1.0
 * @date 2015-05-01
 *
 */

class VNoticeService
{
    const MAX_RETRY_TIMES    = 3;
    const HTTP_GET           = 'GET';
    const HTTP_POST          = 'POST';
    const API_HOST           = 'http://api.vtongzhi.com';
    const SEND_MSG_API       = '/message/send';
    const CHECK_MSG_API      = '/message/check';

    private static $instance = null; 

    protected $errorCode     = 0;
    protected $errorMsg      = 'Success';

    /**
     * @return VNoticeService
     */
    public static function getInstance()
    {
        if ( null === self::$instance ) {
            $clz = __CLASS__;
            self::$instance = new $clz();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    public function sendMessage($ak, $sk, $content)
    {
        $arrPostData = array(
            'ak'      => $ak,
            'sk'      => $sk,
            'content' => $content,
        );
        $result = $this->sendRequest(self::API_HOST . self::SEND_MSG_API, 'POST', array(), $arrPostData);
        if ( false === $result ) {
            return false;
        }
        $responseData = json_decode($result, true);
        if ( false !== $responseData 
            && is_array( $responseData ) 
            && count($responseData) > 0 ) {

            if (isset($responseData['mid'])) {
                $this->errorCode = 0;
                $this->errorMsg  = 'Success';              
                return strval($responseData['mid']);
            } 

            if (isset($responseData['error_code']) && isset($responseData['error_msg'])) {
                $this->errorCode = intval($responseData['error_code']);
                $this->errorMsg  = $responseData['error_msg'];
            } else {
                $this->errorCode = -1;
                $this->errorMsg  = 'Unknow error';
            }
        }
        return false;
    }

    public function checkMessage($ak, $sk, $mid)
    {
        $arrGetData = array(
            'ak'  => $ak,
            'sk'  => $sk,
            'mid' => $mid,
        );
        $result = $this->sendRequest(self::API_HOST . self::CHECK_MSG_API, 'GET', $arrGetData);
        if ( false === $result ) {
            return false;
        }
        $responseData = json_decode($result, true);
        if ( false !== $responseData 
            && is_array( $responseData ) 
            && count($responseData) > 0 ) {

            if (isset($responseData['message_status'])) {
                $this->errorCode = 0;
                $this->errorMsg  = 'Success';              
                return strval($responseData['message_status']);
            } 

            if (isset($responseData['error_code']) && isset($responseData['error_msg'])) {
                $this->errorCode = intval($responseData['error_code']);
                $this->errorMsg  = $responseData['error_msg'];
            } else {
                $this->errorCode = -1;
                $this->errorMsg  = 'Unknow error';
            }
        }
        return false;
    }   

    private function sendRequest(
        $strRequestUri, 
        $strHttpMethod = 'GET', 
        $arrGetData = array(), 
        $arrPostData = array())
    {
        // check curl
        if (!function_exists('curl_init')) {
            $this->errorCode = -2;
            $this->errorMsg  = 'This SDK need curl extension in PHP';
            return false;
        }

        $strHttpMethod = strtoupper($strHttpMethod);

        if (is_array($arrGetData) && count($arrGetData) > 0) {
            $strQueryString = http_build_query($arrGetData);
            if (strpos($strRequestUri, '?') === false) {
                $strRequestUri = $strRequestUri . '?' . $strQueryString;
            } else {
                $strRequestUri = $strRequestUri . '&' . $strQueryString;
            }
        }

        $httpHeader = array(
            "Content-Type: multipart/form-data",
        );
        $time = 0;
        $result = null;

        while ($time++ < self::MAX_RETRY_TIMES && is_null($result)) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $strRequestUri);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            switch ($strHttpMethod) {
                case self::HTTP_POST:
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrPostData);
                    break;
                case self::HTTP_GET:
                    curl_setopt($ch, CURLOPT_HTTPGET, 1);
                    break;
                default:
                    return false;
            }
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $httpErrorCode = curl_errno($ch);
            curl_close($ch);

            if ( false === $result
                || $httpCode >= 500
                || $httpErrorCode > 0
            ) {
                continue;
            }

            return $result;
        }
        return false;
    }
    
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

}
