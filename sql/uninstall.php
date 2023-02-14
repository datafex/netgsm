<?php
/**
 * Created by PhpStorm.
 * User: Engin BAKIR
 * Date: 17.12.2019
 * Time: 11:23
 */

$query = '';

$query .= "DROP TABLE IF EXISTS `netgsm_sms_fields`;";
$stmt = $conn->prepare($query);
$result = $stmt->execute();

$query .= "DROP TABLE IF EXISTS `netgsm_sms_settings`;";
$stmt = $conn->prepare($query);
$result = $stmt->execute();

$query .= "DROP TABLE IF EXISTS `netgsm_sms_templates`;";
$stmt = $conn->prepare($query);
$result = $stmt->execute();



