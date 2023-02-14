<?php
$hook = array(
    'hook' => 'TicketClose',
    'function' => 'TicketClose',
    'hook_tr' => 'Ticket Kapatılması',
    'title' => '',
    'description' => array(
        'turkish' => 'Ticket kapatıldığında müşteriye mesaj gönderir.',
        'english' => 'When a ticket is closed it sends a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, ({ticketid}) numarali ticket kapatilmistir.',
    'variables' => '{firstname}, {lastname}, {ticketid}',
);

if (!function_exists('TicketClose')) {
    function TicketClose($args)
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
        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }
        $message = $templateRow['template'];
        if (!empty($templateRow['smsfieldname'])) {
            $userSql = "SELECT a.tid, c.id as userid, c.firstname, c.lastname ,v.value as gsmnumber
        FROM `tbltickets` as `a`, tblcustomfieldsvalues as v, tblclients as c WHERE c.id = a.userid 
        AND c.id= (SELECT userid FROM tbltickets WHERE id='" . $args['ticketid'] . "')
        AND v.relid = c.id AND a.id = '" . $args['ticketid'] . "' 
        AND v.fieldid = (SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1);";

        } else {
            $userSql = "
        SELECT a.tid,b.id as userid,b.firstname,b.lastname,`b`.`phonenumber` as `gsmnumber` FROM `tbltickets` as `a`
        JOIN tblclients as b ON b.id = a.userid WHERE a.id = '" . $args['ticketid'] . "'
        LIMIT 1 ";
        }

        $client = $service->getConnection()->runSelectQuery($userSql);
        if ($client == false) {
            return null;
        }
        $clientRow = $client->fetch(PDO::FETCH_ASSOC);

        $blocked = $service->isUserBlockedToSms($clientRow['userid']);
        if ($blocked == "1") {
            return null;
        }

        if (empty($clientRow['gsmnumber'])) {
            return null;
        }

        if (strpos($message, "{subject}") !== false) {
            $message = str_replace("{subject}", $args['subject'], $message);
        }
        if (strpos($message, "{message}") !== false) {
            $message = str_replace("{message}", $args['message'], $message);
        }
        if (strpos($message, "{ticketid}") !== false) {
            $message = str_replace("{ticketid}", $clientRow['tid'], $message);
        }

        $fields = $service->getFieldsWithName(__FUNCTION__);

        while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {

            if (strpos($message, "{" . $field['field'] . "}") !== false) {
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

        if (ctype_digit($phonenumber)) {

            array_push($SMSArray, new SMS($message, $phonenumber));

            $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
            $request->prepareXMLRequest();
            $request->XMLPOST();
        }

    }
}

return $hook;
