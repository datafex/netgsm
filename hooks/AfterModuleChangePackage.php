<?php
$hook = array(
    'hook' => 'AfterModuleChangePackage',
    'function' => 'AfterModuleChangePackage',
    'hook_tr' => 'Paket Değişikliği',
    'title' => '',
    'description' => array(
        'turkish' => 'Hizmet Paketi değişikliğinde mesaj gönderir',
        'english' => 'After module package changed it send a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, ürün/hizmet paketiniz degistirildi. ({domain})',
    'variables' => '{firstname},{lastname},{domain}'
);
if (!function_exists('AfterModuleChangePackage')) {
  
    function AfterModuleChangePackage($args)
    {

        $service = new SmsService();

        $userid=$args['params']['clientsdetails']['userid'];

        $blocked = $service->isUserBlockedToSms($userid);
        if ($blocked == "1") {
            return null;
        }

        $type = $args['params']['producttype'];

        if ($type == "hostingaccount") {

            $template = $service->getTemplateDetails(__FUNCTION__);
            if ($template == false) {
                return null;
            }
            $templateRow = $template->fetch(PDO::FETCH_ASSOC);
            if ($templateRow['active'] == 0) {
                return null;
            }
            $message = $templateRow['template'];

            $settings = $service->getSettings();
            $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
            if (!$settingsRow['usercode'] || !$settingsRow['password']) {
                return null;
            }
            if (!empty($templateRow['smsfieldname'])) {
                $stmt = $service->getClientDetailsWithSmsFieldName($templateRow['smsfieldname'], $userid);
            } else {
                $stmt = $service->getClientDetailsBy($userid);
            }

            if ($stmt == false) {
                return null;
            }
            $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($clientRow['phonenumber'])) {
                return null;
            }
            if ($clientRow !== null) {

                $fields = $service->getFieldsWithName(__FUNCTION__);
                if (strpos($message, "{domain}") !== false) {
                    $message = str_replace("{domain}", $args['params']['domain'], $message);
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
                    // $request->XMLPOST();
                }

            }

        } else {
            return null;
        }

    }

}
return $hook;
