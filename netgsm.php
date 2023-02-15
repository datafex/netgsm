<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

include_once('../../../init.php');
include_once('SmsService.php');

//  use WHMCS\Database\Capsule;
use Illuminate\Database\Capsule\Manager as Capsule;

use WHMCS\Module\Addon\Netgsm\Client\ClientDispatcher;

date_default_timezone_set("Asia/Baghdad");
$sondatecalc = date_format(new DateTime(), 'd-m-Y H:i:s') . substr((string)microtime(), 1, 8);

function netgsm_config()
{
    $configarray = array(
        "name" => "NETGSM SMS",
        "description" => "NETGSM Toplu ve Otomatik Sms Eklentisi",
        "version" => "2.2",
        "author" => "NETGSM",
        "language" => "turkish",
    );
    return $configarray;
}

function netgsm_activate()
{
    
    netgsm_deactivate();
        
        $conn = Capsule::connection()->getPdo();
        $conn->beginTransaction();
        $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_settings` 
    (`id` int(10) NOT NULL AUTO_INCREMENT,`usercode` varchar(255) NOT NULL,`smsfieldname` varchar(255) NOT NULL,`password` varchar(255) NOT NULL,`defaultmsgheader` varchar(255) NOT NULL, `autoauthkey` varchar(255) NOT NULL, `blockedsmsfieldname` varchar(255) NOT NULL, `blockedotpsmsfieldname` varchar(255) NOT NULL,
    `gsmnumberfield` int(11) DEFAULT NULL,`dateformat` varchar(12) CHARACTER SET utf8 DEFAULT NULL,
    `version` varchar(6) CHARACTER SET utf8 DEFAULT NULL,`loginredirectpage` varchar(255) NOT NULL, 
    `loginpage` varchar(255) NOT NULL, `smslanguage` varchar(25) CHARACTER SET utf8 DEFAULT 'tr',
    `msgheaders` varchar(255) CHARACTER SET utf8 ,
     PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() ? true : false;
        
        $query = "INSERT INTO `netgsm_sms_settings` (`usercode`,`smsfieldname`, `password`, `defaultmsgheader`,`gsmnumberfield`,`dateformat`, `version`,`loginredirectpage`,`loginpage`) 
        VALUES ('', '','','', 0,'1','1.0','','');";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() ? ($result && true) : false;
        
        $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_templates` 
    (`id` int(10) NOT NULL AUTO_INCREMENT,`name` varchar(50) CHARACTER SET utf8 NOT NULL,`name_tr` varchar(50) CHARACTER SET utf8 NOT NULL,
    `smsfieldname` varchar(255) NOT NULL,
    `type` enum('client','admin') CHARACTER SET utf8 NOT NULL,`admingsm` varchar(255) CHARACTER SET utf8 NOT NULL,
    `template` varchar(240) CHARACTER SET utf8 NOT NULL,`title` varchar(16) CHARACTER SET utf8 NOT NULL,
    `variables` varchar(500) CHARACTER SET utf8 NOT NULL,`active` tinyint(1) NOT NULL,`extra` varchar(3) CHARACTER SET utf8 NOT NULL,
    `description` text CHARACTER SET utf8,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute() ? ($result && true) : false;

        $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_fields`
        (`id` int(10) NOT NULL AUTO_INCREMENT,`hook_id` int(11) NOT NULL ,
        `field` varchar(100) CHARACTER SET utf8 NOT NULL,`field_tr` varchar(100) CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute() ? ($result && true) : false;
        $stmt = null;
        
        $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_otp`
        (`id` int(10) NOT NULL AUTO_INCREMENT,`verification_code` varchar(6) CHARACTER SET utf8 NOT NULL,
        `reference_code` varchar(40) CHARACTER SET utf8 NOT NULL,`userid` int(10) NOT NULL,`phonenumber` varchar(20) CHARACTER SET utf8 NOT NULL,
        `start_otp_date` datetime NOT NULL,`end_otp_date` datetime NOT NULL, `durum` tinyint(2) NOT NULL DEFAULT 0,`islogin` tinyint NOT NULL,
        PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute() ? ($result && true) : false;
        $stmt = null;

        include_once('SmsService.php');
        $service = new SmsService();
        $hooks_number = $service->initializeHooks();
        if ($result) {
            
            if( $conn->inTransaction()){
                $conn->commit();
            }
            $conn = null;
            return array('status' => 'success', 'description' => 'NETGSM SMS Addon Successfully Activated.');
        } else {
            
            if( $conn->inTransaction()){
                $conn->rollBack();
            }
           
            $conn = null;
            return array('status' => 'error', 'description' => 'NETGSM SMS Addon Activation Failed !');
        }
        
        
       
        
        $service = null;
        
        if ($fieldsStatus == true) {
            $conn = null;
            return array('status' => 'success', 'description' => 'NETGSM SMS Addon Successfully Activated.');
        } else {
            throw new Exception('NETGSM SMS Addon Activation Failed!  ');
        }

}

function netgsm_deactivate()
{
    $conn = Capsule::connection()->getPdo();
   
 

    $query = "DROP TABLE IF EXISTS `netgsm_sms_templates`;";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute() ? true : false;
    

    $query = "DROP TABLE IF EXISTS `netgsm_sms_settings`;";
    $stmt = null;
    $stmt = $conn->prepare($query);
    $result = $stmt->execute() ? ($result && true) : false;

    $query = "DROP TABLE IF EXISTS `netgsm_sms_fields`;";
    $stmt = null;
    $stmt = $conn->prepare($query);
    $result = $stmt->execute() ? ($result && true) : false;

    $query = "DROP TABLE IF EXISTS `netgsm_sms_otp`;";
    $stmt = null;
    $stmt = $conn->prepare($query);
    $result = $stmt->execute() ? ($result && true) : false;

    if ($result) {
        if( $conn->inTransaction()){
            $conn->commit();
        }
        $conn = null;
        return array('status' => 'success', 'description' => 'NETGSM SMS Addon Successfully Deactivated.');
    } else {
        if( $conn->inTransaction()){
            $conn->rollBack();
        }
       
        $conn = null;
        return array('status' => 'error', 'description' => 'NETGSM SMS Addon Deactivation Failed !');
    }

}

function netgsm_upgrade($vars)
{
    global $CONFIG;
    $whmcsversion = preg_replace("/[^0-9]/", "", trim($CONFIG['Version']));
    $whmcsversion = substr($whmcsversion, 0, 1);

    $service = new SmsService();

    $settings = $service->getSettings();
    $settingsRow = $settings->fetch();
    $pdo = $service->getConnection();
    $version = $vars['version'];

    if ($version < 1.6) {

        $query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'netgsm_sms_settings' AND column_name = 'defaultmsgheader'";
        $stmt = $pdo->runSelectQuery($query);
        $row = $stmt->fetch();
        if ($row == false) {
            $query = "ALTER TABLE `netgsm_sms_settings` ADD `defaultmsgheader` varchar(255) NOT NULL;";
            $pdo->runSqlQuery($query);
        }
    }

    if ($version < 1.8) {

        $query = "ALTER TABLE `netgsm_sms_settings` ADD `autoauthkey` varchar(255) NOT NULL;";
        $pdo->runSqlQuery($query);
        $query = "ALTER TABLE `netgsm_sms_settings` ADD `blockedsmsfieldname` varchar(255) NOT NULL;";
        $pdo->runSqlQuery($query);
        $query = "ALTER TABLE `netgsm_sms_settings` ADD `loginredirectpage` varchar(255) NOT NULL;";
        $pdo->runSqlQuery($query);
        $query = "ALTER TABLE `netgsm_sms_settings` ADD `loginpage` varchar(255) NOT NULL;";
        $pdo->runSqlQuery($query);

        $query = "CREATE TABLE IF NOT EXISTS `netgsm_sms_otp`
       (`id` int(10) NOT NULL AUTO_INCREMENT,`verification_code` varchar(6) CHARACTER SET utf8 NOT NULL,
       `reference_code` varchar(40) CHARACTER SET utf8 NOT NULL,`userid` int(10) NOT NULL,`phonenumber` varchar(20) CHARACTER SET utf8 NOT NULL,
       `start_otp_date` datetime NOT NULL,`end_otp_date` datetime NOT NULL, `durum` tinyint(2) NOT NULL DEFAULT 0,
       PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
        $pdo->runSqlQuery($query);


        $query = "INSERT INTO `netgsm_sms_templates`(`id`, `name`,`name_tr`, `smsfieldname`, `type`, `template`, `variables`, `extra`, `description`, `title`, `active`) 
              VALUES (NULL,'ClientAreaRegister','Yeni Müşteri - OTP','" . $settingsRow['smsfieldname'] . "','client','Sayin {firstname} {lastname}, Doğrulama Kodunuz: {code}','{firstname},{lastname},{code}','',
              '{\"turkish\":\"Müşteri kaydı tamamlandıktan sonra müşteriye telefon doğrulama mesajı gönderir ve kayıt işleminden sonra müşteri paneline telefon numarası doğrulama ekranı çıkartılır. Bu şablon sadece müşteri tarafında tetiklenebilir. Yönetici panelinden müşteri ekleme durumunda çalışmaz. OTP SMS gönderiminde mesajınız 1 boy olmalıdır. Türkçe karakter kullanımı önerilmez. Müşteri girişinde OTP ile doğrulama aktif edilmesi durumunda bu şablon çalışmaz.\",\"english\":\"After Client Registration it sends verification code with OTP SMS.\"}','" . $settingsRow['defaultmsgheader'] . "',0)";
        $pdo->runSqlQuery($query);

        $query = "INSERT INTO `netgsm_sms_templates`(`id`, `name`,`name_tr`, `smsfieldname`, `type`, `template`, `variables`, `extra`, `description`, `title`, `active`) 
                  VALUES (NULL,'ClientLoginOtpAuth','Müşteri girişinde OTP ile doğrulama','" . $settingsRow['smsfieldname'] . "','client','Sayin {firstname} {lastname}, Dogrulama Kodunuz : {code}','{firstname},{lastname},{code}',
                        '','{\"turkish\":\"Müşteri girişinde tek kullanımlık doğrulama kodu ile müşteri doğrulaması sağlanır. Bu şablonun çalışması için Otomatik Giriş Anahtarını ayarlar sekmesinden belirmiş olmalısınız. OTP SMS gönderiminde mesajınız 1 boy olmalıdır. Türkçe karakter kullanımı önerilmez.\",\"english\":\"When clients login send a otp message to the client phone number\"}','" . $settingsRow['defaultmsgheader'] . "',0)";
        $pdo->runSqlQuery($query);

        $query = "INSERT INTO `netgsm_sms_fields` (`id`, `hook_id`, `field`, `field_tr`) VALUES 
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientAreaRegister' LIMIT 1), 'code', 'Doğrulama Kodu'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='ClientLoginOtpAuth' LIMIT 1), 'code', 'Doğrulama Kodu')";
        $pdo->runSqlQuery($query);

    }

    if ($version < 1.9) {

        $query = "ALTER TABLE `netgsm_sms_settings` ADD `smslanguage` varchar(25)";
        $pdo->runSqlQuery($query);
        $query = "ALTER TABLE `netgsm_sms_settings` ADD `msgheaders` varchar(255)";
        $pdo->runSqlQuery($query);
        $service->getTitleAsArray();

        $query = "update `netgsm_sms_settings` set smslanguage = 'tr'";
        $pdo->runSqlQuery($query);

        $query = "INSERT INTO `netgsm_sms_templates`(`id`,`name`, `name_tr`, `smsfieldname`,`type`, `template`, `title`, `variables`, `active`, `description`) VALUES (NULL,'TicketOpen','Ticket Açma','" . $settingsRow['smsfieldname'] . "','client','Sayin {firstname} {lastname}, ({ticketid}) numarali ticketınız gönderilmiştir.','" . $settingsRow['defaultmsgheader'] . "','{firstname}, {lastname}, {ticketid}',0,'{\"turkish\":\"Ticket açıldığında müşteriye mesaj gönderir.\",\"english\":\"When a ticket is opened it sends a message.\"}')";
        $pdo->runSelectQuery($query);

        $query = "INSERT INTO `netgsm_sms_fields` (`id`, `hook_id`, `field`, `field_tr`) VALUES 
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'ticketid', 'Ticket Numarası'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='TicketOpen' LIMIT 1), 'lastname', 'Soyisim')";
        $pdo->runSelectQuery($query);

    }

    if ($version < 1.92) {
        $query = "ALTER TABLE `netgsm_sms_otp` ADD `islogin` tinyint";
        $pdo->runSqlQuery($query);
    }

    if ($version < 1.95) {

        $query = "ALTER TABLE `netgsm_sms_settings` ADD `blockedotpsmsfieldname` varchar(255) NOT NULL;";
        $pdo->runSqlQuery($query);
    }

    if ($whmcsversion == "8" and $version < 1.97) {

        $query = "INSERT INTO `netgsm_sms_templates`(`id`,`name`, `name_tr`, `smsfieldname`,`type`, `template`, `title`, `variables`, `active`, `description`) 
VALUES (NULL,'UserLoginOtpAuth','Müşteri girişinde OTP ile doğrulama','" . $settingsRow['smsfieldname'] . "','client','Sayin {firstname} {lastname}, Giriş Dogrulama kodunuz : {code}.','" . $settingsRow['defaultmsgheader'] . "','{firstname}, {lastname}, {code}',0,'{\"turkish\":\"Müşteri girişinde tek kullanımlık doğrulama kodu ile müşteri doğrulaması sağlanır. Bu şablonun çalışması için .\",\"english\":\"When clients login send a otp message to the client phone number to verify user.\"}'),

(NULL,'UserLogin_admin','Müşteri Girişi','" . $settingsRow['smsfieldname'] . "','admin','({firstname} {lastname}), Siteye giris yapti','" . $settingsRow['defaultmsgheader'] . "','{firstname}, {lastname}',0,'{\"turkish\":\"Müşteri Sisteme Giriş Yaptıktan Sonra Yöneticiye Mesaj Gönderir.\",\"english\":\"When a client login it sends a message to the admin.\"}')";
        $pdo->runSelectQuery($query);

        $query = "INSERT INTO `netgsm_sms_fields` (`id`, `hook_id`, `field`, `field_tr`) VALUES 
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLoginOtpAuth' LIMIT 1), 'code', 'Doğrulama Kodu'),

(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'firstname', 'İsim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'lastname', 'Soyisim'),
(NULL, (SELECT id FROM netgsm_sms_templates WHERE name='UserLogin_admin' LIMIT 1), 'email', 'E-mail')
";
        $pdo->runSelectQuery($query);

        $query = "delete from netgsm_sms_fields where hook_id in (SELECT id FROM netgsm_sms_templates WHERE name in ('ClientLoginOtpAuth','ClientLogin_admin'))";
        $pdo->runSelectQuery($query);

        $query = "delete from netgsm_sms_templates where name in ('ClientLoginOtpAuth','ClientLogin_admin')";
        $pdo->runSelectQuery($query);
    }

    $service->initializeHooks();

}

function consolelog()
{
    $backtrace = debug_backtrace();

    if (is_array($backtrace)) {
        $belirtec = $backtrace[0]["line"];
        $dosyaadi = $backtrace[0]["file"];
        $dosyaadi = addslashes($dosyaadi);
    }
    global $sondatecalc;
    $datetime2 = date_format(new DateTime(), 'd-m-Y H:i:s') . substr((string)microtime(), 1, 8); //end time
    $diff = abs(strtotime($datetime2) - strtotime($sondatecalc));
    $d2 = new DateTime($datetime2);
    $d1 = new DateTime($sondatecalc);
    $micro1 = $d2->format("u");
    $micro2 = $d1->format("u");

    $micro = abs($micro1 - $micro2);
    $diffSeconds = $diff . "." . $micro;
    $sondatecalc = $datetime2;
    ?>
    <script>console.log("<?php echo $belirtec;?>", "<?php echo $diffSeconds;?>", "<? echo $datetime2;?>___<? echo $dosyaadi;?>");</script><?php
}

function netgsm_output($vars)
{

    echo '
<script src="https://code.jquery.com/jquery-3.3.1.min.js"  
integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" 
integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" 
integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<link rel="stylesheet" href="../modules/addons/netgsm/css/font-awesome.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" 
integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
';

    $service = new SmsService();

    $client_area_page_list = $service->getClientAreaPageList();

    $addonPath = "../modules/addons/netgsm/";
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $LANG = $vars['_lang'];
    $module = $_GET['module'];

    putenv("TZ=Europe/Istanbul");
    $scriptString = '<script> var lang = {';
    foreach ($LANG as $l => $value) {
        $scriptString .= '"' . $l . '":"' . $value . '",';
    }
    $scriptString .= '}; </script>';
    $scriptString .= "

    <script>

     const addonPath = '../modules/addons/netgsm/';
     
function tempAlert(msg,duration,cssClass){

     var element = document.createElement(\"div\");
     element.classList.add(\"alert\");
     element.classList.add(cssClass);
     element.setAttribute(\"style\",\"position:fixed;top:0%;left:40%;padding:30px;font-size:30px; z-index: 9999;\");
     element.innerHTML = msg;
     
     setTimeout(function(){ 
         element.parentNode.removeChild(element);
     },duration);
     document.body.appendChild(element);
}

$(document).ready(function(){
    $('[data-toggle=\"tooltip\"]').tooltip();   
});
    </script>";
    $htmlString = "";

    if (!isset($_GET['tab'])) {
        $currentversion = file_get_contents("http://api.netgsm.com.tr/plugins/whmcs/version.txt");
//        $version = preg_replace('/[^0-9]/', '', $version);
//        $currentversion = preg_replace('/[^0-9]/', '', $currentversion);

        $version = (float)$version;
        $currentversion = (float)$currentversion;

        if ($version < $currentversion) {
            $htmlString .= "<div id='updateNotify' class='alert alert-danger' role='alert'>" . $LANG['newversion'] . "";
        } else if ($version > $currentversion) {
            $htmlString .= "<div id='updateNotify' class='alert alert-warning' role='alert'>" . $LANG['errorversion'] . "";
        } else {
            $htmlString .= "<div id='updateNotify' class='alert alert-info' role='alert'>" . $LANG['uptodate'] . "";
        }

        $htmlString .= "<button type=\"button\" class=\"close\" aria-label=\"Close\" onclick='closeUpdateNotify()'>
                      <span aria-hidden=\"true\">&times;</span>
                  </button></div>
                  <script>
                      function closeUpdateNotify(){
                          $('#updateNotify').hide('5000');
                      }
                  </script>";
        $annoncement = file_get_contents("http://api.netgsm.com.tr/plugins/whmcs/annoncement.txt");

        if (trim($annoncement) != '') {
            $htmlString .= "<div id='notification' class='alert alert-info' role='alert' >
            <strong>Netgsm Duyuru<br></strong>" . $annoncement . "</div>";
        }
    }


    $settings = $service->getSettings()->fetch(PDO::FETCH_ASSOC);
    $usercode = $settings['usercode'];
    $password = $settings['password'];
    $smsfieldname = $settings['smsfieldname'];
    $defaultmsgheader = $settings['defaultmsgheader'];
    $autoauthkey = $settings['autoauthkey'];
    $blockedsmsfieldname = $settings['blockedsmsfieldname'];
    $blockedotpsmsfieldname = $settings['blockedotpsmsfieldname'];
    $loginredirectpage = $settings['loginredirectpage'];
    $loginpage = $settings['loginpage'];
    $smslanguage = $settings['smslanguage'];

    if ((empty($usercode) || empty($password)) && !isset($_GET['tab'])) {
        $tab = "settings";
    } else if (isset($_GET['tab'])) {
        $tab = $_GET['tab'];
    } else
        $tab = "sendbulk";

    $htmlString .= '
    <div id="netgsmsms"> 
    
    <style>
    
    .netgsm_tablehead{
        border-radius: 5px ;background-color: #1a4d80;color: #fff;
    }
    
    .contentarea{
        background: #f5f5f5 !important;
    }
    #clienttabs *{
    margin: inherit;
    padding: inherit;
    border: inherit;
    color: inherit;
    background: inherit;
    background-color: inherit;
    }
    #netgsmsms .internalDiv {
        text-align: left !important;;
        background:#fff !important;;
        margin: 0px !important;;
        padding: 5px !important;
        border: 1px solid #ddd !important;
    }
    #clienttabs{position: relative; z-index: 99;}
     #clienttabs ul li {
        display: inline-block;
        margin-right: 3px;
        border: 1px solid #ddd;
        border-bottom:0px;
        padding: 12px;
        margin-bottom: -1px;
     }
     #clienttabs ul a {
     border: 0px;;
     }
     #clienttabs ul {
        float:left;
        margin-bottom:0px;
     }
     #clienttabs{
//        margin-bottom:10px;
        float:left;
     }
     .tabselected{
        background-color:#fff !important;
     }
        .link {
            padding: 10px 15px;
            background: transparent;
            border: #bccfd8 1px solid;
            border-left: 0px;
            cursor: pointer;
            color: #607d8b
        }
                .disabled {
            cursor: not-allowed;
            color: #bccfd8;
        }

        .current {
            background: #bccfd8;
        }

        .first {
            border-left: #bccfd8 1px solid;
        }.answer {
            padding-top: 10px;
        }

        #pagination {
            margin-top: 20px;
            padding-top: 30px;
            border-top: #F0F0F0 1px solid;
        }

        .dot {
            padding: 10px 15px;
            background: transparent;
            border-right: #bccfd8 1px solid;
        }

        #overlay {
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 999;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: none;
        }

        #overlay div {
            position: fixed;
            left: 50%;
            top: 50%;
            margin-top: -32px;
            margin-left: -32px;
        }

        .page-content {
            padding: 20px;
            margin: 0 auto;
        }

        .pagination-setting {
            padding: 10px;
            margin: 5px 0px 10px;
            border: #bccfd8 1px solid;
            color: #607d8b;
        }
        .big-checkbox {
            width: 15px; height: 15px;
        }
        .large-checkbox{
            width: 24px; height: 22px;
        }
        .table-head{
            border-radius: 5px;background-color: #1a4d80;color: #fff;
        } 

.CellWithComment{
  position:relative;
}

.CellComment{
  display:none;
  position:absolute; 
  z-index:100;
  border:1px;
  background-color:white;
  border-style:solid;
  border-width:1px;
  border-color:red;
  padding:3px;
  color:red; 
  top:20px; 
  left:20px;
}

.CellWithComment:hover span.CellComment{
  display:block;
}
.bs-example{
   margin: 50px;
}
.bs-example a{
   font-size: 22px;        
   text-decoration: none;
   margin: 0 10px;
}
    </style>';

    $htmlString .= '
    <div id="clienttabs">
     <ul>
        <li class="' . (($tab == "settings" || $tab == "") ? "tabselected" : "tab") . '">
            <a href="addonmodules.php?module=netgsm&tab=settings">' . $LANG['settings'] . '</a>
        </li>
        <li class="' . (($tab == "sendbulk") ? "tabselected" : "tab") . '">
             <a href="addonmodules.php?module=netgsm&tab=sendbulk">' . $LANG['bulksms'] . '</a>
        </li>
       <li class="' . (($tab == "templates") ? "tabselected" : "tab") . '">
            <a href="addonmodules.php?module=netgsm&tab=templates">' . $LANG['smstemplates'] . '</a>
       </li>  
        <li class="' . (($tab == "otplist") ? "tabselected" : "otplist") . '">
            <a href="addonmodules.php?module=netgsm&tab=otplist">' . $LANG['otptab'] . '</a>
       </li>           
     </ul>
    </div>
   <div style="clear:both;"></div>';

    if (!isset($tab) || $tab == "settings") {
        $titles = $service->getTitleAsArray();
        $htmlString .= '    
     <div class="internalDiv" >
         <h4 style="padding-left: 15px;"><span class="label label-info">' . $LANG['settingsinfo'] . '</span></h4>
		    <div class="row">
		        <div class="col-md-4 col-sm-6 col-xs-12 ">
		            <div class="form-group">
		                <h4><span >' . $LANG['username'] . ' :</span></h4>
                        <input type="text" id="username" name="username" class="form-control" placeholder="850XXXXXXX" value="' . $usercode . '">
                        <div style="display: none; color: #ff0000;" id="usernameEmpty">
                             ' . $LANG['usernameempty'] . '
                        </div>
		            </div>
		        </div>
		        <div class="col-md-4 col-sm-6 col-xs-12 ">
		            <div class="form-group">
		                <h4><span >' . $LANG['password'] . ' :</span></h4>
                        <input type="password" id="password" name="password" class="form-control" placeholder="************" value="">

                        <div style="display: none; color: #ff0000;" id="passwordEmpty">
                          ' . $LANG['passwordempty'] . '
                        </div>
                    </div>
		        </div>
			</div>
			<div class="row">
			    <div class="col-md-4 col-sm-6 col-xs-12 ">
		            <div class="form-group">
		                <h4><span>Müşteri Telefon Alan Adı :</span></h4>
                        <input type="text" id="smsfield" name="smsfield" class="form-control" placeholder="smstelefonu" value="' . $smsfieldname . '">

                        <div style="display: none; color: #ff0000;" id="smsfieldEmpty">
                           Özel Müşteri Telefon Alan Adı Boş Bırakılamaz
                        </div>
			        </div>
			    </div>
			    <div class="col-md-4 col-sm-6 col-xs-12 ">
		           <div class="form-group">
		               <h4><span>Ön Tanımlı Sms Başlığı : </span></h4>
		               <div class="row">
                           <div class="col-md-8 col-sm-8 col-xs-8">
                               <select name="msgtitles" class="form-control" id="msgtitles">';
        $htmlString .= '<option value="">' . $LANG['select'] . '</option>';
        if (!empty($usercode) and !empty($password)) {
            if ($titles == 30) {
                $scriptString .= '<script>$(document).ready(function(){
                                    $("#msgtitles").css("border-color", "#C80000");  
                                    document.getElementById("defaultmsgheaderEmpty").style.display = "block";
                                 });
                                    
                            </script>';

            } else {
                for ($i = 0; $i < count($titles) - 1; $i++) {
                    $selected = "";
                    if ($defaultmsgheader == $titles[$i]) {
                        $selected = " selected ";
                    }
                    $htmlString .= '<option value="' . $titles[$i] . '" ' . $selected . '>' . $titles[$i] . '</option>';
                }
            }
        }
        $htmlString .= '</select>';
        $htmlString .= '<div style="display: none; color: #ff0000;" id="defaultmsgheaderEmpty">
                                  ' . $LANG['titleempty'] . '
                                </div>';
        $htmlString .= '</div>
                           <div class="col-md-4 col-xs-4">
                               <button type="button" name="baslikgetir" id="baslikgetir" class="btn btn-primary btn-sm">' . $LANG['gettitles'] . '</button>
                           </div>
                       </div>
		           </div>
		        </div>
			</div>
            <div class="row">
                <div class="col-md-4 col-sm-6 col-xs-12">
                	<div class="form-group">
		                <h4><span>' . $LANG['blockersmsfield'] . ' </span><span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="Bu alana bir müşteriye SMS gönderilmesini engellemek için oluşturduğunuz özel müşteri alanının adını yazınız. Boş bırakılırsa sms engelleme çalışmayacaktır." style="color:#5bc0de;;"></span></h4>
                        <input type="text" class="form-control" name="blockedsmsfieldname" id="blockedsmsfieldname" placeholder="smsalmakistemiyorum" value="' . $blockedsmsfieldname . '"> 
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-xs-12">
                	<div class="form-group">
		                <h4><span>' . $LANG['blockedotpsmsfield'] . ' </span><span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="Bu alana bir müşteriye Otp SMS gönderilmesini engellemek için oluşturduğunuz özel müşteri alanının adını yazınız." style="color:#5bc0de;;"></span></h4>
                        <input type="text" class="form-control" name="blockedotpsmsfieldname" id="blockedotpsmsfieldname" placeholder="otpsmsalmakistemiyorum" value="' . $blockedotpsmsfieldname . '"> 
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-6 col-xs-12">
                    <h4><span>' . $LANG['loginredirectpage'] . ' </span><span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="Müşteri Girişinden sonra yönlendirilmesini istediğiniz sayfayı yazınız. Ön tanımlı olarak müşteri paneli anasayfasına yönlendirilir. " style="color:#5bc0de;"></span></h4>
                 
                    <select  class="form-control" name="loginredirectpage" id="loginredirectpage">';

        foreach ($client_area_page_list as $page) {

            if ($page['scope_name'] == $loginredirectpage) {
                $selected = "selected";
            } else {
                $selected = '';
            }

            $htmlString .= '<option value="' . $page['scope_name'] . '" ' . $selected . '>' . $page['destination'] . '</option>';
        }

        $htmlString .= '</select>
                </div>
                <div class="col-md-4 col-sm-6 col-xs-12">
                <br>
                    <h4>
                        <label for="smslanguage" style="font-weight:500">Türkçe Karakterler Engellensin
                        <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="Sms şablonlarınızda yazılan türkçe karakterlerin otomatik olarak ingilizce karaktere çevrilmesini istiyorsanız bu kutucuğu işaretleyin." style="color:#5bc0de;"></span>
                        </label>
                        <input type="checkbox" class="large-checkbox" name="smslanguage" id="smslanguage" ' . ($smslanguage == "tr" ? '' : 'checked') . '>
                    </h4>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-4 col-sm-6 col-xs-12">
                    <h4><span>Tarih formatı</span></h4>
                    
                    <select class="form-control" name="date_format" id="date_format">
                        <option value="">Seç</option>
                        <option value="1" ' . ($settings['dateformat'] == 1 ? "selected" : '') . '>Gün.Ay.Yıl</option>
                        <option value="2"  ' . ($settings['dateformat'] == 2 ? "selected" : '') . '>Yıl-Ay-Gün</option>
                    </select>
                </div>
                
            </div>
			<div class="row">
                <div class="col-md-8" align="right">
                    <input type="button" class="btn btn-success btn-sm" value="' . $LANG['update'] . '" id="updateInfos">
			    </div>
			</div>
		
			';
        $htmlString .= '</div></div>'; //// end of container
        $scriptString .= '<script>

document.getElementById("updateInfos").addEventListener("click", function (){
    
    var username = document.getElementById("username").value.trim();
    var password = document.getElementById("password").value.trim();
    var smsfield = document.getElementById("smsfield").value.trim();
    var defaultmsgheader = document.getElementById("msgtitles").value.trim();
    var blockedsmsfieldname = document.getElementById("blockedsmsfieldname").value.trim();
    var blockedotpsmsfieldname = document.getElementById("blockedotpsmsfieldname").value.trim();
    var loginredirectpage = document.getElementById("loginredirectpage").value.trim();
    var date_format = document.getElementById("date_format").value.trim();
    
    var smslanguage = $("#smslanguage").is(":checked");
    
    if(smslanguage == true){
        smslanguage = "en";
    }else{
        smslanguage = "tr";
    }

    if(username=="" || password=="" || defaultmsgheader == ""){
        if(username=="")
        {
            document.getElementById("username").style.borderColor="#ff0000";
            $("#usernameEmpty").show("slow");
        }
        if(password=="")
        {
            document.getElementById("password").style.borderColor="#ff0000";
            $("#passwordEmpty").show("slow");
        }if(defaultmsgheader == "")
        {
            $("#defaultmsgheaderEmpty").show("slow");
            $("#msgtitles").css("border-color", "#ff0000");
        }
        
    }
    else{
    
        $.ajax({
            type: "POST",
            url:  addonPath+"ajax.php",
            data: {aboneid:username,abonepass:password,smsfield:smsfield,defaultmsgheader:defaultmsgheader,blockedsmsfieldname:blockedsmsfieldname,blockedotpsmsfieldname:blockedotpsmsfieldname,loginredirectpage:loginredirectpage,smslanguage,date_format:date_format},
            error: function(data){
                alert("' . $LANG['errortoadmin'] . '!!!");
            },
            success: function(data) {

                var resp = JSON.parse(data);
                if(resp["status"])
                    tempAlert(resp[\'description\'],3000,"alert-success");
                else{
                    tempAlert(resp[\'description\'],3000,"alert-warning");
                }
                           
                $("#usernameEmpty").hide("slow");
                document.getElementById("username").style.borderColor="#ccc";
                $("#passwordEmpty").hide("slow");
                document.getElementById("password").style.borderColor="#ccc";
    
                $("#defaultmsgheaderEmpty").hide("slow");
                
                $("#msgtitles").css("border-color", "#ccc");
            
            }
        });    
    }
    
   });
    
    document.getElementById("baslikgetir").addEventListener("click", function (){
        
        var username = document.getElementById("username").value.trim();
        var password = document.getElementById("password").value.trim();

     
        $.ajax({
            type: "POST",
            url:  addonPath+"ajax.php",
            data: {gettitles:1,usercode:username,password:password},
            error: function(data){
                alert("' . $LANG['errortoadmin'] . '!!!");
            },
            success: function(data) {
                
//                console.log(data);
                
                if(data == 30){
                    tempAlert("' . $LANG['messagecode30'] . '!!",3000,"alert-warning");
                    document.getElementById("msgtitles").innerHTML="<option value=\'\'>' . $LANG['select'] . '</option>";
                    $("#defaultmsgheaderEmpty").show("slow");
                    $("#msgtitles").css("border-color", "#ff0000");
                    return 0;
                }
                data = JSON.parse(data);
                options="";
                for(let i=0;i<data.length;i++){
                    if(data[i].trim()!="")
                    options+="<option value=\'"+data[i]+"\'>"+data[i]+"</option>";
                }
                document.getElementById("msgtitles").innerHTML=options;
                    $("#defaultmsgheaderEmpty").hide("slow");
                    $("#msgtitles").css("border-color", "#ccc");
            }
            
        });
        
        
    });

    </script>';

        $htmlString .= $scriptString;
        echo $htmlString;
    } /**
     * BULK SMS
     */
    else if ($tab == "sendbulk") {

        $htmlString .= '
        <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.8.1/themes/prism.min.css" rel="stylesheet">
        ';
        $scriptString .= '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.8.1/prism.min.js"></script>';

        try {
            $htmlString .= '
        <div class="internalDiv">
            <div class="row ">
				<div class="col-md-4 col-sm-5 col-xs-5">
				    
					<h4><span >' . $LANG['title'] . '</span></h4>
					<div class="row" id="titleEmptyAlert" style="display: none;">
						<div style="float:left;font-size: 15px;color: rgb(255,0,0); padding-left:15px;">' . $LANG['titleempty'] . '!!</div>
					</div>
					<select id="titles" name="title" class="form-control"> ';
            $titles = $service->getTitleAsArray();

            if ($titles == 30) {
                $scriptString .= '<script>document.getElementById("titles").style.borderColor = "#ff0000";
                                document.getElementById("titleEmptyAlert").style.display = "block";
                        </script>';
                $htmlString .= '<option>' . $LANG['select'] . '</option>';
            } else {
                for ($i = 0; $i < count($titles) - 1; $i++) {
                    if ($titles[$i] == $defaultmsgheader) {
                        $selected = ' selected ';
                    } else {
                        $selected = '';
                    }
                    $htmlString .= '<option value="' . $titles[$i] . '" ' . $selected . '>' . $titles[$i] . '</option>';
                }
            }
            $htmlString .= '</select>
				</div>
				<div class="col-md-4 col-sm-5 col-xs-5">
					<h4><span >' . $LANG['customize'] . '</span></h4>
					
					<select id="fields" class="form-control" onchange="addField()">
                        <option value="" selected>' . $LANG['select'] . '</option>';
            $clientFields = array(0 => "firstname", 1 => "lastname", 2 => "companyname", 3 => "email", 4 => "address1", 5 => "address2",
                6 => "city", 7 => "state", 8 => "postcode", 9 => "country", 10 => "phonenumber");
            $clientFieldsTR = array(0 => $LANG['firstname'], 1 => $LANG['lastname'], 2 => $LANG['companyname'], 3 => $LANG['email'], 4 => $LANG['address1'], 5 => $LANG['address2'],
                6 => $LANG['city'], 7 => $LANG['state'], 8 => $LANG['postcode'], 9 => $LANG['country'], 10 => $LANG['phonenumber']);
            for ($counter = 0; $counter < count($clientFields); $counter++) {
                $htmlString .= '<option value="' . $clientFields[$counter] . '">' . $clientFieldsTR[$counter] . '</option>';
            }
            $htmlString .= '</select>
				</div>
                <div class="col-md-4 col-sm-4 col-xs-4"></div>
			</div>
			<div class="row">
				<div class="col-md-8 col-sm-12 col-xs-12">
					<h4><span>' . $LANG['message'] . '</span> </h4>
					<div class="row" id="messageEmptyAlert" style="display: none;">
						<div style="float:left;font-size: 15px;color: rgb(255,0,0); padding-left:15px;">' . $LANG['messageempty'] . '!!</div>
					</div>
					<textarea id="messageArea" class="form-control style-1" rows="5" cols="75" style="resize: none;" oninput="messageAreaChanging()"></textarea>
				</div>
				<div class="col-md-4 col-sm-12 col-xs-12" style="padding-top: 50px;">
					<button class="btn btn-success" onclick="sendSMS()">' . $LANG['send'] . '</button>
				</div>
			</div>
			';

            $htmlString .= '<br>
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="inputName">' . $LANG['inputname'] . '</label>
                                <input type="text" name="name" id="inputName" class="form-control input-sm" value="" placeholder="KXXXXXXX">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="inputEmail">' . $LANG['inputemail'] . '</label>
                                    <input type="text" name="email" id="inputEmail" class="form-control input-sm" value="" placeholder="abc@xyz">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">       
                            <div class="form-group">
                                <label for="inputPhone">' . $LANG['inputphone'] . '</label>
                                <input type="tel" name="phone" id="inputPhone" class="form-control input-sm" value="" placeholder="053XXXXXXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="inputGroup">' . $LANG['inputgroup'] . '</label>
                                <select type="text" name="group" id="inputGroup" class="form-control input-sm">
                                    <option value="">' . $LANG['select'] . '</option>';
            $groupNames = $service->getClientAllGroupNames();
            while ($groupname = $groupNames->fetch(PDO::FETCH_ASSOC))
                $htmlString .= '<option value="' . $groupname['groupname'] . '">' . $groupname['groupname'] . '</option>';
            $htmlString .= '</select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="form-group">
                                <label for="inputStatus">' . $LANG['status'] . '</label>
                                <select type="text" name="status" id="inputStatus" class="form-control input-sm">
                                    <option value="">' . $LANG['select'] . '</option>
                                    <option value="Active">' . $LANG['active'] . '</option>
                                    <option value="Inactive">' . $LANG['inactive'] . '</option>
                                    <option value="Closed">' . $LANG['closed'] . '</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4">
                            <div class="row"> 
                                <div class="col-md-4 col-xs-6 col-sm-6" style="padding-bottom: 10px; padding-left: 20px; padding-right: 10px;">
                                    <label for="inputStatus">' . $LANG['inputlimit'] . '</label>
                                    <select id="inputLimit" class="form-control input-sm" >
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-xs-6 col-sm-6">
                                    <label for="inputStatus">' . $LANG['inputPhoneType'] . '</label>
                                    <select id="inputPhoneType" class="form-control input-sm" 
                                    data-toggle="tooltip" data-original-title="' . $LANG['inputPhoneDescription'] . '">
                                        <option value="" selected>WHMCS</option>
                                        ';
            if (!empty($settings['smsfieldname'])) {
                $htmlString .= '<option value="' . $settings['smsfieldname'] . '">' . ucfirst($settings['smsfieldname']) . '</option>';
            }
            $htmlString .= '
                                    </select>
                                </div>
                                <div class="col-md-4 col-xs-6 col-sm-6">
                                    <label class="clear-search hidden-xs"></label>
                                    <div style="padding-bottom: 10px; padding-left: 10px; padding-right: 10px;">
                                        <button id="btnSearchClients" class="btn btn-primary btn-search" >
                                            <span>' . $LANG['search'] . '</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>    
                </div>
            </div>
            <div class="row">
                <div id="overlay">
                    <div><img src="' . $addonPath . 'loading.gif" width="64px" height="64px"/></div>
                </div>
                <div class="col-md-12">
                    <input type="hidden" id="holdPageValue">
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-condensed" id="clientTable" style=" border-collapse:separate; border-spacing:1px;">
                        <thead>
                            <tr>
                                <th style="border-radius: 5px ;background-color: #1a4d80;color: #fff" width="45">
                                    <input type="checkbox" id="checkall0" class="big-checkbox">
                                        <a href="#0"><span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-original-title="' . $LANG['selectall'] . '" style="color: #fff;"></span></a>
                                </th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff; width: 30px;">' . $LANG['id'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['firstname'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['lastname'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['phonenumber'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['companyname'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['email'] . '</th>
                                <th style="border-radius: 5px;background-color: #1a4d80;color: #fff">' . $LANG['status'] . '</th>
                            </tr>
                        </thead>
                        <tbody id="tablebody">
          
                        </tbody>
                    </table>
                    
                    </div>
                  
                    <div id="paginationinfos" style="padding-bottom: 10px;">
                    </div>
                    <br>
                    ' . $LANG["totalclient"] . ' <span class="label label-info" style=\'font-size: 15px;\' id="totalcount"></span>
                    ' . $LANG["selectedclient"] . ' <span class="label label-info" style=\'font-size: 15px;\' id="selectedcount"></span>
                </div>
            </div>';
            $htmlString .= '
        </div>
    </div>';

            $scriptString .= '<script>
            var arrayOfPhone = [];
            var arrayOfAllPhone = [];
            $(document).ready(function() {
                document.getElementById("selectedcount").innerText = arrayOfPhone.length;
                getClients(1);
            });
             

         function getClients(page){
                             
             var inputName = document.getElementById("inputName").value.trim();
             var inputEmail = document.getElementById("inputEmail").value.trim();
             var inputPhone = document.getElementById("inputPhone").value.trim();
             var inputGroup = document.getElementById("inputGroup").value.trim();
             var inputStatus = document.getElementById("inputStatus").value.trim();
             var inputLimit = document.getElementById("inputLimit").value.trim();
             var inputPhoneType = document.getElementById("inputPhoneType").value.trim();
             var inputs = {inputName:inputName,inputEmail:inputEmail,inputPhone:inputPhone,inputGroup:inputGroup,
             inputStatus:inputStatus,inputLimit:inputLimit,page:page,arrayOfPhone:arrayOfPhone,inputPhoneType:inputPhoneType};
             
             $.ajax({
                type: "POST",
                url:  addonPath+"ajax.php",
                data: {inputs:inputs,lang:lang},
                beforeSend: function () {
                    $("#overlay").show();
                    setInterval(function () {
                        $("#overlay").hide();
                    }, 5000);
                },
                error: function(data){
                    alert("' . $LANG['errortoadmin'] . '!!!");
                },
                success: function(data) {
                    var tableinfos = JSON.parse(data);
                    document.getElementById("tablebody").innerHTML = tableinfos["tbody"];
                    arrayOfAllPhone = tableinfos["allClientsArray"];
                    document.getElementById("paginationinfos").innerHTML = tableinfos["pagination"];
                    document.getElementById("totalcount").innerText = tableinfos["rowcount"];
                    setInterval(function () {
                        $("#overlay").hide();
                    }, 500);
                    
                  }
                });

         }
         
         document.getElementById("btnSearchClients").addEventListener("click", function (){
                getClients(1);
         });
            
        function changePhoneNumber(element){
                
                id = $(element).closest(\'tr\').find(\'td:nth-child(2)\').text();
                pnumber = $(element).closest(\'tr\').find(\'td:nth-child(5)\').text();
               
                if(element.checked == true){
                    if(!arrayOfPhone.includes(id+"_"+pnumber) && id !== "")
                       arrayOfPhone.push(id+"_"+pnumber);
                }
                else{
                    index = arrayOfPhone.indexOf(id+"_"+pnumber);
                    if (index > -1) {
                        arrayOfPhone.splice(index, 1);
                    }
                }
                document.getElementById("selectedcount").innerText = arrayOfPhone.length;

        }
         
         $("#checkall0").click(function () {
                flag = 0;
                $("input:checkbox").not(this).prop(\'checked\', this.checked);
               
                if(this.checked == true)
                    arrayOfPhone = arrayOfAllPhone;
                else
                    arrayOfPhone = [];
                document.getElementById("selectedcount").innerText = arrayOfPhone.length;

         });
           
         function messageAreaChanging() {
              document.getElementById("messageEmptyAlert").style.display = "none";
              document.getElementById("messageArea").style.borderColor = "";
         }    
         
         function sendSMS(){
            if(arrayOfPhone.length<1){
                tempAlert("' . $LANG['chooseclient'] . '!!",3000,"alert-warning");
                return 0;
            }
            var toSend = 1;
            var title = document.getElementById("titles").value;
            var message = document.getElementById("messageArea").value.trim();
            if(title === ""){
                tempAlert("' . $LANG['notitle'] . '!!",5000,"alert-warning");
                toSend = 0;
            }
            if(message === ""){
                document.getElementById("messageEmptyAlert").style.display = "block";
                document.getElementById("messageArea").style.borderColor = "#ff0000";
                toSend = 0;
            }

            if(toSend == 1){
               $.ajax({
                    type: "POST",
                    url:  addonPath+"ajax.php",
                    data: {numbers:arrayOfPhone,title:title,message:message},
               error: function(data){
                    alert("' . $LANG['errortoadmin'] . '!!!");
               },
               success: function(data) {

//                    console.log(data);

                    var response = data;
                    var resp = response.split(" ");
                    
                    if(resp[0] === "00"){
                        tempAlert("' . $LANG['messagecode00'] . '",3000,"alert-success");
                    }
                    else if(parseInt(resp[0]) == 20){
                        tempAlert("' . $LANG['messagecode20'] . '",6000,"alert-warning");
                    }
                    else if(parseInt(resp[0]) == 30){
                        tempAlert("' . $LANG['messagecode30'] . '!!!",6000,"alert-warning");
                    }
                    else if(parseInt(resp[0]) == 40){
                        tempAlert("' . $LANG['messagecode40'] . '!!!",6000,"alert-warning");
                    }
                    else if(parseInt(resp[0]) == 70){
                        tempAlert("' . $LANG['messagecode70'] . '!!!",6000,"alert-warning");
                    }
                    else{
                        tempAlert("' . $LANG['messageempty'] . '!!!",5000,"alert-warning");;
                    }
               }
               }); 
            }
         }
            
         function addField(){

             document.getElementById(\'messageArea\').value+=" {"+document.getElementById(\'fields\').value+"} ";
         }
            </script>';

            $htmlString .= $scriptString;
            echo $htmlString;
        } catch (Exception $e) {
            echo 'Exception ::' . $e->getMessage();
        }
    } /**
     *  UPDATE CLIENT TEMPLATE
     */
    else if ($tab == "templates") {
        $titles = $service->getTitleAsArray();
        $htmlString .= '
    <div class="internalDiv" >
         <div id="overlay">
             <div><img src="' . $addonPath . 'loading.gif" width="64px" height="64px"/></div>
         </div>
         <div class="row">
             <div class="col-md-4 col-sm-4 col-xs-4" class="form-group">
     			 <h4><span>' . $LANG["templatetype"] . '</span></h4>
			     <select id="templateType" class="form-control" onchange="getTemplates()">
			         <option value="client" selected>' . $LANG["client"] . '</option>
                     <option value="admin">' . $LANG["admin"] . '</option>
                 </select>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6"><br>
            
                <button type="button" class="btn btn-sm btn-default" style="margin-top:20px;" onclick="initializeTemplates()" >Şablonları Yenile <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="Kurulum veya güncelleme sırasında eksik kalan sms şablonlarını yeniler. Not: Whmcs sürümüne göre gereksiz şablonlar silinir." style="color:#5bc0de;"></span></button>
            </div>
        </div>
        <div class="row">
			<div class="col-md-12 col-sm-12 col-xs-12" class="form-group">';
        $htmlString .= '<br>
                    <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed sturdy" id="templateTable" style="border-collapse:separate; border-spacing:1px;">
                </table>
                </div>';
        $htmlString .= '
            </div>';
        $htmlString .= '
        </div>
     </div>
  </div>
<!-- Modal -->
     <div class="modal fade" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="templateModalHeader"></h4>
                    <input type="hidden" id="templateName">
                </div>
                <div class="modal-body">';
        $htmlString .= '               
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-6">
                            <h4><span >' . $LANG["title"] . '</span></h4>
                            <div class="row" id="titleEmptyAlert" style="display: none;">
						        <div style="float:left;font-size: 15px;color: rgb(255,0,0); padding-left:15px;">' . $LANG['titleempty'] . '!!</div>
					        </div>
        					<select id="titles" name="title" class="form-control"> ';
        if ($titles == 30) {
            $scriptString .= '<script>document.getElementById("titles").style.borderColor = "#ff0000";
                                document.getElementById("titleEmptyAlert").style.display = "block";
                        </script>';
            $htmlString .= '<option value="">' . $LANG['select'] . '</option>';
        } else {
            for ($i = 0; $i < count($titles) - 1; $i++) {
                $htmlString .= '<option value="' . $titles[$i] . '">' . $titles[$i] . '</option>';
            }
        }
        $htmlString .= '</select>';

        $htmlString .= '</div>
                        <div class="col-md-6 col-sm-6 col-xs-6">';
        $htmlString .= '<h4><span >' . $LANG["customize"] . '</span></h4>
                            <select id="fields" class="form-control" onchange="changeField()">
                                <option value="">' . $LANG["select"] . '</option>';
        $htmlString .= '</select>';
        $htmlString .= '</div>
                     </div>';
        $htmlString .= '<div class="row">
                         <div class="col-md-6 col-sm-6 col-xs-6" style="padding-top: 10px;" id="inputphonediv">
                         <h4><span>' . $LANG['inputPhoneType'] . '</span></h4>
                         <select id="inputPhoneType" class="form-control" 
                                    data-toggle="tooltip" data-original-title="' . $LANG['inputPhoneDescription'] . '">
                                        <option value="" selected>WHMCS</option>
                                        ';
        if (!empty($settings['smsfieldname'])) {
            $htmlString .= '<option value="' . $settings['smsfieldname'] . '">' . ucfirst($settings['smsfieldname']) . '</option>';
        }
        $htmlString .= '</select>
                         </div>
                         <input type="hidden" id="extraField" class="" value="">

                         <div class="col-md-6 col-sm-6 col-xs-6" style="padding-top: 10px; ">
                             <div style="display: none;" id="divforextra" >
                                 <h4><span id="extraFieldText"></span></h4>
                                 <input type="text" id="extraFieldInput" class="form-control" value="">
                                 <input type="checkbox" class="large-checkbox" id="extraFieldCheckbox" style="display: none" value="1">
                             </div>
                             <div style="display: none;" id="admingsm" >
                                <h4><span >' . $LANG['admingsm'] . '</span></h4>
                                <div class="row" id="admingsmFromTemplateEmptyAlert" style="display: none;">
			                        <div style="float:left;font-size: 15px;color: rgb(255,0,0); padding-left:15px;">' . $LANG['admingsmempty'] . '</div>
		                        </div>
                                <input class="form-control" id="admingsmFromTemplate" type="text" data-toggle="tooltip" data-placement="top" title="' . $LANG['admingsmhint'] . '" placeholder="053XXXXXXX, 053XXXXXXX">
                             </div>
                         </div>
                     </div>';
        $htmlString .= '<div class="row">
                         <div class="col-md-12 col-sm-12 col-xs-12">';
        $htmlString .= '<h4><span >' . $LANG['smstemplate'] . '</span></h4>
                             <div class="row" id="messageSablonuEmptyAlert" style="display: none;float:left;font-size: 15px;color: rgb(255,0,0); padding-left:15px;">
			                    ' . $LANG['messageSablonuEmpty'] . '
		                     </div>
                             <textarea id="templateMessageArea" class="form-control " rows="4" cols="75" style="resize: none;"></textarea>';
        $htmlString .= '</div>
                     </div>';
        $htmlString .= '<br>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="alert alert-info" role="alert">
                                <p style="display: block; " id="descriptionText"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <button type="button" class="btn btn-default" data-dismiss="modal">' . $LANG['cancel'] . '</button>
                            <button class="btn btn-success" onclick="saveTemplate()">' . $LANG['update'] . '</button>
                        </div>
                    </div>
                 </div>
            </div>
        </div>    
    </div>
';

////////////////////////////////////////////////////////////////////
        $scriptString .= '<script>
var templatesStatus = false;
var hookId = 0;

$(document).ready(function() {
    getTemplates();
});

function callModal(element) {
    document.getElementById("admingsmFromTemplateEmptyAlert").style.display = "none";
    document.getElementById("messageSablonuEmptyAlert").style.display = "none";

    var type = document.getElementById("templateType").value;
    var table = document.getElementById("templateTable");
    var templateId = $(element).closest(\'tr\').find(\'td:first\').text();
    
    document.getElementById("divforextra").style.display = "block";
    
    var extra = $(element).closest(\'tr\').find(\'td:nth-child(7)\').text();

    if(parseInt(templateId) == 21){
        document.getElementById("extraFieldInput").style.display = "block";
        document.getElementById("extraFieldInput").value = extra;
        document.getElementById("extraFieldCheckbox").style.display = "none";
        
        document.getElementById("extraFieldText").innerHTML = "' . $LANG['remainingdays'] . '";
        
    }else{
        document.getElementById("divforextra").style.display = "none";
        document.getElementById("extraFieldInput").value = "";
    }
    
    hookId = parseInt(templateId);
    var name_tr = $(element).closest(\'tr\').find(\'td:nth-child(2)\').text();
    var template = $(element).closest(\'tr\').find(\'td:nth-child(3)\').text();
    var smsfieldname = $(element).closest(\'tr\').find(\'td:nth-child(6)\').text();
    $("#inputPhoneType").val(smsfieldname);
    var description = $(element).closest(\'tr\').find(\'td:last\').text();
    var title = $(element).closest(\'tr\').find(\'td:nth-child(4)\').text();
    var options = document.getElementById("titles").options;
    var arr = [];
    for (let i=0; i<options.length; i++) {
        if(options[i].value == title){
            document.getElementById("titles").selectedIndex = i;
            break;
        }
    }
    document.getElementById("templateName").value = name_tr;
    document.getElementById("templateModalHeader").innerText = templateId + " . " + name_tr;
    document.getElementById("templateMessageArea").value = template;
    document.getElementById("descriptionText").innerText = description;
    
    if(type === "admin"){
        document.getElementById("inputphonediv").style.display = "none";
        var admingsm = $(element).closest(\'tr\').find(\'td:nth-child(5)\').text().trim();
        $("#admingsmFromTemplate").val(admingsm);
    }
    else{
        document.getElementById("inputphonediv").style.display = "block";
    }
    
    var fileName = $(element).data("file");
    $("#templateModal").data("fileName", fileName).modal("toggle", $(element));
    getFields(templateId);
}

function getFields(templateId){
  
    $.ajax({ 
        type: "POST",
        url:  addonPath+"ajax.php",
        data: {templateId:templateId,lang:lang},
        error: function(data){
            alert("' . $LANG['errortoadmin'] . '!!!");
        },
        success: function(data) {
            document.getElementById("fields").innerHTML = JSON.parse(data);
        }
    }); 
    
}

function changeTemplateStatus(element){
         var name_tr = $(element).closest(\'tr\').find(\'td:nth-child(2)\').text();
         var table = document.getElementById("templateTable");
         var status = element.checked ? 1 : 0;
         var templateIdforStatus = $(element).closest(\'tr\').find(\'td:first\').text();
         $.ajax({
         
            type:"POST",
            url:addonPath+"ajax.php",
            data:{templateIdforStatus:templateIdforStatus,status:status},
            error: function(data){
                alert("' . $LANG['errortoadmin'] . '!!!");
            },
            success: function(data) {
                if(parseInt(data) === 1 && status == 1){
                    tempAlert("<strong>"+name_tr+" : ' . $LANG['templateactivemessage'] . '!!</strong>",3000,"alert-success");
                    }
                else if(parseInt(data) === 1 && status == 0){
                    tempAlert("<strong>"+name_tr+" : ' . $LANG['templateinactivemessage'] . '!!</strong>",3000,"alert-warning");
                    }
                else{
                    tempAlert("' . $LANG['changefailed'] . '!!",3000,"alert-danger");
                    }
                element.checked = status == 1 ? true : false;
            }
         });
}

function initializeTemplates(){

    $.ajax({
        type:"POST",
        url:addonPath+"ajax.php",
        data:{initialize_templates:"1"},
        error: function(data){
            alert("Şablonlar yenilenemedi");
        },
        success: function(data) {
            console.log(data);
            getTemplates();
        }
    });
}

function changeField(){

    var f = document.getElementById("fields").value;
    if(f.trim() == "")
        return 0;
    f = " {"+f+"} ";
   document.getElementById("templateMessageArea").value += f;
}

function getTemplates(){
    var type = document.getElementById("templateType").value;

    $.ajax({
        type: "POST",
        url:  addonPath+"ajax.php",
        data: {type:type,lang:lang},
        beforeSend: function () {
            $("#overlay").show();
        },
        error: function(data){
            alert("' . $LANG['errortoadmin'] . '!!!");
            setInterval(function () {
                $("#overlay").hide();
            }, 500);
        },
        success: function(data) {
            if(type == "admin"){
                document.getElementById("admingsm").style.display = "block";
            }
            else{
                document.getElementById("admingsm").style.display = "none";
            }
            document.getElementById("templateTable").innerHTML = JSON.parse(data);
            setInterval(function () {
                $("#overlay").hide();
            }, 500);
        }
    }); 
    
}

function saveTemplate(){
    
    var extra = "";
    
    extra = document.getElementById("extraFieldInput").value.trim();
    if($.isNumeric(extra) === false){
        extra = "";
    }

    if(hookId == 21){
        if(extra === "")
            extra = "15";
    }else{
        extra = "";
    }
   
    
    var smsfieldname = document.getElementById("inputPhoneType").value.trim();
    var title = document.getElementById("titles").value.trim();
    if(title === ""){
        tempAlert("Title is Empty",5000,"alert-warning");
        return 0;
    }
    var message = document.getElementById("templateMessageArea").value.trim();
    var admingsm = document.getElementById("admingsmFromTemplate").value.trim();
    var type = document.getElementById("templateType").value;
    var templatename = document.getElementById("templateName").value;
    flag = 1;
    if(type === "admin" && admingsm === ""){
        document.getElementById("admingsmFromTemplateEmptyAlert").style.display = "block";
        flag = 0;
    }
        if(message.trim() == ""){
            document.getElementById("messageSablonuEmptyAlert").style.display = "block";
            document.getElementById("messageSablonuEmptyAlert").innerText = "' . $LANG['messageempty'] . '";
            
            flag = 0;
        }
        if(message.trim().length > 240){
            document.getElementById("messageSablonuEmptyAlert").style.display = "block";
            document.getElementById("messageSablonuEmptyAlert").innerText = "' . $LANG['messagecharacterlimit'] . '";
            flag = 0;
        }
            
        if(flag == 1){
            $.ajax({
            type: "POST",
            url:  addonPath+"ajax.php",
            data: {title:title,message:message,hookId:hookId,admingsm:admingsm,extra:extra,smsfieldname:smsfieldname},
            error: function(data){
                alert("' . $LANG['errortoadmin'] . '!!!");
            },
            success: function(data) {
                if(parseInt(JSON.parse(data)) === 1){
                    tempAlert("<strong>"+templatename+" : ' . $LANG['successfulupdate'] . '!!</strong>",3000,"alert-success");
                    $("#templateModal").modal("hide");
                    getTemplates();
                }
                else{
                    tempAlert("<strong>' . $LANG['failedupdate'] . '!!</strong>",3000,"alert-warning");
                }
            }
            }); 
        }
    
}

         </script>';
        $htmlString .= $scriptString;
        echo $htmlString;

    } else if ($tab == "otplist") {
        $htmlString .= '
            <div class="internalDiv">
                <div class="row">
                    <div class="col-md-12">
                         <h4 style="padding-left: 15px;"><span class="label label-info">Bu liste kullanıcılara en son gönderilen doğrulama kodlarını gösterir. Bir kullanıcıya gönderilen eski doğrulama kodlarını listelenmez. </span></h4>

                    </div>
                </div>
                <div class="row">        
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="text" class="form-control" name="otp_search_phone" id="otp_search_phone" placeholder="5361234567">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kullanıcı Id numarası</label>
                            <input type="text" class="form-control" name="otp_search_userid" id="otp_search_userid" placeholder="">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Durum</label>
                            <select class="form-control" id="otp_search_status">
                                <option value="" selected>Tümü</option>
                                <option value="1">Doğrulandı</option>
                                <option value="0">Doğrulama Bekliyor</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group"><br>
                            <button type="button" class="btn btn-success" name="otp_search_button" id="otp_search_button">Ara</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="netgsm_tablehead">#</th>
                                    <th class="netgsm_tablehead">ID</th>
                                    <th class="netgsm_tablehead">Telefon Numarası</th>
                                    <th class="netgsm_tablehead">Doğrulama Kodu</th>
                                    <th class="netgsm_tablehead">Gönderilme Zamanı</th>
                                    <th class="netgsm_tablehead">Durum</th>
                                </tr>
                            </thead>
                            <tbody id="otp_table_body">
                             
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-8">
                        <div id="paginationinfos" style="padding-bottom: 10px;">
                        </div>
                    </div>
                </div>
           </div>
        </div>';

        $scriptString .= "<script>
            $(document).ready(function() {
                getOtpList(1);
            });
            
            document.getElementById('otp_search_button').addEventListener('click',function(){
                getOtpList(1);
            });
        
            function getOtpList(page){
                             
                 var inputUser = document.getElementById('otp_search_userid').value.trim();
                 var inputPhone = document.getElementById('otp_search_phone').value.trim();
                 var inputStatus = document.getElementById('otp_search_status').value.trim();
                 var otp_inputs = {inputUser:inputUser,inputPhone:inputPhone,inputStatus:inputStatus,page:page};
                 
                 
                 $.ajax({
                    type: 'POST',
                    url:  addonPath+'ajax.php',
                    data: {otp_inputs:otp_inputs,lang:lang},
                    error: function(data){
                        alert('" . $LANG['errortoadmin'] . "!!!');
                    },
                    success: function(data) {
                        
                        data = JSON.parse(data);
                        if(data['tbody'] !=''){
                        document.getElementById('otp_table_body').innerHTML = data['tbody'];
                        }
                        else{document.getElementById('otp_table_body').innerHTML = '<tr><td colspan=\'6\'><strong>KAYIT YOK</strong></td></tr>';}
                        document.getElementById('paginationinfos').innerHTML = data['pagination'];
                      
                    }
                      
                 });

            }
        
        </script>";

        echo $htmlString . $scriptString;
    }

    $service = null;

    echo '<div style="padding: 10px;">
            ' . $LANG['lisans'] . '
          </div>';

}

function netgsm_clientarea($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $lang = $vars['lang'];

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new ClientDispatcher();
    return $dispatcher->dispatch($action, $vars);
}