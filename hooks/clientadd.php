<?php
$hook = array(
    'hook' => 'ClientAdd',
    'function' => 'ClientAdd',
    'hook_tr' => 'Yeni Müşteri',
    'title' => '',
    'description' => array(
        'turkish' => 'Müşteri kaydı tamamlandıktan sonra müşteriye mesaj gönderir',
        'english' => 'After Client Registration it sends a message to the client.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, Bizi tercih ettiginiz icin tesekkur ederiz. Email: {email} Sifre: {Girdiğiniz Sifre}',
    'variables' => '{firstname},{lastname},{email},{password}'
);

if (!function_exists('ClientAdd')) {
    function ClientAdd($args)
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
        $message = $templateRow['template'];
        $fields = $service->getFieldsWithName(__FUNCTION__);
        
        if (!empty($templateRow['smsfieldname'])) {

            $userSql = "SELECT c.*, v.value as `gsmnumber` FROM tblclients as c, tblcustomfieldsvalues as v 
			WHERE c.id='" . $args['client_id'] . "' AND c.id=v.relid AND v.fieldid=(SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1) LIMIT 1;";

            $stmt = $service->getConnection()->runSelectQuery($userSql);
            if ($stmt == false) {
                return null;
            }
            $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($clientRow !== null) {
                if (empty($clientRow['gsmnumber'])) {
                    return null;
                }
                $phonenumber = $clientRow['gsmnumber'];
                if (strpos($message, "{password}") !== false) {
                    $message = str_replace("{password}", $args['password'], $message);
                }
                while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
                    if (strpos($message, "{" . $field['field'] . "}") !== false) {
                        $replaceto = $clientRow[$field['field']];
                        $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
                    }
                }
            }
        } else {

            if (strpos($message, "{password}") !== false) {
                $message = str_replace("{password}", $args['password'], $message);
            }

            while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
                if (strpos($message, "{" . $field['field'] . "}") !== false) {
                    $replaceto = $args[$field['field']];
                    $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
                }
            }

            $phonenumber = $args['phonenumber'];
        }

        $SMSArray = [];

        $result = $service->clearPhoneNumber($phonenumber);
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

return $hook;