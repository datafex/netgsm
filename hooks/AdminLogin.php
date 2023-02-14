<?php
$hook = array(
    'hook' => 'AdminLogin',
    'function' => 'AdminLogin',
    'hook_tr' => 'Yönetici Girişi',
    'title' => '',
    'description' => array(
        'turkish' => 'Yönetici Girişi Yapıldığında Belirlenmiş Numaralara Mesaj Gönderir.',
        'english' => 'When an admin login it sends message.'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => '{username}, Yonetici paneline giris yapti.',
    'variables' => '{username}'
);
if (!function_exists('AdminLogin')) {
    function AdminLogin($args)
    {
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

        if (strpos($message, "{username}") !== false) {
            $message = str_replace("{username}", $args['username'], $message);
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
                
               
            }
            
        }
        $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
        $request->prepareXMLRequest();
        $request->XMLPOST();

    }
}

return $hook;
