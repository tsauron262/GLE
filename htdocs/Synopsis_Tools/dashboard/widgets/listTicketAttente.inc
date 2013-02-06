<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_listTicketAttente() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf;
    $table = '<p><table width="100%" class="noborder"><tbody>';
    $table .= '  <tr class="liste_titre">';
    $table .= '      <td colspan="3">Les 10 derniers tickets non pris</td>';
    $table .= '  </tr>';
    $table .= '</tr>';

    $table2 = '<p><table width="100%" class="noborder"><tbody>';
    $table2 .= '  <tr class="liste_titre">';
    $table2 .= '      <td colspan="3">Les 50 derniers tickets non pris</td>';
    $table2 .= '  </tr>';
    $table2 .= '</tr>';

    $cssClass[true] = 'pair';
    $cssClass[false] = 'impair';
    $bool = false;
    if (isset($conf->MAIN_MODULE_BABELGMAO)) {
        require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/rt.class.php");
        $rt = new rt($db);
        $return = $rt->searchTicket(urlencode("( Status = 'new' )"));
        $prepassArr = array();
        foreach ($return as $key => $val) {
            $date = strtotime($val['Created']);
            $id = $val['ticketId'];
            $priority = $val['Priority'];
            $subject = $val['Subject'];
            $prepassArr[] = array('date' => $date, 'id' => $id, 'priority' => $priority, 'subject' => $subject);
        }
        $iter = 0;
        foreach ($prepassArr as $key => $val) {
            $bool = !$bool;
            $iter++;
            if ($iter <= 10) {
                $table .= '<tr class="' . $cssClass[$bool] . '">';
                $table .= '   </td><td style="overflow: hidden; white-space: nowrap;">';
                $table .= '            ' . $val['Priority'];
                $table .= '   </td><td style="overflow: hidden; white-space: nowrap;">';
                $table .= '       <a href="' . DOL_URL_ROOT . '/Babel_GMAO/SAV/fiche.php?id=' . $val['id'] . '">' . $val['subject'] . '</a>';
                $table .= '   </td><td style="overflow: hidden; white-space: nowrap;">' . date('d/m/Y', $val['date']);
                $table .= '    </td>';
                $table .= '</tr>';
            }
            if ($iter <= 50) {
                $table2 .= '<tr class="' . $cssClass[$bool] . '">';
                $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;">';
                $table2 .= '            ' . $val['Priority'];
                $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;">';
                $table2 .= '       <a href="' . DOL_URL_ROOT . '/Babel_GMAO/SAV/fiche.php?id=' . $val['id'] . '">' . $val['subject'] . '</a>';
                $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;">';
                $table2 .= '            ' . $val['date'];
                $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;">' . date('d/m/Y', $val['date']);
                $table2 .= '    </td>';
                $table2 .= '</tr>';
            }
        }
        $table .= '</tbody></table></p>';
        $table2 .= '</tbody></table></p>';
    }
    else{
        $table = "Modules non actif";
    }


    return array(
        'title' => 'Ticket - Non pris',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
    );
}

// The widget settings handler.
function widget_listTicketAttente_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
