<?php
/**
 * Created by PhpStorm.
 * User: Engin BAKIR
 * Date: 17.12.2019
 * Time: 11:20
 */

$query='';

$query .= "CREATE TABLE IF NOT EXISTS `netgsm_sms_settings` 
    (`id` int(11) NOT NULL AUTO_INCREMENT,`usercode` varchar(255) NOT NULL,`smsfieldname` varchar(255) NOT NULL,`password` varchar(255) NOT NULL,`defaultmsgheader` varchar(255) NOT NULL, 
    `gsmnumberfield` int(11) DEFAULT NULL,`dateformat` varchar(12) CHARACTER SET utf8 DEFAULT NULL,
    `version` varchar(6) CHARACTER SET utf8 DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";


$query .= "INSERT INTO `netgsm_sms_settings` (`usercode`,`smsfieldname`, `password`, `defaultmsgheader`,`gsmnumberfield`,`dateformat`, `version`) 
        VALUES ('', '','','', 0,'%d.%m.%y','1.0');";

$query .= "CREATE TABLE IF NOT EXISTS `netgsm_sms_templates` 
    (`id` int(11) NOT NULL AUTO_INCREMENT,`name` varchar(50) CHARACTER SET utf8 NOT NULL,`name_tr` varchar(50) CHARACTER SET utf8 NOT NULL,
    `smsfieldname` varchar(255) NOT NULL,
    `type` enum('client','admin') CHARACTER SET utf8 NOT NULL,`admingsm` varchar(255) CHARACTER SET utf8 NOT NULL,
    `template` varchar(240) CHARACTER SET utf8 NOT NULL,`title` varchar(16) CHARACTER SET utf8 NOT NULL,
    `variables` varchar(500) CHARACTER SET utf8 NOT NULL,`active` tinyint(1) NOT NULL,`extra` varchar(3) CHARACTER SET utf8 NOT NULL,
    `description` text CHARACTER SET utf8,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";


$query .= "CREATE TABLE IF NOT EXISTS `netgsm_sms_fields`
        (`id` int(11) NOT NULL AUTO_INCREMENT,`hook_id` int(11) NOT NULL ,
        `field` varchar(100) CHARACTER SET utf8 NOT NULL,`field_tr` varchar(100) CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

$query .="CREATE TABLE IF NOT EXISTS `netgsm_sms_otp`
        (`id` int(11) NOT NULL AUTO_INCREMENT,`verification_code` varchar(6) CHARACTER SET utf8 NOT NULL,
        `reference_code` varchar(40) CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";