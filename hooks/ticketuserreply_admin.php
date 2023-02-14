<?php
$hook = array(
    'hook' => 'TicketUserReply',
    'function' => 'TicketUserReply_admin',
    'hook_tr' => 'Ticket Cevabı',
    'title' => '',
    'description' => array(
        'turkish' => 'Bir ticket cevaplandığında yöneticiye mesaj gönderir.',
        'english' => 'When user has replied on the ticket, it sends a message to the admin.'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => '({subject}) konulu ticket cevaplandı.',
    'variables' => '{subject}'
);

if (!function_exists('TicketUserReply_admin')) {
    function TicketUserReply_admin($args)
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

        if (strpos($message, "{subject}") !== false) {
            $message = str_replace("{subject}", $args['subject'], $message);
        }
        if (strpos($message, "{message}") !== false) {
            $message = str_replace("{message}", $args['message'], $message);
        }
        if (strpos($message, "{ticketid}") !== false) {
            $sql = "SELECT * FROM tbltickets where id ='" . $args['ticketid'] . "'; ";
            $stmt = $service->getConnection()->runSelectQuery($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['tid'])) {
                $message = str_replace("{ticketid}", $row['tid'], $message);
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
            if (ctype_digit($phonenumber)) {
                array_push($SMSArray, new SMS($message, trim($phonenumber)));
            }
        }
        if (!empty($SMSArray)) {
            $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
            $request->prepareXMLRequest();
            $request->XMLPOST();
        }
    }
}

return $hook;
