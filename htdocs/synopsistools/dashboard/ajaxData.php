<?php

/**
 * @file
 *    Example server-side implementation for the jQuery.dashboard() plugin.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once('../../main.inc.php');
$type = $_REQUEST['type'];

// Handles all requests.
function execute() {
    // Simulate slow network connections.
    //sleep(rand(10, 50) * 0.05);
    // Find the operation of this request.
    $op = isset($GLOBALS['_GET']['op']) ? $GLOBALS['_GET']['op'] : (isset($GLOBALS['_POST']['op']) ? $GLOBALS['_POST']['op'] : false);

    // Call this operation's handler.
    if ($op and $ret = call_function_if_exists("execute_op_$op")) {
        print_r($ret);
        print to_js($ret);
    }
}

// Calls a function if it exists and returns the function's return value.
function call_function_if_exists($func) {
    if (function_exists($func)) {
        return $func();
    } else {
        return "Function '$func' is not defined.";
    }
}

// Taken from http://api.drupal.org/api/function/drupal_to_js/7 (GPL 2)
function to_js($var) {
    // json_encode() does not escape <, > and &, so we do it with str_replace()
    return @json_encode($var);
}

// Finds the id parameter and includes the respective widget file.
function include_widget_file() {
    $id = $GLOBALS['_GET']['id'] ? $GLOBALS['_GET']['id'] : ($GLOBALS['_POST']['id'] ? $GLOBALS['_POST']['id'] : '');

    // IDs client-side use hyphen separators.  Server-side they use underscores.
    $id = str_replace('-', '_', $id);

    $filename = DOL_DOCUMENT_ROOT . "/synopsistools/dashboard/widgets/$id.inc";
    if (file_exists($filename)) {
        include $filename;
        return $id;
    } else {
        return "You need to create a widget file called '$filename'.";
    }
}

// Operation handler for get_widgets_by_column operation.
function execute_op_get_widgets_by_column() {

    $widgets = array();
    global $db, $user, $type;
    $requete = "SELECT *
                FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
               WHERE user_refid=" . $user->id . "
                 AND dash_type_refid ='" . $type . "'";

    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    if ($res->params . "x" != "x") {
        $widgets = unserialize($res->params);
    } else {
        $requete = "SELECT *
                    FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                   WHERE user_refid is null
                     AND dash_type_refid ='" . $type . "'";
        //print $requete;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $widgets = unserialize($res->params);
    }
    return $widgets;
}

// Operation handler for get_widget operation.
function execute_op_get_widget() {
    $id = include_widget_file();
    return call_function_if_exists("widget_$id");
}

// Operation handler for save_columns operation.
function execute_op_save_columns() {
    $nonNul = false;
    $cols = $GLOBALS['_POST']['columns'];
    // Parse out strings "1" and "0" as ints/booleans.
    if (is_array($cols)) {
        foreach ($cols as $c => $widgets) {
            foreach ($widgets as $wid => $is_minimized) {
                $nonNul = true;
                $cols[$c][$wid] = (int) $is_minimized;
            }
        }
        global $db, $user, $type;
        //Store to DB
        // Par userId
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                       WHERE user_refid = " . $user->id . "
                         AND dash_type_refid ='" . $type . "'";
        $sql = $db->query($requete);
        if ($nonNul) {
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                      (params,user_refid,dash_type_refid)
               VALUES ('" . serialize($cols) . "'," . $user->id . ",'" . $type . "')";
            $sql = $db->query($requete);
        }
        return "OK";
    }
}

// Operation handler for widget_settings operation.
function execute_op_widget_settings() {
    $id = include_widget_file();
    return call_function_if_exists("widget_{$id}_settings");
}

execute();
?>