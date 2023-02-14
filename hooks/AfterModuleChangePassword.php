<?php
$hook = array(
    'hook' => 'AfterModuleChangePassword',
    'function' => 'AfterModuleChangePassword',
    'hook_tr' => 'Hesap Şifresi Değişimi',
    'title' => '',
    'description' => array(
        'turkish' => 'Hosting hesabı şifresi değiştiğinde mesaj gönderir',
        'english' => 'After module password changed it sends a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, {domain} hizmetinin hosting sifresi basariyla degisti.',
    'variables' => '{firstname}, {lastname}, {domain}, {username}'
);
if (!function_exists('AfterModuleChangePassword')) {
    function AfterModuleChangePassword($args)
    {

        $service = new SmsService();

        $userid = $args['params']['clientsdetails']['userid'];

        $blocked = $service->isUserBlockedToSms($userid);
        if ($blocked == "1") {
            return null;
        }

        if (isset($args['params']['producttype']) && $args['params']['producttype'] == "hostingaccount") {
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

            if ($clientRow !== null) {

                $fields = $service->getFieldsWithName(__FUNCTION__);

                if (strpos($message, "{domain}") !== false) {
                    $message = str_replace("{domain}", $args['params']['domain'], $message);
                }
                if (strpos($message, "{username}") !== false) {
                    $message = str_replace("{username}", $args['params']['username'], $message);
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
                die;
                if (ctype_digit($phonenumber)) {

                    array_push($SMSArray, new SMS($message, $phonenumber));

                    $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                    $request->prepareXMLRequest();
                    $request->XMLPOST();
                }
            }
        } else if (isset($args['serviceid'])) {

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
                $sql = "SELECT h.domain, c.firstname, c.lastname, c.companyname, c.email, c.address1, c.address2, c.city,
            c.state, c.postcode, c.country, c.status, v.value as phonenumber
            FROM `tblhosting` as h, `tblclients` as c,`tblcustomfieldsvalues` as v WHERE 
            h.id=" . $args['serviceid'] . " AND h.userid = c.id AND c.id=v.relid AND 
            v.fieldid = (SELECT id from tblcustomfields where fieldname='" . $templateRow['smsfieldname'] . "' AND type = 'client' AND fieldtype = 'text' LIMIT 1);";

            } else {
                $sql = "SELECT a.`domain`, b.`firstname`,b.`lastname`,b.`phonenumber` FROM `tblhosting` as a 
                    JOIN `tblclients` as b ON a.userid = b.id WHERE a.`id`='" . $args['serviceid'] . "';";
            }

            $client = $service->getConnection()->runSelectQuery($sql);
            if ($client == false) {
                return null;
            }
            $clientRow = $client->fetch(PDO::FETCH_ASSOC);

            if (!empty($clientRow)) {
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
                if (ctype_digit($phonenumber)) {

                    array_push($SMSArray, new SMS($message, $phonenumber));

                    $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                    $request->prepareXMLRequest();
                    $request->XMLPOST();
                }
            }
        } else {
            return null;
        }
    }
}
return $hook;
