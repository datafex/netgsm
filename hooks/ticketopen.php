<?php
/**
 * Created by PhpStorm.
 * User: Engin BAKIR
 * Date: 27.01.2020
 * Time: 14:54
 */

$hook = array(
    'hook' => 'TicketOpen',
    'function' => 'TicketOpen',
    'hook_tr' => 'Yeni Ticket Açılması',
    'title' => '',
    'description' => array(
        'turkish' => 'Bir ticket açıldığında müşteriye mesaj gönderir.',
        'english' => 'When a ticket is created it sends a message..'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, ({ticketid}) numarali ticketınız gönderilmiştir.',
    'variables' => '{firstname}, {lastname}, {ticketid}'
);

if (!function_exists('TicketOpen')) {
    function TicketOpen($args)
    {
        $userid = $args['userid'];
        $service = new SmsService();

        $blocked = $service->isUserBlockedToSms($userid);
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

            $message = str_replace("{ticketid}", $args['ticketmask'], $message);

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
    }
}

return $hook;
