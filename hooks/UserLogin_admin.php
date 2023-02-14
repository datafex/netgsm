<?php

$hook = array(
    'hook' => 'UserLogin',
    'function' => 'UserLogin_admin',
    'hook_tr' => 'Müşteri Girişi',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri Sisteme Giriş Yaptıktan Sonra Yöneticiye Mesaj Gönderir',
        'english' => 'When client login it sends a message to the admin.'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => '({firstname} {lastname}), Siteye giris yapti',
    'variables' => '{firstname},{lastname}'
);

if (!function_exists('UserLogin_admin')) {
    function UserLogin_admin($args)
    {
        
        $user = $args['user'];
        $client_id = $args['user']->getClientIds()[0];

        if (!is_numeric($client_id)) {
            return null;
        }
        
        $service = new SmsService();

        $template = $service->getTemplateDetails(__FUNCTION__);
        if ($template == false) {
            return null;
        }
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        if ($templateRow['active'] == 0) {
            return null;
        }
        $message = $templateRow['template'];
        if (empty($templateRow['admingsm'])) {
            return null;
        }
        
        $phonenumbers = explode(",", $templateRow['admingsm']);

        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }

        $stmt = $service->getClientDetailsBy($client_id);
        if ($stmt == false) {
            return null;
        }
        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($clientRow !== null) {

            $fields = $service->getFieldsWithName(__FUNCTION__);

            while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {

                if (strpos($message, "{" . $field['field'] . "}") !== false) {
                    $replaceto = $clientRow[$field['field']];
                    $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
                }
            }

            $SMSArray = [];
            
            foreach ($phonenumbers as $phonenumber) {

                $result = $service->clearPhoneNumber($phonenumber);
                $phonenumber = $result['phonenumber'];
                $validity = $result['validity'];
                if ($validity === false) {
                    continue;
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

}


return $hook;
