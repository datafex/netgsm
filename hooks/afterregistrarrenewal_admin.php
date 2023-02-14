<?php
$hook = array(
    'hook' => 'AfterRegistrarRenewal',
    'function' => 'AfterRegistrarRenewal_admin',
    'hook_tr' => 'Alan Adı Yenileme',
    'title' => '',
    'description' => array(
        'turkish' => 'Domain yenilendikten sonra yöneticiye mesaj gönderir',
        'english' => 'After domain renewal it sends a message to the admin.'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => 'Domain yenilendi. ({domain})',
    'variables' => '{domain}'
);
if (!function_exists('AfterRegistrarRenewal_admin')) {
    function AfterRegistrarRenewal_admin($args)
    {

        $service = new SmsService();

        $template = $service->getTemplateDetails(__FUNCTION__);
        if ($template == false) {
            return null;
        }
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        $message = $templateRow['template'];
        if ($templateRow['active'] == 0) {
            return null;
        }
        if (empty($templateRow['admingsm'])) {
            return null;
        }
        $phonenumbers = explode(",", $templateRow['admingsm']);
        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }
        if (strpos($message, "{domain}") !== false) {
            $message = str_replace("{domain}", $args['params']['sld'].".".$args['params']['tld'], $message);
        }
        $SMSArray = [];
        foreach ($phonenumbers as $phonenumber) {

            $result = $service->clearPhoneNumber($phonenumber);
            $phonenumber = $result['phonenumber'];
            $validity = $result['validity'];
            if ($validity === false) {
                return null;
            }

            if(ctype_digit($phonenumber)) {
                array_push($SMSArray, new SMS($message, trim($phonenumber)));
                $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                $request->prepareXMLRequest();
                $request->XMLPOST();
            }
        }

    }
}

return $hook;