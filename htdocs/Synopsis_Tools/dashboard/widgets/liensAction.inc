<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_liensAction() {
  // A widget object as per jquery.dashboard.js.
    global $db,$bc,$langs;
    $langs->load("companies");
    $langs->load("commercial");
    $langs->load("agenda");

    $table = '<p><div class="ui-widget-content">';
    $var=true;
    $table .= '<form method="post" action="'.DOL_URL_ROOT.'/comm/action/listactions.php">';
    $table .= '<table class="noborder" width="100%">';
    $table .= '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ViewWithPredefinedFilters").'</td></tr>';
    // All actions of everybody
    $var=!$var;
    $table .= '<tr '.$bc[$var].'><td>';
    $table .= $langs->trans("AllActions").'</td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php">';
    $table .= img_picto($langs->trans("ViewList"),'object_list').'</a></td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/index.php">';
    $table .= img_picto($langs->trans("ViewCal"),'object_calendar').'</a></td>';
    $table .= '</tr>';
    // All my actions
    $var=!$var;
    $table .= '<tr '.$bc[$var].'><td>';
    $table .= $langs->trans("AllMyActions").'</td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?filter=mine">';
    $table .= img_picto($langs->trans("ViewList"),'object_list').'</a></td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/index.php?filter=mine">';
    $table .= img_picto($langs->trans("ViewCal"),'object_calendar').'</a></td>';
    $table .= '</tr>';
    // Actions i asked
    $var=!$var;
    $table .= '<tr '.$bc[$var].'><td> &nbsp; &nbsp; &nbsp; ';
    $table .= $langs->trans("MyActionsAsked").'</td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?filtera='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewList"),'object_list').'</a></td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/index.php?filtera='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewCal"),'object_calendar').'</a></td>';
    $table .= '</tr>';
    // Actions affected to me
    $var=!$var;
    $table .= '<tr '.$bc[$var].'><td> &nbsp; &nbsp; &nbsp; ';
    $table .= $langs->trans("MyActionsToDo").'</td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?filtert='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewList"),'object_list').'</a></td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/index.php?filtert='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewCal"),'object_calendar').'</a></td>';
    $table .= '</tr>';
    // Actions done by me
    $var=!$var;
    $table .= '<tr '.$bc[$var].'><td> &nbsp; &nbsp; &nbsp; ';
    $table .= $langs->trans("MyActionsDone").'</td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?filterd='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewList"),'object_list').'</a></td>';
    $table .= '<td><a href="'.DOL_URL_ROOT.'/comm/action/index.php?filterd='.$user->id.'">';
    $table .= img_picto($langs->trans("ViewCal"),'object_calendar').'</a></td>';
    $table .= '</tr>';
    $table .= "</table></form><br>\n";
  $table .="</div></p>";

  return array(
    'title' => 'Liens Actions',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_liensAffaire_settings() {
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
