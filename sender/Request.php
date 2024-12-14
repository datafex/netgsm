<?php

include_once('../SmsService.php');

class Request
{
    private $xmlRequest;
    private $SMS = [];       //// SMS class object array to keep each sms infos
    private $usercode;
    private $password;
    private $title;
    private $url;
    private $language;

    function __construct($title, $SMS, $usercode, $password)
    {
        $this->title = $title;
        $this->SMS = $SMS;
        $this->usercode = $usercode;
        $this->password = $password;
        $this->setLanguage();
    }


    function XMLPOST()
    {
        
        $url = "https://api.netgsm.com.tr/sms/send/xml";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->xmlRequest);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function XMLPOSTOTP()
    {
       
//        header("Content-Type: text/xml");
//        echo ($this->getXmlOtpRequest());
//        exit;

        $url = "https://api.netgsm.com.tr/sms/send/otp";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getXmlOtpRequest());
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            return $error_msg;
        }

        return $result;
    }

    public static function HTTPGET($u, $p)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.netgsm.com.tr/sms/header/get/?usercode=' . $u . '&password=' . $p);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;

    }

    /**
     * @return mixed
     */
    public function getUsercode()
    {
        return $this->usercode;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getSMS()
    {
        return $this->SMS;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getXmlRequest()
    {
        return $this->xmlRequest;
    }

    public function setLanguage()
    {
        $service = new SmsService();
        $settings = $service->getSettings();
        $settingsRow = $settings->fetch();

        $this->language = trim($settingsRow['smslanguage']);
    }

    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * Prepare Xml Request
     */
    public function prepareXMLRequest()
    {
        $this->xmlRequest = $this->getXmlRequestHeader();
        $this->xmlRequest .= $this->getXmlRequestBody();
    }

    function getXmlRequestHeader()
    {
        if ($this->getLanguage() == "tr") {
            $language = 'dil="TR"';
        } else {
            $language = '';
        }
        try {
            $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?><mainbody><header>';
            $xmlHeader .= '<company wh="1" ' . $language . '>Netgsm</company>';
            $xmlHeader .= '<usercode>' . $this->usercode . '</usercode>';
            $xmlHeader .= '<password>' . $this->password . '</password>';
            $xmlHeader .= '<startdate></startdate>';
            $xmlHeader .= '<stopdate></stopdate>';
            $xmlHeader .= '<type>n:n</type>';
            $xmlHeader .= '<msgheader>' . $this->getTitle() . '</msgheader>';
            $xmlHeader .= '</header>';

            return $xmlHeader;
        } catch (Exception $e) {
            return 'XML HEADER ERROR';
        }

    }


    function getXmlRequestBody()
    {
        try {
            $xmlBody = '<body>';
            foreach ($this->SMS as $SM) {

                if ($this->getLanguage() == "tr") {
                    $message = $SM->getMessage();
                } else {
                    $message = $this->replace_tr($SM->getMessage());
                }

                $xmlBody .= '<mp><msg><![CDATA[' . $message . ']]></msg><no>' . $SM->getDestination() . '</no></mp>';
            }
            $xmlBody .= '</body></mainbody>';

            return $xmlBody;
        } catch (Exception $e) {
            return 'XML BODY ERROR';
        }
    }

    function getXmlOtpRequest()
    {
        $xmlOtpRequest = "";
        $xmlOtpRequest .= '<?xml version="1.0"?><mainbody><header>';
        $xmlOtpRequest .= '<usercode>' . $this->getUsercode() . '</usercode>';
        $xmlOtpRequest .= '<password>' . $this->getPassword() . '</password>';
        $xmlOtpRequest .= '<msgheader>' . $this->getTitle() . '</msgheader>';
        $xmlOtpRequest .= '</header><body>';
        foreach ($this->SMS as $SM) {
            $xmlOtpRequest .= '<msg><![CDATA[' . $SM->getMessage() . ']]></msg>';
            $xmlOtpRequest .= '<no>' . $SM->getDestination() . '</no>';
        }
        $xmlOtpRequest .= '</body></mainbody>';

        return $xmlOtpRequest;
    }

    function replace_tr($text)
    {
        $text = trim($text);
        $search = array('Ç', 'ç', 'Ğ', 'ğ', 'ı', 'İ', 'Ö', 'ö', 'Ş', 'ş', 'Ü', 'ü');
        $replace = array('C', 'c', 'G', 'g', 'i', 'I', 'O', 'o', 'S', 's', 'U', 'u');
        $new_text = str_replace($search, $replace, $text);
//        $new_text = preg_replace("/[^a-zA-Z0-9.]+/", "", $new_text);

        return $new_text;
    }
}

?>