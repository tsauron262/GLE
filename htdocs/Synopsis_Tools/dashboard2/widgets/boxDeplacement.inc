<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/boxes.php");

// The get widget handler.
function widget_boxDeplacement() {
  // A widget object as per jquery.dashboard.js.
    global $db,$user,$langs;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load('commercial');
    $langs->load("boxes");

    $file ="box_deplacement.php";
//    $infobox=new InfoBox($db);
//    $boxarray=$infobox->listboxes("0",$user);       // 0=valeur pour la page accueil
    require_once(DOL_DOCUMENT_ROOT.'/includes/boxes/'.$file);
    $nameShort =preg_replace('/.php$/','',$file);
    $box=new $nameShort($db);

    $table = "<p>";
    $box->loadBox(10);
    $table .= $box->showBox(false);
    $table .= "</p>";

    $table2 = "<p>";
    $box->loadBox(50);
    $table2 .= $box->showBox(false);
    $table2 .= "</p>";


      return array(
        'title' => "NDF - ".$langs->trans("BoxLastdeplacement"),
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );
}

// The widget settings handler.
function widget_boxDeplacement_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

//// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
