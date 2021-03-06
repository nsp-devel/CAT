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

$auth = new \web\lib\admin\Authentication();
$loggerInstance = new \core\common\Logging();
$deco = new \web\lib\admin\PageDecoration();
$validator = new \web\lib\common\InputValidation();
$optionParser = new \web\lib\admin\OptionParser();
$ui = new \web\lib\admin\UIElements();

if (isset($_POST['submitbutton']) && $_POST['submitbutton'] == web\lib\common\FormElements::BUTTON_DELETE && isset($_GET['inst_id'])) {
    $auth->authenticate();
    $my_inst = $validator->IdP($_GET['inst_id'], $_SESSION['user']);
    $instId = $my_inst->identifier;
    // delete the IdP and send user to enrollment
    $my_inst->destroy();
    $loggerInstance->writeAudit($_SESSION['user'], "DEL", "IdP " . $instId);
    header("Location: overview_user.php");
    exit;
}

if (isset($_POST['submitbutton']) && $_POST['submitbutton'] == web\lib\common\FormElements::BUTTON_FLUSH_AND_RESTART && isset($_GET['inst_id'])) {
    $auth->authenticate();
    $my_inst = $validator->IdP($_GET['inst_id'], $_SESSION['user']);
    $instId = $my_inst->identifier;
    //
    $profiles = $my_inst->listProfiles();
    foreach ($profiles as $profile) {
        $profile->destroy();
    }
    // flush all IdP attributes and send user to creation wizard
    $my_inst->flushAttributes();
    $loggerInstance->writeAudit($_SESSION['user'], "DEL", "IdP starting over" . $instId);
    header("Location: edit_idp.php?inst_id=$instId&wizard=true");
    exit;
}


echo $deco->pageheader(sprintf(_("%s: IdP enrollment wizard (step 2 completed)"), CONFIG['APPEARANCE']['productname']), "ADMIN-IDP");
$my_inst = $validator->IdP($_GET['inst_id'], $_SESSION['user']);

if ((!isset($_POST['submitbutton'])) || (!isset($_POST['option'])) || (!isset($_POST['value']))) {
    // this page doesn't make sense without POST values
    echo $deco->footer();
    exit(0);
}

if ($_POST['submitbutton'] != web\lib\common\FormElements::BUTTON_SAVE && $_POST['submitbutton'] != web\lib\common\FormElements::BUTTON_CONTINUE) {
    // unexpected button value
    echo $deco->footer();
    exit(0);
}

$inst_name = $my_inst->name;
echo "<h1>" . sprintf(_("Submitted attributes for IdP '%s'"), $inst_name) . "</h1>";
echo "<table>";
echo $optionParser->processSubmittedFields($my_inst, $_POST, $_FILES);
echo "</table>";

// delete cached logo, if present
$dir = ROOT . '/web/downloads/logos/';
array_map('unlink', glob($dir . $my_inst->identifier . "_*.png"));
$loggerInstance->debug(4, "UNLINK from $dir\n");

$loggerInstance->writeAudit($_SESSION['user'], "MOD", "IdP " . $my_inst->identifier . " - attributes changed");

// re-instantiate ourselves... profiles need fresh data

$my_inst = $validator->IdP($_GET['inst_id'], $_SESSION['user']);

// check if we have any SSID at all.

$ssids = [];

if (isset(CONFIG_CONFASSISTANT['CONSORTIUM']['ssid']) && count(CONFIG_CONFASSISTANT['CONSORTIUM']['ssid']) > 0) {
    foreach (CONFIG_CONFASSISTANT['CONSORTIUM']['ssid'] as $ssidname) {
        $ssids[] = $ssidname . " " . (isset(CONFIG_CONFASSISTANT['CONSORTIUM']['tkipsupport']) && CONFIG_CONFASSISTANT['CONSORTIUM']['tkipsupport'] === TRUE ? _("(WPA2/AES and WPA/TKIP)") : _("(WPA2/AES)") );
    }
}

foreach ($my_inst->getAttributes("media:SSID_with_legacy") as $ssidname) {
    $ssids[] = $ssidname['value'] . " " . _("(WPA2/AES and WPA/TKIP)");
}
foreach ($my_inst->getAttributes("media:SSID") as $ssidname) {
    $ssids[] = $ssidname['value'] . " " . _("(WPA2/AES)");
}

echo "<table>";
$uiElements = new web\lib\admin\UIElements();
if (count($ssids) > 0) {
    $printedlist = "";
    foreach ($ssids as $names) {
        $printedlist = $printedlist . "$names ";
    }
    echo $uiElements->boxOkay(sprintf(_("Your installers will configure the following SSIDs: <strong>%s</strong>"), $printedlist), _("SSIDs configured"));
}
$wired_support = $my_inst->getAttributes("media:wired");
if (count($wired_support) > 0) {
    echo $uiElements->boxOkay(sprintf(_("Your installers will configure wired interfaces."), $printedlist), _("Wired configured"));
}
if (count($ssids) == 0 && count($wired_support) == 0) {
    echo $uiElements->boxWarning(_("We cannot generate installers because neither wireless SSIDs nor wired interfaces have been selected as a target!"));
}
echo "</table>";

foreach ($my_inst->listProfiles() as $index => $profile) {
    $profile->prepShowtime();
}
// does federation want us to offer Silver Bullet?
// if so, show both buttons; if not, just the normal EAP profile button
$myfed = new \core\Federation($my_inst->federation);
$allow_sb = $myfed->getAttributes("fed:silverbullet");
// show the new profile jumpstart buttons only if we do not have any profile at all
if (count($my_inst->listProfiles()) == 0) {

    if (CONFIG['FUNCTIONALITY_LOCATIONS']['CONFASSISTANT_SILVERBULLET'] == "LOCAL" && count($allow_sb) > 0) {
        echo "<br/>";
        // did we get an email address? then, show the silverbullet jumpstart button
        // otherwise, issue a smartass comment
        if (count($my_inst->getAttributes("support:email")) > 0) {
            echo "<form method='post' action='edit_silverbullet.php?inst_id=$my_inst->identifier' accept-charset='UTF-8'><button type='submit'>" . sprintf(_("Continue to %s properties"), \core\ProfileSilverbullet::PRODUCTNAME) . "</button></form>";
        } else {
            echo "<table>";
            echo $uiElements->boxError(sprintf(_("You did not submit an e-mail address. This is required for %s. Please go to the %s dashboard and edit your helpdesk settings to include a helpdesk e-mail address."), core\ProfileSilverbullet::PRODUCTNAME, $ui->nomenclature_inst), _("No support e-mail!"));
            echo "</table>";
        }
    }
    if (CONFIG['FUNCTIONALITY_LOCATIONS']['CONFASSISTANT_RADIUS'] == "LOCAL") {
        echo "<br/><form method='post' action='edit_profile.php?inst_id=$my_inst->identifier' accept-charset='UTF-8'><button type='submit'>" . _("Continue to RADIUS/EAP profile definition") . "</button></form>";
    }
}
echo "<br/><form method='post' action='overview_idp.php?inst_id=$my_inst->identifier' accept-charset='UTF-8'><button type='submit'>" . _("Continue to dashboard") . "</button></form>";

echo $deco->footer();
