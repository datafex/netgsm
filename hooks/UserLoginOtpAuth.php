<?php

$hook = array(
    'hook' => 'UserLogin',
    'function' => 'UserLoginOtpAuth',
    'hook_tr' => 'Müşteri girişinde OTP ile doğrulama',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri girişinde tek kullanımlık doğrulama kodu ile müşteri doğrulaması sağlanır.',
        'english' => 'When clients login send a otp message to the client phone number',
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, Giriş Dogrulama kodunuz : {code}.',
    'variables' => '{firstname}, {lastname}, {code}',
);

if (!function_exists('UserLoginOtpAuth')) {
    // register aşamasında ClientAreRegister hook'undan önce bu hook tetikleniyor. ve buraya client_id değil user objesi geliyor. ve register aşamasında client oluşmadan önce, user oluştuktan sonra bu book tetikleniyor. dolayısıyla register olduğunda bu hook return null yapar. bu olay whmcs 8.0 dan sonra yaşanıyor. daha önceki sürümlerde client_id bu hook'a parametre olarak geliyordu.
    function UserLoginOtpAuth($args)
    {

//        echo '<br>' . __FUNCTION__ . '<br>';
//        var_dump($args);
//        exit;

        $function_name = __FUNCTION__;
        $service = new SmsService();
        $client_id = $args['user']->getClientIds()[0];

        if (!is_numeric($client_id)) {
            return null;
        }

        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }

        $blocked = $service->isUserBlockedToSms($client_id);
        if ($blocked == "1") {
            return null;
        }

        $template = $service->getTemplateDetails($function_name);
        if ($template == false) {
            return null;
        }

        $referenceCode = $service->getActiveReferenceCode($client_id);

        if ($referenceCode !== false) {

            session_unset();
            header("Location: index.php?m=netgsm&action=loginWithOtp&reference_code=" . $referenceCode);
            exit;
        }

        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        if ($templateRow['active'] == 0) { //// register olup doğrulama yapmayanları tespit edip tekrardan doğrulama kodu göndermek için.

            $function_name = 'ClientAreaRegister';
            $template_ = $service->getTemplateDetails($function_name);
            if ($template_ == false) {
                return null;
            }

            $templateRow_ = $template_->fetch(PDO::FETCH_ASSOC);
            if ($templateRow_['active'] == 0) {
                return null;
            }
            $phonenumber = $service->getOrijinalPhoneNumberByUseridAndTemplateName($client_id, $function_name);

            $result = $service->clearPhoneNumber($phonenumber);
            $phonenumber = $result['phonenumber'];
            $validity = $result['validity'];
            if ($validity === false) {
                return null;
            }

            $blocked = $service->isUserBlockedToOtpSms($client_id, $phonenumber);
            if ($blocked == "1") {
                return null;
            }

            $queries = $service->getOtpListQueries(array('inputUser' => $client_id, 'inputPhone' => $phonenumber));
            $stmt = $service->getConnection()->runSelectQuery($queries['selectQuery'], $queries['array_execute']);
            $row = $stmt->fetch();

            if (empty($row) or $row['durum'] != "1") {
                die;
                $code = rand(100000, 999999);

                $message = $templateRow['template'];
                
                $message = $service->prepareSmsMessage($client_id, $message, $function_name, $code);
                $reference_code = $service->sendOtpCode($client_id, $message, $phonenumber, $code, $templateRow, $settingsRow);

//                $netgsm_session_temp = $_SESSION;
                session_unset();
//                $_SESSION ['netgsm']['netgsm_session_temp'] = json_encode($netgsm_session_temp);
                header("Location: index.php?m=netgsm&action=registerWithOtp&reference_code=" . $reference_code);
                exit;
            } else {
                return null;
            }

        }

        $phonenumber = $service->getOrijinalPhoneNumberByUseridAndTemplateName($client_id, $function_name);
        $result = $service->clearPhoneNumber($phonenumber);

        $phonenumber = $result['phonenumber'];
        $validity = $result['validity'];
        if ($validity === false) {
            return null;
        }

        $blocked = $service->isUserBlockedToOtpSms($client_id, $phonenumber);
        if ($blocked == "1") {
            return null;
        }

        $code = rand(100000, 999999);

        $message = $templateRow['template'];
        $message = $service->prepareSmsMessage($client_id, $message, $function_name, $code);

        $reference_code = $service->sendOtpCode($client_id, $message, $phonenumber, $code, $templateRow, $settingsRow);

//        $netgsm_session_temp = $_SESSION;
        session_unset();
//        $_SESSION ['netgsm']['netgsm_session_temp'] = json_encode($netgsm_session_temp);
        header("Location: index.php?m=netgsm&action=loginWithOtp&reference_code=" . $reference_code);
        exit;
    }

}


return $hook;
