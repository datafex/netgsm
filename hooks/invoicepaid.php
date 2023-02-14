<?php
$hook = array(
    'hook' => 'InvoicePaid',
    'function' => 'InvoicePaid',
    'hook_tr' => 'Ödenmiş Fatura',
    'title' => '',
    'description' => array(
        'turkish' => 'Fatura ödendiğinde müşteriye mesaj gönderir',
        'english' => 'After invoice is paid it sends a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, {duedate} son odeme tarihli faturaniz odenmistir. Odemenizi zamanında yaptığınız icin tesekkur ederiz.',
    'variables' => '{firstname}, {lastname}, {duedate},{invoiceid}'
);
if (!function_exists('InvoicePaid')) {
    function InvoicePaid($args)
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
            $userSql = "SELECT a.total, a.duedate, c.id as userid, c.firstname, c.lastname ,v.value as gsmnumber
FROM `tblinvoices` as `a`, tblcustomfieldsvalues as v, tblclients as c WHERE c.id = a.userid 
AND c.id= (SELECT userid FROM tblinvoices WHERE id='" . $args['invoiceid'] . "')
AND v.relid = c.id AND a.id = '" . $args['invoiceid'] . "' 
AND v.fieldid = (SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1);";

        } else {
            $userSql = "
        SELECT a.total,a.duedate,b.id as userid,b.firstname,b.lastname,`b`.`country`,`b`.`phonenumber` as `gsmnumber` FROM `tblinvoices` as `a`
        JOIN tblclients as b ON b.id = a.userid
        WHERE a.id = '" . $args['invoiceid'] . "'";
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

            if (strpos($message, "{invoiceid}") !== false) {
                $message = str_replace("{invoiceid}", $args['invoiceid'], $message);
            }
            if (strpos($message, "{duedate}") !== false) {
                $message = str_replace("{duedate}", $clientRow['duedate'], $message);
            }
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

            $gsm = $clientRow['gsmnumber'];

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
}

return $hook;
