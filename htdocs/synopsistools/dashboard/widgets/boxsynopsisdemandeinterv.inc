<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

//require_once(DOL_DOCUMENT_ROOT."/boxes.php");
// The get widget handler.
function widget_boxsynopsisdemandeinterv() {
    global $langs, $user;
    $langs->load("boxes");
    $tabValue = getValSetting();
    if (in_array(1, $tabValue))
        $_REQUEST['filtreUser'] = $user->id;
    if (in_array(11, $tabValue))
        $_REQUEST['lien_commande'] = true;
    if (in_array(12, $tabValue))
        $_REQUEST['lien_contrat'] = true;
//        $_REQUEST['next'] = true;
    return boxToWidget("box_synopsisdemandeinterv.php", "DI - " . $langs->trans("BoxLastsynopsisdemandeinterv"), true);
    
}

// The widget settings handler.
//function widget_boxsynopsisdemandeinterv_settings() {
//    return(array('markup' => '<div>Aucune option pour ce module</div>'));
//}
//// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
function getValSetting() {
    global $db, $user;
    $type = "boxsynopsisdemandeinterv";
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget WHERE module = '" . $type . "'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $typeId = $res->id;
    $c = array();
    $requete = "SELECT * FROM  " . MAIN_DB_PREFIX . "Synopsis_Dashboard_settings WHERE module_id = " . $typeId . " AND user_id = " . $user->id;
    $sql = $db->query($requete);
    if ($sql && $db->num_rows($sql) > 0) {
        $res = $db->fetch_object($sql);
        $c = $res->value;
        $c = explode(',', $c);
    }
    return $c;
}

function widget_boxsynopsisdemandeinterv_settings() {
    // Get the saved settings.
    global $user, $db;

    $c = "";
    if (isset($GLOBALS['_POST']['settings']))
        foreach ($GLOBALS['_POST']['settings'] as $val)
            $c .= $val . ",";
//    $c = $GLOBALS['_POST']['settings'] ? $GLOBALS['_POST']['settings']['filter'] : false;
    $type = "boxsynopsisdemandeinterv";
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget WHERE module = '" . $type . "'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $typeId = $res->id;

    if ($c) {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard_settings WHERE module_id = " . $typeId . " AND user_id = " . $user->id;
        $sql = $db->query($requete);
        $c = explode(',', $c);
        $c1 = array();
        foreach ($c as $key => $val) {
            if ($val != 'undefined')
                $c1[] = $val;
        }
        $c = join(',', $c1);
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Dashboard_settings (module_id,user_id,value) VALUES (" . $typeId . "," . $user->id . ",'" . $c . "')";
        $sql = $db->query($requete);
        $c = explode(',', $c);
    } else {
        $c = array();
        $requete = "SELECT * FROM  " . MAIN_DB_PREFIX . "Synopsis_Dashboard_settings WHERE module_id = " . $typeId . " AND user_id = " . $user->id;
        $sql = $db->query($requete);
        if ($sql) {
            $res = $db->fetch_object($sql);
            $c = $res->value;
            $c = explode(',', $c);
        }
    }
    // A widget settings object as per jquery.dashboard.js.
    $html = "";
    $html .= "<SELECT name='filter'>";
    if (in_array(0, $c))
        $html .= "<OPTION SELECTED value='0'>Toutes les DI</OPTION>";
    else
        $html .= "<OPTION value='0'>Toutes les DI</OPTION>";
    if (in_array(1, $c))
        $html .= "<OPTION SELECTED value='1'>Mes DI</OPTION>";
    else
        $html .= "<OPTION value='1'>Mes DI</OPTION>";
    $html .= "</SELECT>";
    $html .= "<SELECT name='filter2'>";
    if (in_array(10, $c))
        $html .= "<OPTION SELECTED value='10'>Toutes les DI</OPTION>";
    else
        $html .= "<OPTION value='10'>Toutes les DI</OPTION>";
    if (in_array(11, $c))
        $html .= "<OPTION SELECTED value='11'>Liée a une commande</OPTION>";
    else
        $html .= "<OPTION value='11'>Liée a une commande</OPTION>";
    if (in_array(12, $c))
        $html .= "<OPTION SELECTED value='12'>Liée a un contrat</OPTION>";
    else
        $html .= "<OPTION value='12'>Liée a un contrat</OPTION>";
    $html .= "</SELECT>";
    return array(
        // Change the settings a bit so we can see that the new settings have been loaded client-side.
        'markup' => '<div>' . $html . '</div>',
    );
}