<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listSocToContact() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $langs->load("companies");
    $langs->load("commercial");

    $table = '<p>';
    $table2 = '<p>';
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    if ($user->rights->societe->lire)
    {

        $sql = "SELECT s.nom, s.rowid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql .= " FROM llx_societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", llx_societe_commerciaux as sc";
        $sql .= " WHERE s.fk_stcomm = 1";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        $sql .= " ORDER BY s.tms DESC";

        $sql .= " LIMIT 50";
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"3\">".$langs->trans("ProspectToContact")."</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"3\">".$langs->trans("ProspectToContact")."</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($obj =$db->fetch_object($resql))
                {
                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td colspan=3  nowrap="nowrap">'.
                                    img_object($langs->trans("ShowCompany"),"company") .
                                    '<a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->rowid.'"> '.$obj->nom;
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                                   <td  colspan=3 nowrap="nowrap">'.
                                         img_object($langs->trans("ShowCompany"),"company") .
                                    '<a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->rowid.'"> '.$obj->nom;
                    $iter++;
                }
                $table .=  "</table><br>";
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";

      return array(
        'title' => 'Prospects - &Agrave; contacter',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );
    } else {
      return array(
        'title' => 'Prospects - &Agrave; contacter',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }
}

// The widget settings handler.
function widget_listSocToContact_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}
