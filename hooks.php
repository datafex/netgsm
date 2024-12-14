<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

try {
    require_once("sender/Request.php");
    require_once('sender/SMS.php');
    require_once('SmsService.php');

    $hooks = getHooks();
    foreach ($hooks as $hook) {
        $result = add_hook($hook['hook'], 1, $hook['function'], "");
    }

    add_hook('ClientAreaHeadOutput', 1, function ($vars) {
        return '<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
			<script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>';
    });



    ///////////////////////***************************************************///////////////////////

    add_hook('AdminAreaClientSummaryPage', 1, function ($vars) {
        $service = new SmsService();
        $row = $service->getSettings()->fetch();
        $smsfieldname = $row['smsfieldname'];
        $defaultmsgheader = $row['defaultmsgheader'];

        $script = '<script> 

        function sendSmsNetgsm(){
            var message = $("#smsmessage").val();
            var smsgonderuserid = $("#smsgonderuserid").val();
            var fieldname = $("#inputPhoneType").val();
            if(message.trim() == ""){
                alert("Bir Mesaj Giriniz");
                return false;
            }
             
            $.ajax({
                type: "POST",
                url:  "../modules/addons/netgsm/ajax.php",
                data: {smsgonderuserid:smsgonderuserid,message:message,fieldname:fieldname},
                error: function(data){
                    console.log(\'Error:\', data);
                },
                success: function(data) {           
                    data = data.split(\' \');
                    if(data[0]=="00"){
                        alert("Mesajınız Başarıyla Gönderildi.");
                    }else{
                        alert("Mesaj Gönderimi Başarışız.");
                    }
                }
            });
        }
    </script>
';

        $html = '';
//        $html .= '<div class="row client-summary-panels">';
//        $html .= '<div class="col-lg-3 col-sm-6">';
        $html .= '<div class="clientssummarybox">';
        $html .= '<div class="title">Sms Gönder</div>';
        $html .= '<div align="center">';

        $html .= '<input type="hidden" name="smsgonderuserid" id="smsgonderuserid" value="' . $vars['userid'] . '">';
        $html .= '<textarea class="form-control bottom-margin-5" name="smsmessage" id = "smsmessage" rows="2"  placeholder="Mesajınız.." style="resize:none;"></textarea>';
        $html .= '<select name="inputPhoneType" id="inputPhoneType" class="form-control bottom-margin-5"><option value="" selected>WHMCS</option><option value="' . $smsfieldname . '">' . $smsfieldname . '</option></select>';

        $html .= '<button type="button" onclick="sendSmsNetgsm()" id="sendsmsbutton" class="button btn btn-default">Gönder</button>';

        $html .= '</div>';
        $html .= '</div>';
//        $html .= '</div>';
//        $html .= '</div>';

        $script_1 = '<script>$(document).ready(function(){
            
            $(".client-summary-panels").children().last().append(\'' . $html . '\');
                        
        });</script>';

        return $script_1 . $script;

    });

} catch (Exception $e) {

}

function getHooks()
{
    $file = array();
    if ($handle = opendir(dirname(__FILE__) . '/hooks')) {
        while (false !== ($entry = readdir($handle))) {
            if (substr($entry, strlen($entry) - 4, strlen($entry)) == ".php") {
                array_push($file, include_once('hooks/' . $entry));
            }
        }
        closedir($handle);
    }
    return $file;
}

$service = null;