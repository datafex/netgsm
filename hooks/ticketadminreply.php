<?php
$hook = array(
    'hook' => 'TicketAdminReply',
    'function' => 'TicketAdminReply',
    'hook_tr' => 'Ticket Cevabı',
    'title' => '',
    'description' => array(
        'turkish' => 'Bir ticket cevaplandığında müşteriye mesaj gönderir.',
        'english' => 'After a ticket replied by admin, it sends a message to the client.'
    ),
    'type' => 'client',
    'extra' => '',
    'variables' => '{firstname},{lastname},{subject}',
    'defaultmessage' => 'Sayin {firstname} {lastname}, ({subject}) konu baslikli destek talebiniz yanitlandi.',
);

if (!function_exists('TicketAdminReply')) {
    function TicketAdminReply($args)
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


        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }
        if (!empty($templateRow['smsfieldname'])) {
            $userSql = "SELECT `a`.`id`,`a`.`firstname`, `a`.`lastname`, `a`.`phonenumber` as `gsmnumber`, `a`.`country`
        FROM `tblclients` as `a`
        WHERE `a`.`id` IN (SELECT userid FROM tbltickets WHERE id = '" . $args['ticketid'] . "')
        LIMIT 1";

            $userSql = "SELECT c.*, v.value as `gsmnumber` FROM tblclients as c, tblcustomfieldsvalues as v 
            			WHERE c.id=(SELECT userid FROM tbltickets WHERE id='" . $args['ticketid'] . "') AND c.id=v.relid AND v.fieldid=(SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1) LIMIT 1;";

        } else {
            $userSql = "SELECT `a` . `id`,`a` . `firstname`, `a` . `lastname`, `a` . `phonenumber` as `gsmnumber`, `a` . `country`
        FROM `tblclients` as `a`
        WHERE `a` . `id` IN(SELECT userid FROM tbltickets WHERE id = '" . $args['ticketid'] . "')
        LIMIT 1";
        }

        $client = $service->getConnection()->runSelectQuery($userSql);
        if ($client == false) {
            return null;
        }
        $clientRow = $client->fetch(PDO::FETCH_ASSOC);

        $blocked = $service->isUserBlockedToSms($clientRow['id']);
        if ($blocked == "1") {
            return null;
        }

        if (empty($clientRow['gsmnumber'])) {
            return null;
        }

        if (strpos($message, "{
                subject}") !== false) {
            $message = str_replace("{
                subject}", $args['subject'], $message);
        }
        if (strpos($message, "{
                message}") !== false) {
            $message = str_replace("{
                message}", $args['message'], $message);
        }

        $fields = $service->getFieldsWithName(__FUNCTION__);


        // if (strpos($message, "{subject}")) {
            // $message = str_replace("{subject}", $args['subject'], $message);
        // }
        // if (strpos($message, "{ticketid}")) {
            // $message = str_replace("{ticketid}", $args['ticketid'], $message);
        // }
		$sql = "select tid from tbltickets where id = :ticketid";
        $result = $service->getConnection()->runSelectQuery($sql, array(':ticketid' => $args['ticketid']));
        
		$args['ticketmask'] = '';
		if ($result) {
            $row = $result->fetch();
            $args['ticketmask'] = $row['tid'] ? $row['tid'] : '';
        }

        if (strpos($message, "{subject}")) {
            $message = str_replace("{subject}", $args['subject'], $message);
        }
        if (strpos($message, "{ticketid}")) {
            $message = str_replace("{ticketid}", $args['ticketmask'], $message);
        }
		
        if (strpos($message, "{message}")) {
            $message = str_replace("{message}", $args['message'], $message);
        }
        while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
            if (strpos($message, "{" . $field['field'] . "}")) {
                $replaceto = $clientRow[$field['field']];
                $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
            }
        }

        $SMSArray = [];

        $result = $service->clearPhoneNumber($clientRow['gsmnumber']);
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

return $hook;
