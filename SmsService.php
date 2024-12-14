<?php

include_once('../../../init.php');

use WHMCS\Database\Capsule;

/**
 *  Sms Service
 */
class SmsService
{
    
    private $db;

    function __construct()
    {
        $this->db = DatabaseProcess::getInstance();
    }

    public function getHooksName()
    {
        if ($handle = opendir(dirname(__FILE__) . '/hooks')) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, strlen($entry) - 4, strlen($entry)) == ".php") {
                    $names[] = substr($entry, 0, strlen($entry) - 4);
                }
            }
            closedir($handle);
        }
        return $names;
    }

    public function initializeHooks($hooks = null)
    {
       
        global $CONFIG;
        $whmcsversion = preg_replace("/[^0-9]/", "", trim($CONFIG['Version']));
        $whmcsversion = substr($whmcsversion, 0, 1);

        $hook_name = "";
        if ($hooks == null) {
            $hooks = $this->getHooks();
        }
        $i = 0;
        try {
            $this->db->beginTransaction();

            $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_templates` 
    (`id` int(10) NOT NULL AUTO_INCREMENT,`name` varchar(50) CHARACTER SET utf8 NOT NULL,`name_tr` varchar(50) CHARACTER SET utf8 NOT NULL,
    `smsfieldname` varchar(255) NOT NULL,
    `type` enum('client','admin') CHARACTER SET utf8 NOT NULL,`admingsm` varchar(255) CHARACTER SET utf8 NOT NULL,
    `template` varchar(240) CHARACTER SET utf8 NOT NULL,`title` varchar(16) CHARACTER SET utf8 NOT NULL,
    `variables` varchar(500) CHARACTER SET utf8 NOT NULL,`active` tinyint(1) NOT NULL,`extra` varchar(3) CHARACTER SET utf8 NOT NULL,
    `description` text CHARACTER SET utf8,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
            $this->db->runSqlQuery($query);
            
            foreach ($hooks as $hook) {

                if ($hook['hook'] == "ClientLogin" and $whmcsversion == "8") {
                    $sql = "delete FROM `netgsm_sms_templates` 
                           WHERE `name` = '" . $hook['function'] . "'";
                    $this->db->runSqlQuery($sql);

                    continue;
                }
                if ($hook['hook'] == "UserLogin" and $whmcsversion == "7") {

                    $sql = "delete FROM `netgsm_sms_templates` 
                           WHERE `name` = '" . $hook['function'] . "'";
                    $this->db->runSqlQuery($sql);

                    continue;
                }
                
                $sql_count = "SELECT COUNT(*) FROM `netgsm_sms_templates` 
                 WHERE `name` = '" . $hook['function'] . "' AND `type` = '" . $hook['type'] . "' LIMIT 1";
                $stmt_count = $this->db->runSelectQuery($sql_count);

                if ($stmt_count->fetchColumn() == 0) {
                    if ($hook['type']) {

                        $sql = "INSERT INTO `netgsm_sms_templates`(`id`, `name`,`name_tr`, `smsfieldname`, `type`, `template`, `variables`, `extra`, `description`, `title`, `active`) 
                        VALUES (NULL,'" . $hook['function'] . "','" . $hook['hook_tr'] . "','','" . $hook['type'] . "','" . $hook['defaultmessage'] . "','" . $hook['variables'] . "',
                        '" . (isset($hook['extra']) ? $hook['extra'] : "") . "','" . json_encode(@$hook['description'], JSON_UNESCAPED_UNICODE) . "','" . $hook['title'] . "',0)";
                        $result = $this->db->runSqlQuery($sql);

                        if (!$result) {
                            throw new Exception("Hooks are not initialized");
                        }
                        $i++;
                    }
                }
            }
            $fieldsStatus = $this->setFields();
            $this->db->commit();
            
            return $i;
        } catch (Exception $e) {
            if( $this->db->inTransaction()){
                $this->db->rollBack();
            }
           
            return 0;
        }
    }

    public function getHooks()
    {
        $file = array();
        if ($handle = opendir(dirname(__FILE__) . '/hooks')) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, strlen($entry) - 4, strlen($entry)) == ".php") {
                    array_push($file, include('hooks/' . $entry));
                }
            }
            closedir($handle);
        }
        return $file;
    }

    public function setFields()
    {
        global $CONFIG;
        $whmcsversion = preg_replace("/[^0-9]/", "", trim($CONFIG['Version']));
        $whmcsversion = substr($whmcsversion, 0, 1);

        $query = "INSERT INTO `netgsm_sms_fields` (`id`, `hook_id`, `field`, `field_tr`) VALUES
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'orderid', 'Sipariş Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'companyname', 'Firma Adı'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'email', 'E-mail'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'address1', 'Adres 1'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'address2', 'Adres 2'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'city', 'Şehir'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'country', 'Ülke'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AcceptOrderr' LIMIT 1), 'phonenumber', 'Telefon Numarası'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AdminLogin' LIMIT 1), 'username', 'Kullanıcı Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePackage' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePackage' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePackage' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePassword' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePassword' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleChangePassword' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleCreate_Hosting' LIMIT 1), 'username', 'Kullanıcı Adı'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleCreate_Hosting' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleCreate_Hosting' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleCreate_Hosting' LIMIT 1), 'domain', 'Alan Adı'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleCreate_Hosting' LIMIT 1), 'password', 'Şifre'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleSuspend' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleSuspend' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleSuspend' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleUnsuspend' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleUnsuspend' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterModuleUnsuspend' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistration' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistration' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistration' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistrationFailed' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistrationFailed' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistrationFailed' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistrationFailed_admin' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRegistration_admin' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRenewal' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRenewal' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRenewal' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRenewalFailed_admin' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='AfterRegistrarRenewal_admin' LIMIT 1), 'domain', 'Alan Adı'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd' LIMIT 1), 'password', 'Şifre'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd' LIMIT 1), 'email', 'E-mail'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister'), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister' LIMIT 1), 'code', 'Doğrulama Kodu'),
";

        if ($whmcsversion == "8") {
            $query .= "
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'code', 'Doğrulama Kodu'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'email', 'E-mail'),
";
        } else {
            $query .= "
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'code', 'Doğrulama Kodu'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLogin_admin' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLogin_admin' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLogin_admin' LIMIT 1), 'email', 'E-mail'),
";

        }

        $query .= "
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd_admin' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd_admin' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAdd_admin' LIMIT 1), 'email', 'E-mail'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientChangePassword' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientChangePassword' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientChangePassword' LIMIT 1), 'password', 'Şifre'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='DomainRenewalNotice' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='DomainRenewalNotice' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='DomainRenewalNotice' LIMIT 1), 'domain', 'Alan Adı'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='DomainRenewalNotice' LIMIT 1), 'expirydate', 'Bitiş Tarihi'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='DomainRenewalNotice' LIMIT 1), 'x', 'Kalan Gün'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoiceCreated' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoiceCreated' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoiceCreated' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoiceCreated' LIMIT 1), 'total', 'Tutar'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoiceCreated' LIMIT 1), 'invoiceid', 'Fatura Numarası'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaid' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaid' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaid' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaid' LIMIT 1), 'invoiceid', 'Fatura Numarası'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Firstoverdue' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Firstoverdue' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Firstoverdue' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Reminder' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Reminder' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_Reminder' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_secondoverdue' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_secondoverdue' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_secondoverdue' LIMIT 1), 'firstname', 'İsim'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_thirdoverdue' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_thirdoverdue' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='InvoicePaymentReminder_thirdoverdue' LIMIT 1), 'duedate', 'Son Ödeme Tarihi'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketAdminReply' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketAdminReply' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketAdminReply' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketAdminReply' LIMIT 1), 'subject', 'Konu'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketAdminReply' LIMIT 1), 'message', 'Mesaj'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketClose' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketClose' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketClose' LIMIT 1), 'lastname', 'Soyisim'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen_admin' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen_admin' LIMIT 1), 'subject', 'Konu'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen_admin' LIMIT 1), 'message', 'Mesaj'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketUserReply_admin' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketUserReply_admin' LIMIT 1), 'subject', 'Konu'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketUserReply_admin' LIMIT 1), 'message', 'Mesaj'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'lastname', 'Soyisim');";

        $this->db->beginTransaction();

        $result = $this->db->runSqlQuery($query);

        if ($result) {
            $this->db->commit();
            return true;
        } else {
            $this->db->rollBack();
            return false;
        }
    }

    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public function getTitleAsArray($usercode = '', $password = '')
    {
        include_once('sender/Request.php');

        if (trim($usercode) == '' or trim($password) == '') {

            $stmt = $this->getSettings();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['msgheaders']) and trim($row['msgheaders']) != '') {
                if ($this->isJson($row['msgheaders'])) {
                    return json_decode($row['msgheaders'], true);
                }
            }
            $usercode = $row['usercode'];
            $password = $row['password'];
        }


        $titles = Request::HTTPGET($usercode, $password);

        if ($titles == 30) {
            return 30;
        } else {
            $titles = explode("<br>", $titles);
            $sql = "update netgsm_sms_settings set msgheaders='" . json_encode($titles) . "'";
            $this->getConnection()->runSelectQuery($sql);
        }

        return $titles;
    }

    public function getSettings()
    {
        return $this->db->runSelectQuery("SELECT * FROM `netgsm_sms_settings`");
    }

    public function countClients($query)
    {
        $result = $this->getConnection()->runSelectQuery($query);
        return $result->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getConnection()
    {
        return $this->db;
    }

    public function getClientAllGroupNames()
    {
        $query = "SELECT groupname FROM tblclientgroups";
        return $this->getConnection()->runSelectQuery($query);
    }

    public function updateSettings($u, $p, $f, $d, $blockedsmsfieldname = '', $blockedotpsmsfieldname = '', $loginredirectpage = '', $smslanguage = '', $date_format = '')
    {
        try {
            $conn = DatabaseProcess::getInstance();
            $sql = "UPDATE netgsm_sms_settings SET usercode=? , password=?, smsfieldname=?, defaultmsgheader=?,  blockedsmsfieldname = ?,blockedotpsmsfieldname = ? ,loginredirectpage =?, smslanguage = ?,dateformat = ?";
            $stmt = $conn->runSelectQuery($sql, array($u, $p, $f, $d, $blockedsmsfieldname, $blockedotpsmsfieldname, $loginredirectpage, $smslanguage, $date_format));
            if ($stmt != false and $stmt->rowCount() > 0) {
                $sql = "UPDATE netgsm_sms_templates SET title = ?";
                $stmt = $conn->runSelectQuery($sql, array($d));
            }
            if ($stmt != false) {
                return array('status' => true, 'description' => 'Güncelleme Başarılı.');
            } else {
                return array('status' => false, 'description' => 'Güncelleme Başarısız!', 'error' => $stmt->errorInfo());
            }
        } catch (Exception $e) {
            return array('status' => false, 'description' => $e->getMessage() . ' Güncelleme Başarısız!');
        }
    }

    public function getTemplateDetailsTR($template = null)
    {
        $query = "SELECT * FROM netgsm_sms_templates WHERE name_tr ='" . $template . "';";
        $result = $this->db->runSelectQuery($query);
        return $result;
    }

    public function getTemplateDetails($template = null)
    {
        $query = "SELECT * FROM `netgsm_sms_templates` WHERE `name` ='" . $template . "'";
        $result = $this->getConnection()->runSelectQuery($query);
        return $result;
    }

    public function getFields($templateId)
    {
        $sql = "SELECT `field`, `field_tr` FROM `netgsm_sms_fields` WHERE `hook_id`='" . $templateId . "';";

        return $this->db->runSelectQuery($sql);
    }

    public function getFieldsWithName($templateName)
    {
        $sql = "SELECT `field`, `field_tr` FROM `netgsm_sms_fields` WHERE `hook_id`=
                (SELECT id FROM netgsm_sms_templates WHERE name = '" . $templateName . "')";
        return $this->db->runSelectQuery($sql);
    }

    public function getAllTemplate()
    {
        $query = "SELECT `name`,`name_tr`,`type` FROM netgsm_sms_templates ORDER BY name_tr ASC";
        return $this->db->runSelectQuery($query);
    }

    public function getAllTemplates($type = "client")
    {
        $query = "SELECT `id`, `name`, `name_tr`, `type`, `admingsm`, `smsfieldname`, `template`, `title`, `variables`, `active`, `extra`, `description` FROM netgsm_sms_templates WHERE type ='" . $type . "' ORDER BY id ASC";
        if ($type === "admin") {
            $query = "SELECT `id`, `name`, `name_tr`, `type`, `admingsm`, `smsfieldname`, `template`, `title`, `variables`, `active`, `extra`, `description` FROM netgsm_sms_templates WHERE type ='" . $type . "' ORDER BY id ASC";
        }
        return $this->db->runSelectQuery($query);
    }

    public function XMLPOST($numbers, $message, $title = null)
    {
        
        
        $flag = false;

        include_once('sender/SMS.php');
        include_once('sender/Request.php');

        $SMSArray = [];
        $replaceFrom = "";
        $replaceTo = "";

        $settings = $this->getSettings();
        $row = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$row['usercode'] || !$row['password']) {
            return false;
        }
        $usercode = $row['usercode'];
        $password = $row['password'];

        if (is_null($title))
            $title = $row['defaultmsgheader'];

        $stmt = $this->getClientColumnNames();
        $columnNames = $stmt->fetchAll();
      
        for ($i = 0; $i < count($numbers); $i++) {
            $newMessage = $message;
            $arr = explode("_", $numbers[$i]);
            $client = $this->getClientDetailsBy($arr[0]);
            $row = $client->fetch(PDO::FETCH_ASSOC);

            $result = $this->clearPhoneNumber($arr[1]);
            $phonenumber = $result['phonenumber'];
            $validity = $result['validity'];
            if ($validity === false) {
                continue;
            }
           
            if (ctype_digit($phonenumber)) {


                foreach ($columnNames as $column) {

                    $columnName = $column['COLUMN_NAME'];

                    if (strpos($newMessage, "{" . $columnName . "}") === false) {

                    } else {
                        $newMessage = str_replace("{" . $columnName . "}", $row[$columnName], $newMessage);
                    }
                }

                array_push($SMSArray, new SMS($newMessage, $phonenumber));
                $flag = true;
            }
        }
       
        if ($flag == true) {
            $request = new Request($title, $SMSArray, $usercode, $password);
            $request->prepareXMLRequest();
            return $request->XMLPOST();
        } else {
            return false;
        }
    }

    public function XMLPOSTOTP($number, $message, $title)
    {

    }

    public function getClientColumnNames()
    {
        $sql = "select COLUMN_NAME from INFORMATION_SCHEMA.COLUMNS 
                where TABLE_NAME = 'tblclients';";

        return $this->db->runSelectQuery($sql);
    }

    public function getClientDetailsBy($clientId)
    {
        $userSql = "SELECT * FROM `tblclients` as `a` WHERE `a`.`id`  = '" . $clientId . "'
        LIMIT 1";
        return $this->db->runSelectQuery($userSql);
    }

    public function getClientDetailsWithSmsFieldName($smsfieldname, $clientId)
    {
        $sql = "SELECT c.firstname, c.lastname, c.companyname, c.email, c.address1, c.address2, c.city,
c.state, c.postcode, c.country, c.status, v.value as phonenumber
FROM tblclients as c, tblcustomfieldsvalues as v WHERE c.id='" . $clientId . "' AND c.id=v.relid AND v.fieldid= 
(SELECT id from tblcustomfields where fieldname='" . $smsfieldname . "' AND type = 'client' AND fieldtype = 'text' LIMIT 1) LIMIT 1;";

        return $this->db->runSelectQuery($sql);
    }

    public function isUserBlockedToSms($userid)
    {
        $settings = $this->getSettings();
        $settings_row = $settings->fetch(PDO::FETCH_ASSOC);

        if (trim($settings_row['blockedsmsfieldname']) == '') {
            return "0";
        }

        $sql = "select case when v.value='on' then '1' else '0' end as blocked 
                from tblclients as c 
                inner join tblcustomfieldsvalues as v on v.relid=c.id 
                where c.id=:usr and v.fieldid=(SELECT id from tblcustomfields where fieldname=:field AND type = 'client' LIMIT 1)";

        $stmt = $this->db->runSelectQuery($sql, array(':usr' => $userid, ':field' => $settings_row['blockedsmsfieldname']));
        $row = $stmt->fetch();

        return $row['blocked'];
    }

    public function isUserBlockedToOtpSms($userid, $phonenumber)
    {
        $settings = $this->getSettings();
        $settings_row = $settings->fetch(PDO::FETCH_ASSOC);

        if (trim($settings_row['blockedotpsmsfieldname']) == '') {
            return "0";
        }

        $sql = "select case when v.value='on' and 
                (select durum from netgsm_sms_otp where userid=c.id and phonenumber=:phone LIMIT 1) = '1'
                then '1' else '0' end as blocked 
                from tblclients as c 
                inner join tblcustomfieldsvalues as v on v.relid=c.id 
                where c.id=:usr and v.fieldid=(SELECT id from tblcustomfields where fieldname=:field AND type = 'client' LIMIT 1)";

        $stmt = $this->db->runSelectQuery($sql, array(':usr' => $userid, ':phone' => $phonenumber, ':field' => $settings_row['blockedotpsmsfieldname']));
        $row = $stmt->fetch();

        return $row['blocked'];
    }

    public function updateTemplate($hookId, $title, $message, $admingsm, $extra, $smsfieldname)
    {
        if (empty($hookId))
            return false;
        $sql = "UPDATE `netgsm_sms_templates` SET `smsfieldname` = '" . $smsfieldname . "' ,`extra` = '" . $extra . "' , `admingsm` = '" . $admingsm . "'  ,`title` = '" . $title . "', `template` = '" . $message . "'
         WHERE `id` = '" . $hookId . "'";
        return $this->db->runSqlQuery($sql);
    }

    public function changeTemplateStatus($templateId, $status)
    {
        $sql = "UPDATE netgsm_sms_templates SET active = " . $status . " WHERE id = '" . $templateId . "';";
        return $this->getConnection()->runSqlQuery($sql);
    }

    public function preparePaginationLinks($count, $limit, $page, $function_name = "getClients")
    {
        $output = '';

//        $output.=  $count." ".$limit." ".$page;

        $pages = ceil($count / $limit);

        if ($pages > 1) {

            if ($page == 1)
                $output = $output . '<span class="link first disabled">&#8810;</span><span class="link disabled">&#60;</span>';
            else
                $output = $output . '<a class="link first" onclick="' . $function_name . '(1)" >&#8810;</a><a class="link" onclick="' . $function_name . '(' . ($page - 1) . ')">&#60;</a>';

            if (($page - 3) > 0) {
                if ($page == 1)
                    $output = $output . '<span id=1 class="link current">1</span>';
                else
                    $output = $output . '<a class="link" onclick="' . $function_name . '(1)" >1</a>';
            }
            if (($page - 3) > 1) {
                $output = $output . '<span class="dot">...</span>';
            }

            for ($i = ($page - 2); $i <= ($page + 2); $i++) {
                if ($i < 1) continue;
                if ($i > $pages) break;
                if ($page == $i)
                    $output = $output . '<span id=' . $i . ' class="link current">' . $i . '</span>';
                else
                    $output = $output . '<a class="link" onclick="' . $function_name . '(' . $i . ')" >' . $i . '</a>';
            }

            if (($pages - ($page + 2)) > 1) {
                $output = $output . '<span class="dot">...</span>';
            }
            if (($pages - ($page + 2)) > 0) {
                if ($page == $pages)
                    $output = $output . '<span id=' . ($pages) . ' class="link current">' . ($pages) . '</span>';
                else
                    $output = $output . '<a class="link" onclick="' . $function_name . '(' . $pages . ')" >' . ($pages) . '</a>';
            }

            if ($page < $pages)
                $output = $output . '<a  class="link" onclick="' . $function_name . '(' . ($page + 1) . ')" >></a><a  class="link" onclick="' . $function_name . '(' . $pages . ')" >&#8811;</a>';
            else
                $output = $output . '<span class="link disabled">></span><span class="link disabled">&#8811;</span>';

        }
        return $output;

    }

    public function getClients($limit = 10, $start = 0)
    {
        return $this->getConnection()->runSelectQuery("SELECT * FROM tblclients LIMIT " . $start . ", " . $limit);
    }

    public function getSelectAndCountQuery($inputs, $limit = 10, $start = 0)
    {
        $names = null;
        $email = null;
        $phonenumber = null;
        $status = null;
        $group = null;
        $settings = $this->getSettings();
        $row = $settings->fetch(PDO::FETCH_ASSOC);

        if (isset($inputs)) {
            if (!empty($inputs['inputName']))
                $names = $inputs['inputName'];
            if (!empty($inputs['inputEmail']))
                $email = $inputs['inputEmail'];
            if (!empty($inputs['inputPhone']))
                $phonenumber = $inputs['inputPhone'];
            if (!empty($inputs['inputGroup']))
                $group = $inputs['inputGroup'];
            if (!empty($inputs['inputStatus']))
                $status = $inputs['inputStatus'];
            if (!empty($inputs['inputLimit']))
                $limit = $inputs['inputLimit'];
            if (!empty($inputs['page']))
                $start = $limit * ($inputs['page'] - 1);

            if (!empty($row['smsfieldname']) && $inputs['inputPhoneType'] === $row['smsfieldname']) {
                $countQuery = "SELECT COUNT(c . id) as total FROM tblclients as c, tblcustomfieldsvalues as v WHERE c . id = v . relid ";
                $selectQuery = "SELECT c . id, c . firstname, c . lastname, c . email, c . country, c . city, c . companyname, c . status, v . value as phone
            FROM tblclients as c, tblcustomfieldsvalues as v WHERE c . id = v . relid ";
                $query = "";

                if ($names !== null) {
                    $query .= " AND (c . firstname LIKE '%" . $names . "%' OR c . lastname LIKE '%" . $names . "%' OR c . companyname LIKE '%" . $names . "%') ";
                }
                if ($email !== null) {
                    $query .= " AND c . email LIKE '%" . $email . "%' ";
                }
                if ($phonenumber !== null) {
                    $query .= " AND v . value LIKE '%" . $phonenumber . "%' ";
                }
                if ($status !== null) {
                    $query .= " AND c . status = '" . $status . "' ";
                }
                if ($group !== null) {
                    $query .= " AND c . groupid = (SELECT `id` FROM `tblclientgroups` WHERE groupname = '" . $group . "')";
                }

                $query .= " AND v . fieldid = (SELECT id from tblcustomfields 
            where fieldname = '" . $row['smsfieldname'] . "' AND type = 'client' AND fieldtype = 'text' LIMIT 1) ";
                $countQuery .= $query;
                $selectQueryWithoutLimit = $selectQuery . $query;
                $query .= " LIMIT " . $start . ", " . $limit;
                $selectQuery .= $query;
            } else {
                $countQuery = "SELECT COUNT(*) as total FROM `tblclients`";
                $selectQuery = "SELECT `id`, `firstname`, `lastname`, `companyname`, `email`,`status`,`phonenumber` as phone FROM `tblclients` ";
                $query = " where 1=1 ";
                $i = 0;

                if ($names !== null) {
                    $query .= " and (firstname LIKE '%" . $names . "%' OR lastname LIKE '%" . $names . "%' OR companyname LIKE '%" . $names . "%') ";
                    $i = 1;
                }
                if ($email !== null) {
                    $query .= " AND email LIKE '%" . $email . "%' ";
                }

                if ($phonenumber !== null) {
                    $query .= " AND phonenumber LIKE '%" . $phonenumber . "%' ";
                }

                if ($status !== null) {
                    $query .= " AND status = '" . $status . "' ";
                }

                if ($group !== null) {
                    $query .= " AND groupid = (SELECT `id` FROM `tblclientgroups` WHERE groupname = '" . $group . "')";
                }

                $countQuery .= $query;
                $selectQueryWithoutLimit = $selectQuery . $query;
                $query .= " LIMIT " . $start . ", " . $limit;
                $selectQuery .= $query;
            }


            return array("selectQuery" => $selectQuery, "countQuery" => $countQuery, "selectQueryWithoutLimit" => $selectQueryWithoutLimit);

        } else {
            return null;
        }


    }

    public function getUserInfoByUserId($userid = null, $fieldname = null)
    {
        if (is_numeric($userid)) {
            if (!is_null($fieldname)) {
                $sql = "SELECT c . id,c . firstname,c . lastname,c . email,c . country,c . city,c . companyname,c . status,v . value as phone
                FROM tblclients as c,tblcustomfieldsvalues as v WHERE c . id = v . relid AND v . fieldid = (SELECT id from tblcustomfields 
                where fieldname =? AND type = 'client' AND fieldtype = 'text' LIMIT 1)
                and c . id =?";
                $stmt = $this->db->runSelectQuery($sql, array($fieldname, $userid));
                $row = $stmt->fetch();
                return $row;
            } else {
                $sql = " select c . id, c . firstname,c . lastname,c . email,c . country,c . city,c . companyname,c . status,c . phonenumber as phone from tblclients as c where id = ?";
                $stmt = $this->db->runSelectQuery($sql, array($userid));
                $row = $stmt->fetch();
                return $row;
            }
        }

        return false;
    }

    public function getOtpListQueries($otp_inputs, $limit = 10, $start = 0)
    {
        $page = $otp_inputs['page'];
        $inputPhone = $otp_inputs['inputPhone'];
        $inputUser = $otp_inputs['inputUser'];
        $inputStatus = $otp_inputs['inputStatus'];

        $where_array = array();
        $array_execute = array();

        if (isset($otp_inputs) and !empty($otp_inputs)) {

            if (isset($inputUser) and $inputUser != '') {
                $where_array[] = " userid =:usr";
                $array_execute[':usr'] = $inputUser;
            }
            if (isset($inputPhone) and $inputPhone != '') {
                $where_array[] = " phonenumber = :phone ";
                $array_execute[':phone'] = $inputPhone;
            }
            if (isset($inputStatus) and $inputStatus !== '') {
                $where_array[] = " durum =:durum";
                $array_execute[':durum'] = $inputStatus;
            }

        } else {
            return null;
        }

        if (!empty($where_array))
            $where_array = " where " . implode(' and ', $where_array);
        else
            $where_array = '';

        if (!empty($otp_inputs['page']))
            $start = $limit * ($otp_inputs['page'] - 1);

        $sql = "select * from netgsm_sms_otp " . $where_array . ' order by start_otp_date desc';
        $sql_limitless = $sql;
        $sql = $sql . ' LIMIT ' . $start . ', ' . $limit;

        $sql_count = "select count(id) as toplam from netgsm_sms_otp " . $where_array;

        return array("selectQuery" => $sql, "countQuery" => $sql_count, "selectQueryWithoutLimit" => $sql_limitless, 'array_execute' => $array_execute);

    }

    public function getPhoneNumberByUseridAndTemplateName($userid = null, $templatename)
    {
        $template = $this->getTemplateDetails($templatename);
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        $gsm = '';
        if (!empty($templateRow['smsfieldname'])) {

            $userSql = "SELECT c.*, v.value as `gsmnumber` FROM tblclients as c, tblcustomfieldsvalues as v
			WHERE c.id='" . $userid . "' AND c.id=v.relid AND v.fieldid=(SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1) LIMIT 1;";

//            print_r($userSql);

            $stmt = $this->getConnection()->runSelectQuery($userSql);
            $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($clientRow !== null) {
                $gsm = $clientRow['gsmnumber'];
            }

        }

        if (empty($gsm)) {
            $stmt = $this->getClientDetailsBy($userid);
            $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $gsm = $clientRow['phonenumber'];
            $gsmx = explode('.', $gsm);
            if (!empty($gsmx[1]))
                $gsm = $gsmx[1];
        }


        $gsm = str_replace(" ", "", $gsm);
        $gsm = str_replace("-", "", $gsm);

        if (substr($gsm, 0, 1) === "+") {
            $gsm = substr($gsm, 3);
        } else if (substr($gsm, 0, 1) === "9") {
            $gsm = substr($gsm, 2);
        }

        return $gsm;

    }

    public function getOrijinalPhoneNumberByUseridAndTemplateName($userid = null, $templatename)
    {
        $template = $this->getTemplateDetails($templatename);
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        $gsm = '';
        if (!empty($templateRow['smsfieldname'])) {

            $userSql = "SELECT c.*, v.value as `gsmnumber` FROM tblclients as c, tblcustomfieldsvalues as v
			WHERE c.id='" . $userid . "' AND c.id=v.relid AND v.fieldid=(SELECT id FROM tblcustomfields WHERE fieldname='" . $templateRow['smsfieldname'] . "' AND type='client' AND fieldtype='text' LIMIT 1) LIMIT 1;";
            $stmt = $this->getConnection()->runSelectQuery($userSql);
            $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($clientRow)) {
                $gsm = $clientRow['gsmnumber'];
            }
        }

        if (empty($gsm)) {
            $stmt = $this->getClientDetailsBy($userid);
            if (!empty($stmt)) {
                $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $gsm = $clientRow['phonenumber'];
            }
        }

        return $gsm;

    }
   
    public function clearPhoneNumber($phonenumber)
    {
        $validity = false;
        $phonenumber = trim($phonenumber);
        $phonenumber = preg_replace("/[^0-9]/", "", $phonenumber);
//        $phonenumber = str_replace(" ", "", $phonenumber);
//        $phonenumber = str_replace("-", "", $phonenumber);

        if (mb_strlen($phonenumber) > 10) {
            $phonenumberx = substr($phonenumber, 0, -10);

            if ($phonenumberx == '90' or $phonenumberx === "0") {
                $validity = true;
            }
        } elseif (mb_strlen($phonenumber) == 10 and substr($phonenumber, 0, 1) == "5") {
            $validity = true;
        }

        $phonenumber = substr($phonenumber, -10);

        return array('phonenumber' => $phonenumber, 'validity' => $validity);
    }

    public function prepareSmsMessage($userid, $message, $templateName, $code)
    {
       
        $stmt = $this->getClientDetailsBy($userid);
        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $fields = $this->getFieldsWithName($templateName);
        if (strpos($message, "{code}") !== false) {
            $message = str_replace("{code}", $code, $message);
        }

        $message = str_replace("{firstname}", $clientRow['firstname'], $message);
        $message = str_replace("{lastname}", $clientRow['lastname'], $message);
       
        
       
        while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
            if (strpos($message, "{" . $field['field'] . "}") !== false) {
                $replaceto = $clientRow[$field['field']];
                $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
            }
        }

        $message = str_replace("{", "", $message);
        $message = str_replace("}", "", $message);

        return $message;
    }

    public function sendOtpCode($userid, $message, $phonenumber, $code, $templateRow, $settingsRow)
    {
        
        $remaining_time = $this->getDefaultOtpRemainingTime();
        $SMSArray = array();
        array_push($SMSArray, new SMS($message, $phonenumber));
        $reference_code = sha1($phonenumber . time() . $userid . $code);


        $sql = "SELECT `id`, `verification_code`, `reference_code`, `userid`, `phonenumber`, `start_otp_date`, `durum` 
                    FROM `netgsm_sms_otp`
                    WHERE phonenumber=:gsm and userid=:usr LIMIT 1";
        $stmt = $this->getConnection()->runSelectQuery($sql, array(':gsm' => $phonenumber, ':usr' => $userid));
        $otp_row = $stmt->fetch();

        if ($otp_row) {

            $current_reference = $otp_row['reference_code'];
            $otp_id = $otp_row['id'];

            $sql = "update `netgsm_sms_otp` set durum=0,islogin=0,start_otp_date=now(),end_otp_date=(select date_add(now(),INTERVAL " . $remaining_time . " SECOND)), reference_code=:new_reference,verification_code=:code where id=:otp_id and reference_code=:reference and userid=:usr";
            $array_execute = array(':otp_id' => $otp_id, ':new_reference' => $reference_code, ':code' => $code, ':reference' => $current_reference, ':usr' => $userid);
            $stmt = $this->getConnection()->runSelectQuery($sql, $array_execute);

        } else {
            $sql = "INSERT INTO `netgsm_sms_otp`(`verification_code`, `reference_code`, `userid`, `phonenumber`,`start_otp_date`,`end_otp_date`,`islogin`) VALUES ('" . $code . "','" . $reference_code . "','" . $userid . "','" . $phonenumber . "',now(),date_add(now(),INTERVAL " . $remaining_time . " SECOND),0)";
            $this->getConnection()->runSelectQuery($sql);
        }

        
        $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
        
        if ($request->XMLPOSTOTP() !== true) {
//            return false;
        }

        return $reference_code;
    }

    private function getDefaultOtpRemainingTime()
    {
        $remaining_time = 120;

        return $remaining_time;
    }

    private function getDefaultDestination()
    {
        $query = "select loginredirectpage from netgsm_sms_settings 
                  where loginredirectpage!='' and LENGTH(loginredirectpage)>0  limit 1";
        $result = $this->getConnection()->runSelectQuery($query);

        $defaultDestination = null;

        if ($result !== false) {
            $row = $result->fetch();
            $defaultDestination = $row['loginredirectpage'];
        }

        return $defaultDestination;
    }

    public function createSSOToken($client_id, $destination = null)
    {
        if (is_null($destination)) {
            $destination = $this->getDefaultDestination();
        }

        $command = 'CreateSsoToken';
        $postData = array(
            'client_id' => $client_id,
        );

        if ($destination != '') {
            $postData['destination'] = $destination;
        }

//        $adminUsername = 'ADMIN_USERNAME'; // Optional for WHMCS 7.2 and later

        $results = localAPI($command, $postData);
        return $results;
    }

    public function getClientAreaPageList()
    {
        $page_list = array();
        $page_list [] = array('scope_name' => '', 'destination' => 'Ana Sayfa');
        $page_list [] = array('scope_name' => 'clientarea:profile', 'destination' => 'Profil');
        $page_list [] = array('scope_name' => 'clientarea:billing_info', 'destination' => 'Fatura Bilgileri');
        $page_list [] = array('scope_name' => 'clientarea:invoices', 'destination' => 'Faturalar');
        $page_list [] = array('scope_name' => 'clientarea:announcements', 'destination' => 'Duyurular');
        $page_list [] = array('scope_name' => 'clientarea:domains', 'destination' => 'Alan Adları');

        return $page_list;
    }

    public function getDateFormats()
    {
        $date_formats = array('d.m.Y', 'Y-m-d');

        return $date_formats;
    }

    public function prepareDate($date, $format = 1)
    {
        if ($format == 1) {
            $date = date('d.m.Y', strtotime($date));
        } elseif ($format == 2) {
            $date = date('Y-m-d', strtotime($date));
        }

        return $date;
    }

    public function getActiveReferenceCode($clientId)
    {
        $sql = "select nso.reference_code
                from netgsm_sms_otp as nso
                inner join tblclients as c on c.id=nso.userid
                where c.id =:clientid and DATE_ADD(now(), INTERVAL 5 second)<nso.end_otp_date 
                limit 1";

        $stmt = $this->db->runSelectQuery($sql, array(':clientid' => $clientId));

        if ($stmt !== false) {
            $row = $stmt->fetch();

            if ($row !== false and $row['reference_code'] != "") {
                return $row['reference_code'];
            }
        }
        return false;
    }
}

class DatabaseProcess
{
    private static $connection = null;

    function __construct()
    {
        try {
            $this->connection = Capsule::connection()->getPdo();
        } catch (PDOException $e) {
            die("PDO CREATING EXCEPTION::: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$connection == null)
            self::$connection = new DatabaseProcess();

        return self::$connection;
    }

    function runSelectQuery($sql, $parameter = null)
    {
        $conn = $this->connection;
        $stmt = $conn->prepare($sql);
        if (is_null($parameter))
            $result = $stmt->execute();
        else
            $result = $stmt->execute($parameter);

        return $result ? $stmt : $result;
    }

    function runSqlQuery($sql, $parameter = null)
    {
        $conn = $this->connection;
        $stmt = $conn->prepare($sql);
        if (is_null($parameter))
            $result = $stmt->execute();
        else
            $result = $stmt->execute($parameter);
        return $result;
    }

    function beginTransaction()
    {
        $this->connection->beginTransaction();
    }
    
    function inTransaction()
    {
        $this->connection->inTransaction();
    }

    function commit()
    {
        $this->connection->commit();
    }

    function rollBack()
    {
        $this->connection->rollBack();
    }

}

?>