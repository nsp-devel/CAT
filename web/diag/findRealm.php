<?php
/*
 * *****************************************************************************
 * Contributions to this work were made on behalf of the GÉANT project, a 
 * project that has received funding from the European Union’s Framework 
 * Programme 7 under Grant Agreements No. 238875 (GN3) and No. 605243 (GN3plus),
 * Horizon 2020 research and innovation programme under Grant Agreements No. 
 * 691567 (GN4-1) and No. 731122 (GN4-2).
 * On behalf of the aforementioned projects, GEANT Association is the sole owner
 * of the copyright in all material which was developed by a member of the GÉANT
 * project. GÉANT Vereniging (Association) is registered with the Chamber of 
 * Commerce in Amsterdam with registration number 40535155 and operates in the 
 * UK as a branch of GÉANT Vereniging.
 * 
 * Registered office: Hoekenrode 3, 1102BR Amsterdam, The Netherlands. 
 * UK branch address: City House, 126-130 Hills Road, Cambridge CB2 1PQ, UK
 *
 * License: see the web/copyright.inc.php file in the file structure or
 *          <base_url>/copyright.php after deploying the software
 */

/**
 * This file executes AJAX searches from diagnostics page.
 * 
 *
 * @author Maja Gorecka-Wolniewicz <mgw@umk.pl>
 *
 * @package Developer
 */
function check_my_nonce($nonce, $optSalt='') {
    $remote = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    $lasthour = date("G")-1<0 ? date('Ymd').'23' : date("YmdG")-1;
    if (hash_hmac('sha256', session_id().$optSalt, date("YmdG").'1qaz2wsx3edc!QAZ@WSX#EDC'.$remote) == $nonce || 
        hash_hmac('sha256', session_id().$optSalt, $lasthour.'1qaz2wsx3edc!QAZ@WSX#EDC'.$remote) == $nonce) {
        return true;
    } else {
        return false;
    }
}
require_once dirname(dirname(dirname(__FILE__))) . "/config/_config.php";

// we are referring to $_SESSION later in the file
CAT_session_start();

$loggerInstance = new \core\common\Logging();
$returnArray = [];
//$headers = apache_request_headers();
//$is_ajax = (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest');
//$nonce = filter_input(INPUT_GET, 'myNonce', FILTER_SANITIZE_STRING);

//if (!$is_ajax || check_my_nonce($nonce, $_SESSION['current_page'])) {
//    $loggerInstance->debug(4, 'A hostile AJAX call');
//} else {
    $languageInstance = new \core\common\Language();
    $languageInstance->setTextDomain("web_user");
    $cat = new \core\CAT();
    $realmByUser = filter_input(INPUT_GET, 'realm', FILTER_SANITIZE_STRING);
    $realmQueryType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    $realmCountry = filter_input(INPUT_GET, 'co', FILTER_SANITIZE_STRING);
    $realmOu = filter_input(INPUT_GET, 'ou', FILTER_SANITIZE_STRING);
    if (!empty($realmByUser)) {
        /* select the record matching the realm */
        $details = $cat->getExternalDBEntityDetails(0, $realmByUser);
        if (!empty($details)) {
            $admins = array();
            if (!empty($details['admins'])) {
                foreach ($details['admins'] as $admin) {
                    $admins[] = $admin['email'];
                }
                $details['admins'] = base64_encode(join(',',$admins));
            } else {
                $details['admins'] = '';
            }
            $details['status'] = 1;
            $returnArray = $details;
        }
    } else { 
        if ($realmQueryType) {
            if ($realmQueryType == "co") {
                /* select countries list */
                $details = $cat->getExternalCountriesList();
                if (!empty($details)) {
                    $returnArray['status'] = 1;
                    $returnArray['time'] = $details['time'];
                    unset($details['time']);
                    $returnArray['countries'] = $details;
                } 
            }
            if ($realmQueryType == "inst") {
                if ($realmCountry) {
                    $fed = new \core\Federation(strtoupper($realmCountry));
                    $details = $fed->listExternalEntities(FALSE);
                    if (!empty($details)) {
                        $returnArray['status'] = 1;
                        $returnArray['institutions'] = $details;
                    }    
                }
            }
            if ($realmQueryType == "realm") {
                if ($realmOu) {
                    $details = $cat->getExternalDBEntityDetails($realmOu);
                    if (!empty($details)) {
                        $returnArray['status'] = 1;
                        $returnArray['realms'] = explode(',',$details['realmlist']);
                    }   
                }
            }
        }
    }
    if (empty($returnArray)) {
        $returnArray['status'] = 0;
    }
//}
echo(json_encode($returnArray));

