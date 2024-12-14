<?php
$hook = array(
    'hook' => 'InvoicePaymentReminder',
    'function' => 'InvoicePaymentReminder_secondoverdue',
    'hook_tr' => 'Ödenmemiş Fatura 2. Hatırlatma',
    'title' => '',
    'description' => array(
        'turkish' => 'Ödenmemiş faturanın ikinci zaman aşımında mesaj gönderir',
        'english' => 'Invoice payment reminder for second overdue'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, {duedate} son odeme tarihli gecikmis bir faturaniz bulunmaktadir. Detayli bilgi icin sitemizi ziyaret edin.',
    'variables' => '{firstname}, {lastname}, {duedate}'
);

if (!function_exists('InvoicePaymentReminder_secondoverdue')) {
    function InvoicePaymentReminder_secondoverdue($args)
    {
        $service = new SmsService();
        if ($args['type'] == "secondoverdue") {
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
                $userSql = "SELECT a.total, a.duedate, c.id as userid, c.firstname, c.lastname ,v.value as gsmnumber
FROM `tblinvoices` as `a`, tblcustomfieldsvalues as v, tblclients as c WHERE c.id = a.userid 
AND c.id= (SELECT userid FROM tblinvoices WHERE id='" . $args['invoiceid'] . "')
AND v.relid = c.id AND a.id = '" . $args['invoiceid'] . "' 
AND v.fieldid = (SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1);";

            } else {
                $userSql = "
        SELECT a.total,a.duedate,b.id as userid,b.firstname,b.lastname,`b`.`country`,`b`.`phonenumber` as `gsmnumber` 
        FROM `tblinvoices` as `a`
        JOIN tblclients as b ON b.id = a.userid
        WHERE a.id = '" . $args['invoiceid'] . "'
        LIMIT 1";
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

            if ($clientRow !== false) {
                $fields = $service->getFieldsWithName(__FUNCTION__);

                if (strpos($message, "{duedate}") !== false) {
                    $message = str_replace("{duedate}", $clientRow['duedate'], $message);
                }

                $message = str_replace("{firstname}", $clientRow['firstname'], $message);
                $message = str_replace("{lastname}", $clientRow['lastname'], $message);
                while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
                    if (strpos($message, "{" . $field['field'] . "}") !== false) {

                        if (trim($field['field']) == 'duedate') {
                            $replaceto = $service->prepareDate($clientRow[$field['field']], $settingsRow['dateformat']);
                        } else {
                            $replaceto = $clientRow[$field['field']];
                        }

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
        } else {
            return false;
        }
    }
}

return $hook;
