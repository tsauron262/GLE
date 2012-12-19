<?php
/*
  * GLE by Synopsis
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 2 mars 2011
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
 /**
  *
  * Name : widget-modele.php
  * GLE-1.2
  */

require_once("../../main.inc.php");
//require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/Babel_Chrono/Chrono.class.php');


// The get widget handler.
function widget_listChronoModele1() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("chrono");
    $model = 1;

    $chrono=new Chrono($db);
    $chrono->model_refid = $model;
    $chrono->getGlobalRights();
    $table =  '<p>';
    $table2 = '</p>';
   $model = 1;

//    $table .= print_r($user->rights,1);
    if ($conf->global->MAIN_MODULE_BABELCHRONO && ($user->rights->Chrono->Global->Afficher || $user->rights->chrono_user->chrono1->voir))
    {
        $requete = "SELECT value
                      FROM ".MAIN_DB_PREFIX."Synopsis_dashboard_settings,".MAIN_DB_PREFIX."Synopsis_dashboard_widget
                     WHERE module_id = ".MAIN_DB_PREFIX."Synopsis_dashboard_widget.id
                       AND ".MAIN_DB_PREFIX."Synopsis_dashboard_widget.module = 'listChronoModele1'
                       AND user_id = ".$user->id;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);

        $sql = " SELECT * FROM Babel_Chrono as p
                  WHERE fk_statut IN (".$res->value.")
                                         AND model_refid = ".$model;
        $sql .= " ORDER BY p.date_create DESC LIMIT 50";

//$table .= $sql;
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
                $table .= "<td colspan=\"4\">".$langs->trans("Chronos")."</td>";
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"1\">".$langs->trans("Ref")."</td>";
                $table .= "<td colspan=\"1\">".$langs->trans("Cr&eacute;er le")."</td>";
                $table .= "<td colspan=\"1\">".$langs->trans("Modifier le")."</td>";
                $table .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"4\">".$langs->trans("Chronos")."</td>";
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"1\">".$langs->trans("Ref")."</td>";
                $table2 .= "<td colspan=\"1\">".$langs->trans("Cr&eacute;er le")."</td>";
                $table2 .= "<td colspan=\"1\">".$langs->trans("Modifier le")."</td>";
                $table2 .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {

                    $chrono->fetch($res->id);

                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">';
                            $table .= $chrono->getNomUrl(1,5);

                            $table .=  '<td nowrap="nowrap">'.date('d/m/Y',$chrono->date).'</td>';
                            $table .=  '<td nowrap="nowrap">'.date('d/m/Y',$chrono->date_modif).'</td>';
                            $table .=  '<td>'.$chrono->getLibStatut(3).'</td>';
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $table2 .= $chrono->getNomUrl(1,5);

                    $table2 .=  '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$res->socid.'">'.img_object('','societe')." ".dolibarr_trunc($res->nom,18).'</a></td>';
                    $table2 .=  '<td>'.$res->Blabel.'</td>';
                    $iter++;
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Chrono - Derniers chronos (test)',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => true,
        'reload' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Chrono - Derniers chronos (test)',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listChronoModele1_settings() {
  // Get the saved settings.
  global $user,$db;
  $c = $GLOBALS['_POST']['settings'] ? $GLOBALS['_POST']['settings']['filter'] : false;
  $type = "listChronoModele1";
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_dashboard_widget WHERE module = '".$type."'";
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $typeId = $res->id;

  if ($c)
  {
      $requete= "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_dashboard_settings WHERE module_id = ".$typeId." AND user_id = ".$user->id;
      $sql = $db->query($requete);
      $c = explode(',',$c);
      $c1 = array();
      foreach($c as $key=>$val)
      {
        if($val != 'undefined')
        $c1[] = $val;
      }
      $c = join(',',$c1);
      $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_dashboard_settings (module_id,user_id,value) VALUES (".$typeId.",".$user->id.",'".$c."')";
      $sql = $db->query($requete);
      $c = explode(',',$c);

  } else {
    $c = array();
    $requete = "SELECT * FROM  ".MAIN_DB_PREFIX."Synopsis_dashboard_settings WHERE module_id = ".$typeId. " AND user_id = ".$user->id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $res = $db->fetch_object($sql);
        $c = $res->value;
        $c = explode(',',$c);
    }
  }
  // A widget settings object as per jquery.dashboard.js.
  $html = "";
  $html .= "<SELECT name='filter' MULTIPLE=1 SIZE=5>";
  if(in_array(0,$c))
    $html .= "<OPTION SELECTED value='0'>Brouillon</OPTION>";
  else
    $html .= "<OPTION value='0'>Brouillon</OPTION>";
  if(in_array(2,$c))
    $html .= "<OPTION SELECTED value='2'>Valid&eacute;</OPTION>";
  else
    $html .= "<OPTION value='2'>Valid&eacute;</OPTION>";
  if(in_array(3,$c))
    $html .= "<OPTION SELECTED value='3'>R&eacute;vis&eacute;</OPTION>";
  else
    $html .= "<OPTION value='3'>R&eacute;vis&eacute;</OPTION>";
  if(in_array(4,$c))
    $html .= "<OPTION SELECTED value='4'>Cl&ocirc;tur&eacute;</OPTION>";
  else
    $html .= "<OPTION value='4'>Cl&ocirc;tur&eacute;</OPTION>";
  if(in_array(999,$c))
    $html .= "<OPTION SELECTED value='999'>Dem. Validation</OPTION>";
  else
    $html .= "<OPTION value='999'>Dem. Validation</OPTION>";
  $html .= "</SELECT>";
  return array(
    // Change the settings a bit so we can see that the new settings have been loaded client-side.
    'markup' => '<div>'.$html.'</div>',
  );

//    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}





?>
