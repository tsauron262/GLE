<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_statOfcPropalStatusAll() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("propal");

    $table ="<p>";
    $table2 ="<p>";

    $jsMainpath = DOL_URL_ROOT."/Babel_Common/js";

    $js = ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';



    $js .= '<script type="text/javascript">';

    $js .= 'swfobject.embedSWF(';
    $js .= '"'.DOL_URL_ROOT.'/Babel_Common/open-flash-chart/open-flash-chart.swf", "propal_status_all_chart", "100%", "350px",';
    $js .= '"9.0.0", "expressInstall.swf",';
    $js .= '{"data-file":"'.DOL_URL_ROOT.'/Synopsis_Tools/dashboard/ajax/stat_propale_status_all_json.php"} );';

    $js .= "var DOL_URL_ROOT='".DOL_URL_ROOT."'";
    $js .= '</script>';


    $table .=  $js;

    $table .=  '    <div id="propal_status_all_chart"></div>';
    $table2 .=  '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="propal_status_all_chart1"></div>';
    $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%><thead>';
    $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Les 20 meilleurs ventes</thead><tbody><tr><td colspan=2>&nbsp</tbody>';

    $requete = "SELECT *
                FROM llx_c_propalst
                WHERE active = 1";
    $presql = $db->query($requete);
    while ($resql = $db->fetch_object($presql))
    {
        $trans = $resql->code;
        if ($trans == $langs->trans($trans))
        $trans = $resql->label;
        $sql = "SELECT *
                FROM llx_propal
                WHERE datep > date_sub(now(),interval 6 month)
                AND fk_statut = ".$resql->id."
            ORDER BY total_ht DESC ";

        $sql .= $db->plimit(20);

        $result=$db->query($sql) ;
        if ($result)
        {
        $num = $db->num_rows($result);
        $i = 0;
        if ($num> 0)
            {
            $table2 .= "<thead><tr><th class='liste_titre' style='color: white;' colspan=4>" .$trans;
            $table2 .= "</thead><tbody>";
        }

        $var=true;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            $table2 .= "<tr $bc[$var]>";
            $table2 .= '<td><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$objp->rowid.'">';
            $table2 .= img_object($langs->trans("Proposal"),"propal");
            $table2 .= " ";
            $table2 .= $objp->ref.'</a></td>';
            $table2 .= '<td>';
            $tmpUser = new User($db);
            $tmpUser->fetch($objp->fk_user_author);
            $table2 .= $tmpUser->getNomUrl(1);
            $table2 .= '</td>';
            $table2 .= '<td>'.$objp->label.'</td>';
            $table2 .= '<td align="right">'.price($objp->total_ht).'&euro;</td>';
            $table2 .= "</tr>\n";
            $i++;
            }
        $db->free();
        }
    }
        $table2 .= "</table>";
        $table2 .= "</table>";

    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Stats - Toutes les propositions com. par status',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/propal_status_all_chart.js',
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/propal_status_all_chart.js',
      );
}

// The widget settings handler.
function widget_statOfcPropalStatusAll_settings() {
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
