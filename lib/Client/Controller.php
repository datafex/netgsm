<?php

namespace WHMCS\Module\Addon\Netgsm\Client;

/**
 * Sample Client Area Controller
 */
class Controller
{

    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return array
     */

    public function index($vars)
    {
        return array(
            'pagetitle' => 'loginWithOtp',
            'breadcrumb' => array(
                'index.php?m=netgsm&action=loginWithOtp' => 'Kullanıcı Doğrulama',
            ),
            'templatefile' => 'secretpage',
            'requirelogin' => false,
            'forcessl' => false,
        );

    }

    public function loginWithOtp($vars)
    {
        $parameters = array();
        $parameters['timezero'] = '0';
        $parameters['action'] = __FUNCTION__;
        $parameters['islogin'] = 1;
        $parameters = $this->checkOtp($parameters);

        return array(
            'pagetitle' => 'Telefon Doğrulama',
            'breadcrumb' => array(
                'index.php?m=netgsm&action=loginWithOtp' => 'Kullanıcı Doğrulama',
            ),
            'templatefile' => 'publicpage',
            'requirelogin' => false,
            'forcessl' => false,
            'vars' => $parameters,
        );

    }

    public function registerWithOtp($vars)
    {
        $parameters = array();
        $parameters['timezero'] = '0';
        $parameters['action'] = __FUNCTION__;
        $parameters['islogin'] = 1;
        $parameters = $this->checkOtp($parameters);

        return array(
            'pagetitle' => 'Telefon Doğrulama',
            'breadcrumb' => array(
                'index.php?m=netgsm&action=registerWithOtp' => 'Kullanıcı Doğrulama',
            ),
            'templatefile' => 'publicpage',
            'requirelogin' => false,
            'forcessl' => false,
            'vars' => $parameters,
        );

    }

    private function checkOtp($parameters)
    {
        $code = 0;

        try {
            $service = new \SmsService();
            $sql = "select nso.id, nso.verification_code, nso.reference_code, nso.userid, nso.phonenumber, nso.start_otp_date,nso.end_otp_date, nso.durum,c.email
                    ,(case when now()<nso.end_otp_date then '0'
                    else '1' end) as timesup
                    from netgsm_sms_otp as nso
                    inner join tblclients as c on c.id=nso.userid
                    where nso.reference_code=:reference and nso.durum = 0 LIMIT 1";

            $array_execute = array(':reference' => $_REQUEST['reference_code']);
            $stmt = $service->getConnection()->runSelectQuery($sql, $array_execute);
            $row = $stmt->fetch();

//            print_r($row);
//            exit;
            $userid = $row['userid'];
            $email = $row['email'];
            $code = trim($row['verification_code']);
            $timesup = $row['timesup'];

            $parameters['start_otp_date'] = $row['start_otp_date'] ? $row['start_otp_date'] : "0";
            $parameters['phonenumber'] = $row['phonenumber'] ? $row['phonenumber'] : "";
            $parameters['timesup'] = $timesup;

            $sql_time = "select TIMESTAMPDIFF(SECOND,now(),'" . $row['end_otp_date'] . "') as remaining_time";
            $stmt_time = $service->getConnection()->runSelectQuery($sql_time);
            $row = $stmt_time->fetch();
            $parameters['remaining_time'] = $row['remaining_time'] ? $row['remaining_time'] : "0";

            if ($timesup === "1") {
                $parameters['message'] = 'Doğrulama kodunun geçerlilik süresi dolmuş.';
            }

            if ($row == false || $parameters['phonenumber'] == '') {
                $parameters['timesup'] = "1";
                $parameters['message'] = 'Doğrulanacak telefon numarası bulunamadı. ';
            }

        } catch (Exception $e) {
            $parameters['message'] = 'Doğrulanacak telefon numarası bulunamadı. ';
        }

//        if(isset($_SESSION['netgsm']['netgsm_session_temp'])){
//            $_SESSION = json_decode($_SESSION ['netgsm']['netgsm_session_temp']);
//        }

        $parameters['reference_code'] = $_REQUEST['reference_code'];
        $reference_code = $_REQUEST['reference_code'];

        if ($timesup === "0" and isset($_REQUEST['otp_code']) and $code === trim($_REQUEST['otp_code'])) {

            $sql = "update `netgsm_sms_otp` set durum = 1,islogin = :islogin where reference_code=:reference and verification_code=:code";
            $service->getConnection()->runSelectQuery($sql, array(':reference' => $reference_code, ':code' => $code, ':islogin' => $parameters['islogin']));

//            echo $url;
//            exit;
            $client_id = $userid;
            $sso = $service->createSSOToken($client_id);

//            print_r($client_id.'<br><br>');
//            print_r($sso);
//            exit;

            header("Location: " . $sso['redirect_url']);
            exit;

        } elseif ($timesup === "0" and isset($_REQUEST['otp_code']) and $code !== $_REQUEST['otp_code']) {
            $parameters['message'] = 'Doğrulama kodunuz yanlış. Lütfen kontrol ediniz.';
        }

        return $parameters;
    }
}
