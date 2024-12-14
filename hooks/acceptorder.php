<?php
$hook = array(
    'hook' => 'AcceptOrder',
    'function' => 'AcceptOrderr',
    'hook_tr' => 'Sipariş Onay',
    'title' => '',
    'description' => array(
        'turkish' => 'Sipariş onaylandığında Müşteriye mesaj gönderir',
        'english' => 'When an order accepted it sends a message.'
    ),
    'type' => 'client',
    'extra' => '',
    'defaultmessage' => 'Sayin {firstname} {lastname}, {orderid} numarali siparisiniz onaylanmistir. ',
    'variables' => '{firstname},{lastname},{orderid}'
);
if (!function_exists('AcceptOrderr')) {
    function AcceptOrderr($args)
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
            $userSql = "SELECT c.*, v.value as `gsmnumber` FROM tblclients as c, tblcustomfieldsvalues as v 
            WHERE c.id=(SELECT userid FROM tblorders WHERE id='" . $args['orderid'] . "') AND c.id=v.relid AND v.fieldid= 
            (SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1) LIMIT 1;";

        } else {
            $userSql = "SELECT `a`.id,`a`.`firstname`, `a`.`lastname`, `a`.`phonenumber` as `gsmnumber`, `a`.`country`,
        `a`.`companyname`,`a`.`email`,`a`.`address1`, `a`.`address2`,`a`.`city`
        FROM `tblclients` as `a`
        WHERE `a`.`id` IN (SELECT userid FROM tblorders WHERE id = '" . $args['orderid'] . "') LIMIT 1";
        }

        $orderSql = "SELECT ordernum FROM tblorders WHERE id = '" . $args['orderid'] . "'";
        $orderStmt = $service->getConnection()->runSelectQuery($orderSql);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $service->getConnection()->runSelectQuery($userSql);
        if ($stmt == false) {
            return null;
        }
        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($clientRow['gsmnumber'])) {
            return null;
        }

        $blocked = $service->isUserBlockedToSms($clientRow['id']);
        if ($blocked == "1") {
            return null;
        }

        if ($clientRow !== null) {

            $fields = $service->getFieldsWithName(__FUNCTION__);

            if (strpos($message, "{orderid}") !== false) {
                $message = str_replace("{orderid}", $order['ordernum'], $message);
            }

            $message = str_replace("{firstname}", $clientRow['firstname'], $message);
            $message = str_replace("{lastname}", $clientRow['lastname'], $message);

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
}

return $hook;