<?php

$hook = array(
    'hook' => 'ClientLogin',
    'function' => 'ClientLoginOtpAuth',
    'hook_tr' => 'Müşteri girişinde OTP ile doğrulama',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri girişinde tek kullanımlık doğrulama kodu ile müşteri doğrulaması sağlanır.',
        'english' => 'When clients login send a otp message to the client phone number',
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, Dogrulama kodunuz : {code}.',
    'variables' => '{firstname}, {lastname}, {code}',
);

if (!function_exists('ClientLoginOtpAuth')) {
    function ClientLoginOtpAuth($args)
    {
        $function_name = __FUNCTION__;
        $userid = $args['userid'];

        $service = new SmsService();

        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }

        $blocked = $service->isUserBlockedToSms($userid);
        if ($blocked == "1") {
            return null;
        }

        $template = $service->getTemplateDetails($function_name);
        if ($template == false) {
            return null;
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
            $phonenumber = $service->getOrijinalPhoneNumberByUseridAndTemplateName($userid, $function_name);
            $result = $service->clearPhoneNumber($phonenumber);
            $phonenumber = $result['phonenumber'];
            $validity = $result['validity'];
            if ($validity === false) {
                return null;
            }

            $blocked = $service->isUserBlockedToOtpSms($userid, $phonenumber);
            if ($blocked == "1") {
                return null;
            }

            $queries = $service->getOtpListQueries(array('inputUser' => $userid, 'inputPhone' => $phonenumber));
            $stmt = $service->getConnection()->runSelectQuery($queries['selectQuery'], $queries['array_execute']);
            $row = $stmt->fetch();

            if (empty($row) or $row['durum'] != "1") {

                $code = rand(100000, 999999);

                $message = $templateRow['template'];
                $message = $service->prepareSmsMessage($userid, $message, $function_name, $code);
                $reference_code = $service->sendOtpCode($userid, $message, $phonenumber, $code, $templateRow, $settingsRow);

                $netgsm_session_temp = $_SESSION;
                session_unset();
                $_SESSION ['netgsm_session_temp'] = $netgsm_session_temp;
                header("Location: index.php?m=netgsm&action=registerWithOtp&reference_code=" . $reference_code);
                exit;
            } else {
                return null;
            }

        }

        $phonenumber = $service->getOrijinalPhoneNumberByUseridAndTemplateName($userid, $function_name);
        $result = $service->clearPhoneNumber($phonenumber);
        $phonenumber = $result['phonenumber'];
        $validity = $result['validity'];
        if ($validity === false) {
            return null;
        }


        $blocked = $service->isUserBlockedToOtpSms($userid, $phonenumber);
        if ($blocked == "1") {
            return null;
        }


        $code = rand(100000, 999999);

        $message = $templateRow['template'];
        $message = $service->prepareSmsMessage($userid, $message, $function_name, $code);

        $reference_code = $service->sendOtpCode($userid, $message, $phonenumber, $code, $templateRow, $settingsRow);

        $netgsm_session_temp = $_SESSION;
        session_unset();
        $_SESSION ['netgsm_session_temp'] = $netgsm_session_temp;
        header("Location: index.php?m=netgsm&action=loginWithOtp&reference_code=" . $reference_code);
        exit;
    }

}


return $hook;
