<?php

$hook = array(
    'hook' => 'ClientAreaRegister',
    'function' => 'ClientAreaRegister',
    'hook_tr' => 'Yeni Müşteri - OTP',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri kaydı tamamlandıktan sonra telefon doğrulama mesajı gönderir ve müşteriye doğrulama ekranı çıkartır. Bu şablon sadece müşteri tarafında tetiklenebilir. Yönetici panelinden müşteri ekleme durumunda çalışmaz. OTP SMS gönderiminde mesajınız 1 boy olmalıdır. Türkçe karakter kullanımı önerilmez. Telefon doğrulaması yapılmadığı sürece her login işleminde (login doğrulaması kapalı olsa bile) doğrulama kodu gönderilir. Bir kez telefon doğrulaması yapıldıktan sonra bu şablon tarafından sms gönderilmez.',
        'english' => 'After Client Registration it sends a message to the client. You can also send verification code with OTP SMS.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, Doğrulama Kodunuz: {code}',
    'variables' => '{firstname},{lastname},{code}'
);

if (!function_exists('ClientAreaRegister')) {
    // ClientLoginOtpAuth.php dosyası var olduğu sürece burası çalışmayacak ve müşteri kaydındaki otp sms'i de oradan gönderecek. Eğer whmcs tetikelerse diye aşağıdaki kod yazılıdır. whmcs 8.0 da registir durumunda bu hook çalışır. önceki sürümlerde register durumunda bile ClientLogin hook'undan register mesajı gönderiliyor.

    function ClientAreaRegister($args)
    {
        $service = new SmsService();

        $client_id = isset($args['client_id']) ? $args['client_id'] : $args['userid'];

        $blocked = $service->isUserBlockedToSms($client_id);
        if ($blocked == "1") {
            return null;
        }
        $template = $service->getTemplateDetails(__FUNCTION__);
        if ($template == false) {
            return null;
        }
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);

        if ($templateRow['active'] == 0) {
            return null;
        }
        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }

        $phonenumber = $service->getOrijinalPhoneNumberByUseridAndTemplateName($client_id, __FUNCTION__);
        $result = $service->clearPhoneNumber($phonenumber);
        $phonenumber = $result['phonenumber'];
        $validity = $result['validity'];
        if ($validity === false) {
            return null;
        }

        $code = rand(100000, 999999);

        $message = $templateRow['template'];
        $message = $service->prepareSmsMessage($client_id, $message, __FUNCTION__, $code);
        $reference_code = $service->sendOtpCode($client_id, $message, $phonenumber, $code, $templateRow, $settingsRow);

//        $netgsm_session_temp = $_SESSION;
        session_unset();
//        $_SESSION ['netgsm_session_temp'] = $netgsm_session_temp;
        header("Location: index.php?m=netgsm&action=registerWithOtp&reference_code=" . $reference_code);
        exit;
    }
}

return $hook;