<?php
/**
 * Created by PhpStorm.
 * User: Engin BAKIR
 * Date: 5.09.2019
 * Time: 10:22
 */


//
//if (!empty($_POST)) {
//
//    include_once('SmsService.php');
//
//    $service = new SmsService();
//    $settings = $service->getSettings();
//    $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
//
//    array_push($SMSArray, new SMS($message, trim($gsm)));
//    $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
//    $request->prepareXMLRequest();
//    $request->XMLPOST();
//}