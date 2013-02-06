<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/agenda.lib.php");

// The get widget handler.
function widget_listActionTODO() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("commercial");

    if ($user->rights->agenda->myactions->read)
    {
        ob_start();
        $table = "<p>".utf8_encode(show_array_actions_to_do(10,false))."</p>";
        $table2 = "<p>".utf8_encode(show_array_actions_to_do(50,false))."</p>";
        ob_clean();
        
          return array(
            'title' => 'Action Com. &agrave; faire - 10 derni&egrave;res actions',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
            'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
          );

    }else {
          return array(
            'title' => 'Action Com. &agrave; faire - 10 derni&egrave;res actions',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
          );
    }
}

// The widget settings handler.
function widget_listActionTODO_settings() {
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
