<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');

// The get widget handler.
function widget_NavigationUser() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("orders");

    $commandestatic=new CommandeFournisseur($db);


    $table =  '<p>';

    $table .= '<table class="notopnoleftnoright" width="100%">';
    $table .= '<tr valign="top"><td class="notopnoleft" width="30%">';

    $table2 =  '<p>';
    $table2 .= '<table class="notopnoleftnoright" width="100%">';
    $table2 .= '<tr valign="top"><td class="notopnoleft" width="30%">';
    if($conf->global->MAIN_MODULE_SYNOPSISHISTO){

        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_Histo_User
                     WHERE user_refid = ".$user->id.
                " AND ref != '' AND element_type != '' ORDER BY tms DESC
                    LIMIT 50";
        $sql = $db->query($requete);
        global $conf, $bc;
        if ($sql)
        {
            $num = $db->num_rows($sql);
            $i = 0;


                $table .= '<table class="liste" width="100%">';
                $table2 .= '<table class="liste" width="100%">';

                $var = true;
                while ($res=$db->fetch_object($sql))
                {

                    $var=!$var;
                    require_once (DOL_DOCUMENT_ROOT."/synopsishisto/class/actions_synopsishisto.class.php");
                    $ret = histoNavigation::histoUser($res);
//                    $table .= print_r($ret,1);
                    if ($i < ($conf->global->SYNOPSIS_HISTO_LENGTH>0?"  ".$conf->global->SYNOPSIS_HISTO_LENGTH:" 5")){
                        $table .=  "<tr $bc[$var]><td>";
                        $table .= $ret;
                        $table .=  "</tr>\n";
                    }
                    $table2 .=  "<tr $bc[$var]><td>";
                    $table2 .= $ret;
                    $table2 .=  "</tr>\n";

                    $i++;
                }
                $table .=  "</table>";
                $table2 .=  "</table>";
                $db->free($sql);
        }


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Navigation - Derniers &eacute;l&eacute;ments visit&eacute;s',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
      );

    } else {
      return array(
        'title' => 'Navigation - Derniers &eacute;l&eacute;ments visit&eacute;s',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_NavigationUser_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}
