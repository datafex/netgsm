<?php
$hook = array(
    'hook' => 'DailyCronJob',
    'function' => 'DomainRenewalNotice',
    'hook_tr' => 'Alan Adı Kalan Süre Bildirimi',
    'title' => '',
    'description' => array(
        'turkish' => 'Alan adı süresinin dolmasına {x} gün kala mesaj gönderir. {x} değeri kurulumda 15 olarak atanır.',
        'english' => 'It sends a message before {x} days of domain renewal. Default value of {x} is 15.'
    ),
    'type' => 'client',
    'extra' => '15',
    'defaultmessage' => 'Sayin {firstname} {lastname}, {domain} alan adiniz {expirydate}({x} gun sonra) tarihinde sona erecektir. Yenilemek icin sitemizi ziyaret edin. www.netgsm.com.tr',
    'variables' => '{firstname}, {lastname}, {domain},{expirydate},{x}'
);
if (!function_exists('DomainRenewalNotice')) {
    function DomainRenewalNotice($args)
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

        $extra = $templateRow['extra'];

        $sqlDomain = "SELECT  `userid` ,  `domain` ,  `expirydate`
           FROM  `tbldomains`
           WHERE  `status` =  'Active'";

        $domainStmt = $service->getConnection()->runSelectQuery($sqlDomain);

        $SMSArray = [];

        while ($domain = $domainStmt->fetch(PDO::FETCH_ASSOC)) {

            $blocked = $service->isUserBlockedToSms($domain['userid']);
            if ($blocked == "1") {
                continue;
            }

            $message = $templateRow['template'];

            $tarih = explode("-", $domain['expirydate']);
            $yesterday = mktime(0, 0, 0, $tarih[1], $tarih[2] - $extra, $tarih[0]);
            $today = date("Y-m-d");
            if (date('Y-m-d', $yesterday) == $today) {

                if (!empty($templateRow['smsfieldname'])) {
                    $stmt = $service->getClientDetailsWithSmsFieldName($templateRow['smsfieldname'], $domain['userid']);
                } else {
                    $stmt = $service->getClientDetailsBy($domain['userid']);
                }

                $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if (empty($clientRow['phonenumber'])) {
                    return null;
                }
                if (strpos($message, "{x}") !== false) {
                    $message = str_replace("{x}", $extra, $message);
                }
                if (strpos($message, "{expirydate}") !== false) {
                    $message = str_replace("{expirydate}", $domain['expirydate'], $message);
                }
                if (strpos($message, "{domain}") !== false) {
                    $message = str_replace("{domain}", $domain['domain'], $message);
                }

                $message = str_replace("{firstname}", $clientRow['firstname'], $message);
                 $message = str_replace("{lastname}", $clientRow['lastname'], $message);
                $fields = $service->getFieldsWithName(__FUNCTION__);
                while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {

                    if (strpos($message, "{" . $field['field'] . "}") !== false) {
                        $replaceto = $clientRow[$field['field']];
                        $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
                    }
                }

                $result = $service->clearPhoneNumber($clientRow['phonenumber']);
                $phonenumber = $result['phonenumber'];
                $validity = $result['validity'];
                if ($validity === false) {
                    return null;
                }

                if (ctype_digit($phonenumber)) {
                    array_push($SMSArray, new SMS($message, $phonenumber));
                }
            }
        }

        $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
        $request->prepareXMLRequest();
        $request->XMLPOST();
    }
}



return $hook;