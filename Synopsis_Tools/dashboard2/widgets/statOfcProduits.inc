<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_statOfcProduits() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }

    $langs->load("products");

    $table ="<p>";
    $table2 ="<p>";

    $jsMainpath = DOL_URL_ROOT."/Babel_Common/js";

    $js = ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';



    $js .= '<script type="text/javascript">';

    $js .= 'swfobject.embedSWF(';
    $js .= '"'.DOL_URL_ROOT.'/Babel_Common/open-flash-chart/open-flash-chart.swf", "products_chart", "100%", "350px",';
    $js .= '"9.0.0", "expressInstall.swf",';
    $js .= '{"data-file":"'.DOL_URL_ROOT.'/Synopsis_Tools/dashboard/ajax/stat_produit_json.php"} );';

    $js .= "var DOL_URL_ROOT='".DOL_URL_ROOT."'";
    $js .= '</script>';


    $table .=  $js;

    $table .=  '    <div id="products_chart"></div>';
    $table2 .=  '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="products_chart1"></div>';
    $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%>';
    $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Les 20 meilleurs ventes';


    $sql  = "SELECT p.rowid, p.label, p.ref, fk_product_type, count(*) as c";
    $sql .= " FROM llx_propaldet as pd, llx_propal as pl, babel_product as p";
    if ($conf->categorie->enabled && !$user->rights->categorie->voir)
    {
      $sql.= " LEFT JOIN llx_categorie_product as cp ON cp.fk_product = p.rowid";
      $sql.= " LEFT JOIN llx_categorie as c ON cp.fk_categorie = c.rowid";
    }
    $sql .= " WHERE p.rowid = pd.fk_product";
    if ($conf->categorie->enabled && !$user->rights->categorie->voir)
    {
      $sql.= ' AND IFNULL(c.visible,1)=1';
    }
    $sql.= ' AND pl.rowid = pd.fk_propal';
    $sql.= ' AND pl.datep > date_sub(now(), interval 12 month)';
    $sql.= 'AND pl.fk_statut in (2,4)';
    $sql .= " GROUP BY (p.rowid)";
    $sql .= " ORDER BY c DESC ";
//    $table.= $sql;
    $sql .= $db->plimit(20);

    $result=$db->query($sql) ;
    if ($result)
    {
      $num = $db->num_rows($result);
      $i = 0;

      $var=true;
      while ($i < $num)
      {
          $objp = $db->fetch_object($result);
          $var=!$var;
          $table2 .= "<tr $bc[$var]>";
          $table2 .= '<td><a href="'.DOL_URL_ROOT.'/product/stats/fiche.php?id='.$objp->rowid.'">';
        if ($objp->fk_product_type==1) $table2 .= img_object($langs->trans("ShowService"),"service");
        else $table2 .= img_object($langs->trans("ShowProduct"),"product");
          $table2 .= " ";
          $table2 .= $objp->ref.'</a></td>';
          $table2 .= '<td>';
          if ($objp->type==1) $table2 .= $langs->trans("ShowService");
          else $table2 .= $langs->trans("ShowProduct");
          $table2 .= '</td>';
          $table2 .= '<td>'.$objp->label.'</td>';
          $table2 .= '<td align="right">'.$objp->c.'</td>';
          $table2 .= "</tr>\n";
          $i++;
        }
      $db->free();
    }
    $table2 .= "</table>";
    $table2 .= "</table>";


    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Stats - 5 meilleures ventes produits/services',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/products_chart.js',
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/products_chart.js',
      );
}

// The widget settings handler.
function widget_statOfcProduits_settings() {
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
