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

require_once dirname(dirname(dirname(__FILE__))) . "/config/_config.php";

$Tests = [
    'cat_base_url',
    'ssp',
    'security',
    'php',
    'phpModules',
    'openssl',
    'zip',
    'directories',
    'locales',
    'defaults',
    'databases',
    'device_cache',
    'mailer',
];

if (CONFIG['FUNCTIONALITY_LOCATIONS']['CONFASSISTANT_SILVERBULLET'] == "LOCAL" || CONFIG['FUNCTIONALITY_LOCATIONS']['CONFASSISTANT_RADIUS'] == "LOCAL" ) {
    $Tests[] = 'makensis';
    $Tests[] = 'makensis=>NSISmodules';
}

if (CONFIG['FUNCTIONALITY_LOCATIONS']['DIAGNOSTICS'] == "LOCAL") {
    $Tests[] = 'eapol_test';
}

ini_set('display_errors', '0');

/**
 * creates the HTML code displaying the result of a test that was run previously
 * 
 * @param \core\SanityTests $test the test that was run
 * @return string
 * @throws Exception
 */
function print_test_results($test) {
    $out = '';
    switch ($test->test_result['global']) {
        case \core\common\Entity::L_OK:
            $message = "Your configuration appears to be fine.";
            break;
        case \core\common\Entity::L_WARN:
            $message = "There were some warnings, but your configuration should work.";
            break;
        case \core\common\Entity::L_ERROR:
            $message = "Your configuration appears to be broken, please fix the errors.";
            break;
        case \core\common\Entity::L_REMARK:
            $message = "Your configuration appears to be fine.";
            break;
        default:
            throw new Exception("The result code level " . $test->test_result['global'] . " is not defined!");
    }
    $uiElements = new web\lib\admin\UIElements();
    $out .= $uiElements->boxFlexible($test->test_result['global'], "<br><strong>Test Summary</strong><br>" . $message . "<br>See below for details<br><hr>");
    foreach ($test->out as $testValue) {
        foreach ($testValue as $o) {
            $out .= $uiElements->boxFlexible($o['level'], $o['message']);
        }
    }
    return($out);
}

if (!in_array("I do not care about security!", CONFIG['SUPERADMINS'])) {
    $auth = new \web\lib\admin\Authentication();
    $auth->authenticate();
    $user = new \core\User($_SESSION['user']);
    if (!$user->isSuperadmin()) {
        throw new Exception("Not Superadmin");
    }
}
$test = new \core\SanityTests();
$test->run_tests($Tests);
$format = empty($_REQUEST['format']) ? 'include' : $_REQUEST['format'];
switch ($format) {
    case 'include':
        $o = print_test_results($test);
        print "<table>$o</table>";
        break;
    case 'html':
        header("Content-Type:text/html;charset=utf-8");
        echo "<!DOCTYPE html>
          <html xmlns='http://www.w3.org/1999/xhtml' lang='$ourlocale'>
          <head lang='$ourlocale'>
          <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head>";

        $o = print_test_results($test);
        print "<body><table>$o</table></body></html>";
        break;
    case 'json':
        header('Content-type: application/json; utf-8');
        print json_encode(['global' => $test->test_result, 'details' => $test->out]);
        break;
    case 'print_r':
        echo "<!DOCTYPE html>
          <html xmlns='http://www.w3.org/1999/xhtml' lang='$ourlocale'>
          <head lang='$ourlocale'>
          <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head>";
        print "<body><pre>";
        print_r(['global' => $test->test_result, 'details' => $test->out]);
        print "</pre><body>";
        break;
    default:
        break;
}
