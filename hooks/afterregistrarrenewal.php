<?php
$hook = array(
    'hook' => 'AfterRegistrarRenewal',
    'function' => 'AfterRegistrarRenewal',
    'hook_tr' => 'Alan Adı Yenileme',
    'title' => '',
    'description' => array(
        'turkish' => 'Domain yenilendikten sonra mesaj gönderir',
        'english' => 'After domain renewal it sends a message to the client'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, alan adiniz basariyla yenilendi. ({domain})',
    'variables' => '{firstname},{lastname},{domain}'
);
if (!function_exists('AfterRegistrarRenewal')) {
    function AfterRegistrarRenewal($args)
    {


        $service = new SmsService();

        $userid = $args['params']['userid'];
        $blocked = $service->isUserBlockedToSms($userid);
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
        $message = $templateRow['template'];

        if (!empty($templateRow['smsfieldname'])) {
            $stmt = $service->getClientDetailsWithSmsFieldName($templateRow['smsfieldname'],$userid);
        } else {
            $stmt = $service->getClientDetailsBy($userid);
        }

        if ($stmt == false) {
            return null;
        }
        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($clientRow !== null) {

            $fields = $service->getFieldsWithName(__FUNCTION__);

            if (strpos($message, "{domain}") !== false) {
                $message = str_replace("{domain}", $args['params']['sld'] . "." . $args['params']['tld'], $message);
            }
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

            if (ctype_digit($phonenumber)) {

                array_push($SMSArray, new SMS($message, $phonenumber));

                $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                $request->prepareXMLRequest();
                $request->XMLPOST();
            }
        }
    }
}

return $hook;