<?php
$hook = array(
    'hook' => 'ClientChangePassword',
    'function' => 'ClientChangePassword',
    'hook_tr' => 'Müşteri Şifre Değişimi',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri şifresini değiştirdiğinde müşteriye mesaj gönderir',
        'english' => 'After client changes account password it sends a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'variables' => '{firstname},{lastname}',
    'defaultmessage' => 'Sayin {firstname} {lastname}, sifreniz degistirildi.',
);

if (!function_exists('ClientChangePassword')) {
    function ClientChangePassword($args)
    {
        // whmcs 8'de userid ile telefon numarası elde edilemiyor. dolayısıyla şifre değişiminde sms gönderilemez
        // şimdilik bu hook kaldırılıyor.
        return null;

        $service = new SmsService();

        $blocked = $service->isUserBlockedToSms($args['userid']);
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

        if (!empty($templateRow['smsfieldname'])) {
            $stmt = $service->getClientDetailsWithSmsFieldName($templateRow['smsfieldname'], $args['userid']);
        } else {
            $stmt = $service->getClientDetailsBy($args['userid']);
        }

        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = $templateRow['template'];

        if (strpos($message, "{password}") !== false) {
            $replaceto = $args['password'];
            $message = str_replace("{password}", $replaceto, $message);
        }
        $fields = $service->getFieldsWithName(__FUNCTION__);
        while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {

            if (strpos($message, "{" . $field['field'] . "}") !== false) {
                $replaceto = $clientRow[$field['field']];
                $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
            }
        }

        $SMSArray = [];

        $result = $service->clearPhoneNumber($clientRow['phonenumber']);
        $phonenumber = $result['phonenumber'];
        $validity = $result['validity'];
        if ($validity === false) {
            return null;
        }

        if(ctype_digit($phonenumber)) {

            array_push($SMSArray, new SMS($message, $phonenumber));

            $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
            $request->prepareXMLRequest();
            $request->XMLPOST();
        }
    }
}

// whmcs 8'de userid ile telefon numarası elde edilemiyor. dolayısıyla şifre değişiminde sms gönderilemez
// şimdilik bu hook kaldırılıyor.
//return $hook;