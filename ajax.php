<?php

if (!empty($_POST)) {

    include_once('SmsService.php');
    include_once('sender/Request.php');
    include_once('sender/SMS.php');
    
    $service = new SmsService();

    if (empty($_SESSION['adminid'])) {
        http_response_code(403);
        exit("UNAUTHORIZED REQUEST");
    }
   
    /**
     * Getting usercode and password
     */
    if (isset($_POST['gettitles']) && isset($_POST['usercode']) && isset($_POST['password'])) {
        
        try {
            $titles = $service->getTitleAsArray($_POST['usercode'], $_POST['password']);
            echo json_encode($titles);
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
        }
    }
 
    if (isset($_POST['action'])) {
       
        if (trim($_POST['action']) == "validateOtp" and isset($_POST['netgsm_reference_code']) and isset($_POST['netgsm_otp_id']) and is_numeric($_POST['netgsm_otp_id']) and isset($_POST['netgsm_otp_code']) and !is_numeric($_POST['netgsm_otp_code'])) {

            echo json_encode(array('status' => 0, 'message' => 'Doğrulama kodu sadece sayılardan oluşabilir.'));

        } elseif (trim($_POST['action']) == "validateOtp" and isset($_POST['netgsm_reference_code']) and isset($_POST['netgsm_otp_id']) and is_numeric($_POST['netgsm_otp_id']) and isset($_POST['netgsm_otp_code']) and is_numeric($_POST['netgsm_otp_code'])) {

            $otp_id = $_POST['netgsm_otp_id'];
            $reference_code = $_POST['netgsm_reference_code'];

            $sql = "SELECT `id`, `verification_code`, `reference_code`, `userid`, `phonenumber`, `start_otp_date`, `durum` 
                    FROM `netgsm_sms_otp`
                    WHERE now()<=DATE_ADD(start_otp_date, INTERVAL 5 MINUTE) and id=:otp_id and reference_code=:reference LIMIT 1";

            $stmt = $service->getConnection()->runSelectQuery($sql, array(':otp_id' => $otp_id, ':reference' => $reference_code));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($row['durum'] == 0) {
                    if ($row['verification_code'] === trim($_POST['netgsm_otp_code'])) {
                        $sql = " update `netgsm_sms_otp` set durum = 1 where id=:otp_id and reference_code=:reference";
                        $service->getConnection()->runSelectQuery($sql, array(':otp_id' => $otp_id, ':reference' => $reference_code));
                        echo json_encode(array('status' => 1, 'message' => 'Telefon Doğrulaması Tamamlandı.'));
                    } else {
                        echo json_encode(array('status' => 0, 'message' => 'Gönderilen doğrulama kodu ile girdiğiniz kod uyuşmamaktadır.'));
                    }
                } else {
                    echo json_encode(array('status' => 0, 'message' => 'Telefon doğrulaması daha önce yapılmış.'));
                }
            } else {
                echo json_encode(array('status' => 0, 'message' => 'Doğrulanacak telefon numarası bulunamadı veya geçerlilik süresi dolmuş.'));
            }
        } elseif (trim($_POST['action']) == 'resendOtp' and isset($_POST['netgsm_reference_code'])) {

            $reference_code = $_POST['netgsm_reference_code'];
            $phonenumber = $_POST['phonenumber'];

            $sql = "SELECT c.firstname,c.lastname,nso.id, nso.`verification_code`, nso.`reference_code`, `userid`, nso.`phonenumber`, nso.`start_otp_date`,(select now()) as end_otp_date, nso.`durum` 
                    FROM `netgsm_sms_otp` as nso
                    inner join tblclients as c on c.id=nso.userid
                    WHERE reference_code=:reference LIMIT 1";

            $stmt = $service->getConnection()->runSelectQuery($sql, array(':reference' => $reference_code));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $gsm = $row['phonenumber'];
            $start_otp_date = $row['start_otp_date'];
            $end_otp_date = $row['end_otp_date'];
            $userid = $row['userid'];
            $durum = trim($row['durum']);
            $gsm = $service->getPhoneNumberByUseridAndTemplateName($userid, 'ClientAreaRegister');

            $start_otp_date_second = strtotime($start_otp_date);
            $end_otp_date_second = strtotime($end_otp_date);
            $difference = $end_otp_date_second - $start_otp_date_second;

            if ($durum === "0" and $difference <= 300) {
                echo json_encode(array('status' => 0, 'message' => 'Aktif bir kodunuz mevcut. Yeni kod gönderilmedi.'));
            } elseif ($durum === "0" and $difference > 300) {
                $code = rand(100000, 999999);
                $new_reference_code = sha1($gsm . time() . $userid . $code);

                $sql = "update `netgsm_sms_otp` set durum=0,start_otp_date=now(),end_otp_date=date_add(now(),INTERVAL 120 SECOND), verification_code=:code,reference_code =:new_reference_code, phonenumber=:phonenumber where reference_code=:reference and userid=:usr";

                $array_execute = array(':new_reference_code' => $new_reference_code, ':code' => $code, ':phonenumber' => $gsm, ':reference' => $reference_code, ':usr' => $userid);
                $stmt = $service->getConnection()->runSelectQuery($sql, $array_execute);

                $template = $service->getTemplateDetails('ClientAreaRegister');
                $templateRow = $template->fetch(PDO::FETCH_ASSOC);
                $settings = $service->getSettings();
                $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
                $message = $templateRow['template'];
                $fields = $service->getFieldsWithName('ClientAreaRegister');
                if (strpos($message, "{code}") !== false) {
                    $message = str_replace("{code}", $code, $message);
                }
               
                while ($field = $fields->fetch(PDO::FETCH_ASSOC)) {
                    if (strpos($message, "{" . $field['field'] . "}") !== false) {
                        $replaceto = $row[$field['field']];
                        $message = str_replace("{" . $field['field'] . "}", $replaceto, $message);
                    }
                }


                $SMSArray = array();
                array_push($SMSArray, new SMS($message, $gsm));
                $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                
                $result = $request->XMLPOSTOTP();
                $remaining_time = 300;

                echo json_encode(array('status' => 1, 'message' => 'Yeni doğrulama kodu ' . $gsm . ' numarasına gönderildi.', 'reference_code' => $new_reference_code, 'remaining_time' => $remaining_time));
            } else {
                echo json_encode(array('status' => 0, 'message' => 'Numara bulunamadı. Yeni kod gönderilmedi.'));
            }

        }

    }
    
    /**
     *  Sending SMS from client summary page
     */
    if (isset($_POST['smsgonderuserid']) && isset($_POST['message']) && isset($_POST['fieldname'])) {
        
        include_once('sender/SMS.php');
        include_once('sender/Request.php');
        
        if (is_numeric($_POST['smsgonderuserid'])) {
            $SMSArray = array();

            $row = $service->getSettings()->fetch();
            $defaulmsgheader = $row['defaultmsgheader'];
            $usercode = $row['usercode'];
            $password = $row['password'];

            $smsgonderuserid = $_POST['smsgonderuserid'];
            $fieldname = $_POST['fieldname'] != '' ? $_POST['fieldname'] : null;
            $row = $service->getUserInfoByUserId($smsgonderuserid, $fieldname);

            array_push($SMSArray, new SMS(trim($_POST['message']), $row['phone']));

            $request = new Request($defaulmsgheader, $SMSArray, $usercode, $password);
            $request->prepareXMLRequest();

            $result = $request->XMLPOST();
            echo $result;

        } else {
            echo json_encode(array('status' => 0, 'message' => 'Hatalı İşlem, Yöneticinize Başvurun'));
        }

    }
  
    /**
     *  Updating Usercode and Password
     */
    if (isset($_POST['aboneid']) && isset($_POST['abonepass']) && isset($_POST['smsfield']) && isset($_POST['defaultmsgheader'])) {
      
        $u = $_POST['aboneid'];
        $p = $_POST['abonepass'];
        $f = $_POST['smsfield'];
        $d = $_POST['defaultmsgheader'];
        $blockedsmsfieldname = $_POST['blockedsmsfieldname'];
        $blockedotpsmsfieldname = $_POST['blockedotpsmsfieldname'];
        $loginredirectpage = $_POST['loginredirectpage'];
        $smslanguage = $_POST['smslanguage'];
        $date_format = $_POST['date_format'];

        $result = $service->updateSettings($u, $p, $f, $d, $blockedsmsfieldname, $blockedotpsmsfieldname, $loginredirectpage, $smslanguage, $date_format);

        echo json_encode($result);
    }
    
    /**
     * Sending SMS To All Clients
     */
    if (isset($_POST['numbers']) && isset($_POST['message']) && isset($_POST['title'])) {
        
        echo (string)$service->XMLPOST($_POST['numbers'], $_POST['message'], $_POST['title']);
    }

    /**
     * Getting Fields Of a Template
     */
    if (isset($_POST['templateId']) && isset($_POST['lang'])) {
        $LANG = $_POST['lang'];
        $fieldStmt = $service->getFields($_POST['templateId']);
        $o = '<option value="" selected>' . $LANG['select'] . '</option>';
        while ($fieldRow = $fieldStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($LANG['lang'] === "english")
                $o .= "<option value='" . $fieldRow['field'] . "'>" . ucfirst($fieldRow['field']) . "</option>";
            else
                $o .= "<option value='" . $fieldRow['field'] . "'>" . $fieldRow['field_tr'] . "</option>";

        }
        echo json_encode($o);
    }
    /*
     * Sending Bulk SMS
     */
    if (isset($_POST['hookId']) && isset($_POST['title']) && isset($_POST['message']) && isset($_POST['admingsm']) && isset($_POST['smsfieldname'])) {
        $result = $service->updateTemplate($_POST['hookId'], $_POST['title'], $_POST['message'], $_POST['admingsm'], $_POST['extra'], $_POST['smsfieldname']);
        echo json_encode($result ? 1 : 0);
        
    }
    
    /**
     * Searching Clients
     */
    if (isset($_POST['inputs']) && isset($_POST['lang'])) {
        $allClientsArray = [];
        $output = "";
        $inputs = $_POST['inputs'];
        $page = $inputs['page'];
        $rowLimit = $inputs['inputLimit'];

        $queries = $service->getSelectAndCountQuery($inputs);

        $arrayOfPhone = isset($inputs['arrayOfPhone'])?$inputs['arrayOfPhone']:array();
        $clients = $service->getConnection()->runSelectQuery($queries['selectQuery']);
        $selectAllClientStmt = $service->getConnection()->runSelectQuery($queries['selectQueryWithoutLimit']);

        while ($selectAllClient = $selectAllClientStmt->fetch(PDO::FETCH_ASSOC))
            array_push($allClientsArray, $selectAllClient['id'] . "_" . $selectAllClient['phone']);

        $rowCount = $service->getConnection()->runSelectQuery($queries['countQuery'])->fetch(PDO::FETCH_ASSOC)['total'];

        $LANG = array("Active" => "Aktif", "Inactive" => "Pasif", "Closed" => "Kapalı");

        $checkBoxID = "checkBoxId";
        $i = 0;
        while ($clientRow = $clients->fetch(PDO::FETCH_ASSOC)) {
            $output .= "<tr>";
            if (in_array($clientRow['id'] . "_" . $clientRow['phone'], $arrayOfPhone))
                $output .= '<td><input id="' . $checkBoxID . $i . '" type="checkbox" class="checkall big-checkbox" checked onchange="changePhoneNumber(this)"></td>';
            else
                $output .= '<td><input id="' . $checkBoxID . $i . '" type="checkbox" class="checkall big-checkbox" onchange="changePhoneNumber(this)"></td>';
            $output .= '<td>' . $clientRow['id'] . '</td>';
            $output .= '<td>' . $clientRow['firstname'] . '</td>';
            $output .= '<td>' . $clientRow['lastname'] . '</td>';
            $output .= '<td>' . $clientRow['phone'] . '</td>';
            $output .= '<td>' . $clientRow['companyname'] . '</td>';
            $output .= '<td>' . $clientRow['email'] . '</td>';
            $LANG = $_POST['lang'];
            if ($clientRow['status'] === "Active")
                $output .= '<td><span class="label active">' . $LANG['active'] . '</span></td>';
            else if ($clientRow['status'] === "Closed") {
                $output .= '<td><span class="label closed">' . $LANG['closed'] . '</span></td>';
            } else {
                $output .= '<td><span class="label" style="background-color: goldenrod">' . $LANG['inactive'] . '</span></td>';
            }
            $output .= '</tr>';

        }
       
        $pagination = $service->preparePaginationLinks($rowCount, $rowLimit, $page);
        echo json_encode(array("tbody" => $output, "pagination" => $pagination, "rowcount" => $rowCount, "allClientsArray" => $allClientsArray, "selectQuery" => $queries['selectQuery'], "countQuery" => $queries['countQuery']));

    }

    /**
     * Searching OTP SMS
     */
    if (isset($_POST['otp_inputs']) and isset($_POST['lang'])) {
        
        $output = '';
        $otp_inputs = $_POST['otp_inputs'];
        $page = $otp_inputs['page'];
        $inputUser = $otp_inputs['inputUser'];
        $inputPhone = $otp_inputs['inputPhone'];
        $inputStatus = $otp_inputs['inputStatus'];

        $queries = $service->getOtpListQueries($otp_inputs);

        $selectQuery = $queries['selectQuery'];
        $countQuery = $queries['countQuery'];

//        print_r($queries);exit;

        $otp_list = $service->getConnection()->runSelectQuery($queries['selectQuery'], $queries['array_execute']);
        $stmt_count = $service->getConnection()->runSelectQuery($queries['countQuery'], $queries['array_execute']);

//        print_r($otp_list);exit;

        $row_count = $stmt_count->fetch();
        $rowCount = $row_count['toplam'];

        $i = 0;
        while ($otp_row = $otp_list->fetch()) {

//            print_r($otp_row);exit;

            $output .= '<tr>';
            $output .= '<td>' . ++$i . '</td>';
            $output .= '<td>' . $otp_row['userid'] . '</td>';
            $output .= '<td>' . $otp_row['phonenumber'] . '</td>';
            $output .= '<td>' . $otp_row['verification_code'] . '</td>';
            $output .= '<td>' . $otp_row['start_otp_date'] . '</td>';
            $output .= '<td style="color:' . ($otp_row['durum'] == "1" ? 'green' : 'red') . '"><strong>' . ($otp_row['durum'] == "1" ? 'Doğrulandı' : 'Doğrulama Bekliyor') . '</strong></td>';
            $output .= '</tr>';
        }

        $rowLimit = 10;
        $pagination = $service->preparePaginationLinks($rowCount, $rowLimit, $page, 'getOtpList');

        echo json_encode(array('tbody' => $output, 'toplam_kayit' => $rowCount, 'pagination' => $pagination));
    }
    
    /**
     * Getting All Templates
     */
    if (isset($_POST['type']) && isset($_POST['lang'])) {
        $LANG = $_POST['lang'];
        $type = $_POST['type'];

        try {
            $templates = $service->getAllTemplates($type);
        } catch (Exception $e) {
            $type = 'admin';
            $templates = null;
        }
        if ($type == "admin") {
            $output = '<tr>
                    <th class="table-head" style="width:75px;">ID</th>
                    <th class="table-head" style="width: 200px;">' . $LANG["templatename"] . '</th>
                    <th class="table-head" style="width: 500px;">' . $LANG["template"] . '</th>
                    <th class="table-head" width="100">' . $LANG["title"] . '</th>
                    <th class="table-head" width="250" height="35">' . $LANG["admingsm"] . '</th>
                    <th class="table-head" style="width:75px;">' . $LANG["edit"] . '/' . $LANG["status"] . '</th>
                    <th style="display: none;">' . $LANG["description"] . '</th>
                </tr> ';

            if ($templates !== null) {
                while ($template = $templates->fetch(PDO::FETCH_ASSOC)) {
                    $output .= '<tr>';
                    $output .= '<td >' . $template['id'] . '</td>';
                    if ($LANG['lang'] === "english")
                        $output .= '<td style="width: 200px;">' . $template['name'] . '</td>';
                    else {
                        $output .= '<td style="width: 200px;">' . $template['name_tr'] . '</td>';
                    }
                    $output .= '<td style="width: 500px;">' . $template['template'] . '</td>';
                    $output .= '<td width="100">' . $template['title'] . '</td>';
                    $output .= '<td width="250" height="35">
                                <div style="width: 250px; height: 35px; overflow: auto">
                                    ' . $template['admingsm'] . '
                                </div>
                           </td>';
                    $output .= '<td style="width:75px;"> 
                    <div class="row">
                        <div class="col-md-6 col-xs-6 col-sm-6">
                            <a href="#0"  data-toggle="modal" class="btn btn-xs btn-warning" onclick="callModal(this)">
                                <span class="glyphicon glyphicon-edit"></span>
                            </a>
                        </div>
                        <div class="col-md-6 col-xs-6 col-sm-6">
                            <input style="margin-top: 0px;" type="checkbox" id="checkall"  class="large-checkbox" ';
                    if ($template['active'] == 1)
                        $output .= 'checked ';
                    $output .= ' onchange="changeTemplateStatus(this)">
                        </div>
                    </div></td>';
                    $output .= '<td style="display: none;">' . json_decode($template['description'], true)['' . $LANG["lang"] . ''] . '</td>';
                    $output .= '</tr>';

                }
            }
           
        } else {
            $output = '<tr>
                    <th class="table-head" style="width:75px;">ID</th>
                    <th class="table-head" style="width: 275px;">' . $LANG["templatename"] . '</th>
                    <th class="table-head" style="width: 500px;">' . $LANG["template"] . '</th>
                    <th class="table-head" width="100">' . $LANG["title"] . '</th>
                    <th class="table-head" style="width:75px;">' . $LANG["edit"] . '/' . $LANG["status"] . '</th>
                    <th class="table-head" style="width:75px; display: none;">Smsfieldname</th>
                    <th style="display: none;">' . $LANG["description"] . '</th>
                </tr>';
            while ($template = $templates->fetch(PDO::FETCH_ASSOC)) {

                $output .= '<tr>';
                $output .= '<td style="max-width: 20px;">'. $template['id'] . '</td>';
                if ($LANG['lang'] === "english")
                    $output .= '<td style="width: 200px;">' . $template['name'] . '</td>';
                else {
                    $output .= '<td style="width: 200px;">' . $template['name_tr'] . '</td>';
                }
                $output .= '<td style="width: 775px;">' . $template['template'] . '</td>';
                $output .= '<td width="100">' . $template['title'] . '</td>';
                $output .= '<td style="width:75px;"> <p>
                    <div class="row">
                        <div class="col-md-6">
                            <a  href="#0"  data-toggle="modal" class="btn btn-xs btn-warning" onclick="callModal(this)">
                                <span class="glyphicon glyphicon-edit"></span> 
                            </a>
                        </div>
                        <div class="col-md-6">
                            <input style="margin-top: 0px;" type="checkbox" id="checkall"  class="large-checkbox"';
                if ($template['active'] == 1)
                    $output .= ' checked ';
                $output .= ' onchange="changeTemplateStatus(this)">
                        </div>
                    </div></td>';
                $output .= '<td style="display: none;">' . $template['smsfieldname'] . '</td>';
                $output .= '<td style="display: none;">' . $template['extra'] . '</td>';
                $output .= '<td style="display: none;">' . json_decode($template['description'], true)['' . $LANG["lang"] . ''] . '</td>';
                $output .= ' </tr>';
            }

        }
        echo json_encode($output);
    }
    
    /**
     * Changing Template Status
     */
    if (isset($_POST['templateIdforStatus']) && isset($_POST['status'])) {

        $result = $service->changeTemplateStatus($_POST['templateIdforStatus'], $_POST['status']);
        echo($result ? 1 : 0);
    }

    if (isset($_POST['initialize_templates'])) {
       
       
        $hooks_number = $service->initializeHooks();
        
        // $fieldsStatus = $service->setFields();
        
        return $hooks_number;
    }

    unset($_POST['aboneid']);
    unset($_POST['abonepass']);

    unset($_POST['numbers']);
    unset($_POST['message']);
    unset($_POST['title']);

    unset($_POST['templateId']);

    unset($_POST['hookId']);
    unset($_POST['title']);
    unset($_POST['message']);
    unset($_POST['admingsm']);

    unset($_POST['inputs']);

    unset($_POST['type']);

    unset($_POST['templateIdforStatus']);
    unset($_POST['status']);
    unset($_POST['lang']);


}