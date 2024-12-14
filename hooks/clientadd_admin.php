<?php
$hook = array(
    'hook' => 'ClientAdd',
    'function' => 'ClientAdd_admin',
    'hook_tr' => 'Yeni Müşteri',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri kaydı tamamlandıktan sonra yöneticiye mesaj gönderir.',
        'english' => 'After Client Registration it sends a message to the admin.'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => 'Yeni bir müşteri www.netgsm.com.tr adresine kayıt oldu.',
    'variables' => ''
);
if (!function_exists('ClientAdd_admin')) {
    function ClientAdd_admin($args)
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
        if (empty($templateRow['admingsm'])) {
            return null;
        }

        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }

        $message = $templateRow['template'];
        $phonenumbers = explode(",", $templateRow['admingsm']);

        $fields = $service->getFieldsWithName(__FUNCTION__);

        while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {

            if (strpos($message, "{" . $field['field'] . "}") !== false) {
                $replaceto = $args[$field['field']];
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
return $hook;