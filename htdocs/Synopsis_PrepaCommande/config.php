<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : config.php
  * GLE-1.2
  */
  //choix du chemin
  $GLOBALS['IS_ADMIN']=1;

  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
  $html = new Form($db);
  $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
  $csspath = DOL_URL_ROOT."/Synopsis_Common/css";
  $js  = ' <script src="'.$jspath.'/jquery.checkboxtree.min.js" type="text/javascript"></script>';
  $js  .= ' <script>var DOL_URL_ROOT = "'.DOL_URL_ROOT.'"</script>';
  $js .= ' <link rel="stylesheet" type="text/css" href="'.$csspath.'/jquery.checkboxtree.min.css">';

  $js .= <<<EOF

  <script>
  jQuery(document).ready(function(){
      jQuery('#checkboxTree').checkboxTree({
        collapsable: false,
        collapseEffect: '',
        expandEffect:'',
        collapseImage: DOL_URL_ROOT+'/Synopsis_Common/css/images/downArrow.gif',
        expandImage: DOL_URL_ROOT+'/Synopsis_Common/css/images/rightArrow.gif',
        onUncheck: {
            ancestors: '', //or 'check', 'uncheck'
            descendants: '', //or '', 'check'
            node: '' // or 'collapse', 'expand'
        },
        onCheck: {
            ancestors: '', //or 'check', 'uncheck'
            descendants: 'check', //or '', 'check'
            node: '' // or 'collapse', 'expand'
        }
      });
      jQuery('#checkboxTree2').checkboxTree({
        collapsable: false,
        collapseEffect: '',
        expandEffect:'',
        collapseImage: DOL_URL_ROOT+'/Synopsis_Common/css/images/downArrow.gif',
        expandImage: DOL_URL_ROOT+'/Synopsis_Common/css/images/rightArrow.gif',
        onUncheck: {
            ancestors: '', //or 'check', 'uncheck'
            descendants: 'uncheck', //or '', 'check'
            node: '' // or 'collapse', 'expand'
        },
        onCheck: {
            ancestors: '', //or 'check', 'uncheck'
            descendants: 'check', //or '', 'check'
            node: '' // or 'collapse', 'expand'
        }
      });

      jQuery('#tabs').tabs({
            spinner:"Chargement...",
          cache: true,
          fx: { opacity: "toggle" },
      });
  });
  </script>

EOF;
  llxHeader($js,'Config. pr&eacute;pa. commande');

$msg="";

if ($_REQUEST["action"] == 'setMailGestProd')
{
    dolibarr_set_const($db, "BIMP_MAIL_GESTPROD",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailLogistique')
{
    dolibarr_set_const($db, "BIMP_MAIL_GESTLOGISTIQUE",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'setMailFinance')
{
    dolibarr_set_const($db, "BIMP_MAIL_GESTFINANCIER",$_REQUEST["value"]);
}
if ($_REQUEST["action"] == 'resumeCatList')
{
    $db->begin();
    $requete = "DELETE FROM llx_Synopsis_PrepaCom_c_cat_listContent";
    $sql = $db->query($requete);
    foreach($_REQUEST as $key=>$val)
    {
        if (preg_match('/^cat-[0-9]*$/',$key))
        {
            $requete = "INSERT INTO llx_Synopsis_PrepaCom_c_cat_listContent (catId) VALUES (".$val.")";
            $sql = $db->query($requete);
        }
    }
    $db->commit();
}
//var_dump($_REQUEST);
if ($_REQUEST["action"] == 'totalCatList')
{
    $db->begin();
    $requete = "DELETE FROM llx_Synopsis_PrepaCom_c_cat_total";
    $sql = $db->query($requete);
    foreach($_REQUEST as $key=>$val)
    {
        if (preg_match('/^cat-[0-9]*$/',$key))
        {
            $requete = "INSERT INTO llx_Synopsis_PrepaCom_c_cat_total (catId) VALUES (".$val.")";
            $sql = $db->query($requete);
        }
    }
    $db->commit();
}

  print "<div class='titre'>Configuration des emails</div>";
  print "<br/>";
  print "<div id='tabs'>";
  print "<ul>";
  print '<li><a href="#tabs-1">Email</a></li>';
  print '<li><a href="#tabs-2">Cat&eacute;gorie dans le r&eacute;sum&eacute;</a></li>';
  print '<li><a href="#tabs-3">Cat&eacute;gorie dans le total</a></li>';
  print "\n".'</ul>';

  print "<div id='tabs-3'>";
  print "<form id='formCat2' action='config.php' method='POST'>";
  print "<input type='hidden' name='action' value='totalCatList'>";
  //Liste les catégories en arbre avec 3 check box :> 1 Afficher le détail 2 Dans le total
  //
  require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
  $cat = new categorie($db);
  $requete = "SELECT catId FROM llx_Synopsis_PrepaCom_c_cat_total";
  $sql = $db->query($requete);
  $arrCat=array();
  while ($res = $db->fetch_object($sql))
  {
      $arrCat[$res->catId] = $res->catId;
  }
  $cate_arbo = $cat->get_full_arbo(0);
  if (sizeof($cate_arbo))
  {
//        require_once(DOL_DOCUMENT_ROOT.'/includes/treemenu/TreeMenu.php');
        // Ajoute id_mere sur tableau cate_arbo
        $i=0;
        print "<ul id='checkboxTree2' class='checkboxTree'>";
        foreach ($cate_arbo as $key => $val)
        {
            $i++;
            if ($val['id_mere']."x"=='x')
            {
                print "<li><input type='checkbox' ".(in_array($val['id'],$arrCat)?'checked':'')." name='cat-".$val['id']."' value='".$val['id']."'>".$val['label']."\n";
                print "<ul>\n";
                if (count($val['id_children']) > 0)
                {
                    foreach($val['id_children'] as $key1=>$val1)
                    {
                        //$table .= print_r($cate_arbo,true);
                        $tmpId = false;
                        foreach($cate_arbo as $key2=>$val2)
                        {
                            if ($val2['id'] == $val1)
                            {
                                $tmpId = $key2;
                                break;
                            }
                        }
                        if ($tmpId  && count($cate_arbo[$tmpId]['id_children']) > 0)
                        {
                            print recurse_tree($val1,$cate_arbo,$arrCat);
                        }
                    }
                }
                print "\n</ul>";
            }
        }
    } else {
        print $langs->trans("NoneCategory");
    }

  //require_once('Var_Dump.php');
  //Var_Dump::Display($cat);

  print "</form>";
  print "<button id='validateButton2' class='butAction'>Valider</button>";
  print "</div>";

  $requete = "SELECT catId FROM llx_Synopsis_PrepaCom_c_cat_listContent";
  $sql = $db->query($requete);
  $arrCat=array();
  while ($res = $db->fetch_object($sql))
  {
      $arrCat[$res->catId] = $res->catId;
  }

  print "<div id='tabs-2'>";
  print "<form id='formCat1' action='config.php' method='post'>";
  print "<input type='hidden' name='action' value='resumeCatList'>";
  //Liste les catégories en arbre avec 3 check box :> 1 Afficher le détail 2 Dans le total
  //
  require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
  $cat = new categorie($db);
  $cate_arbo = $cat->get_full_arbo(0);
    if (sizeof($cate_arbo))
    {
//        require_once(DOL_DOCUMENT_ROOT.'/includes/treemenu/TreeMenu.php');
        // Ajoute id_mere sur tableau cate_arbo
        $i=0;
        print "<ul id='checkboxTree' class='checkboxTree'>";
        foreach ($cate_arbo as $key => $val)
        {
            $i++;
            if ($val['id_mere']."x"=='x')
            {
                print "<li><input type='checkbox' name='cat-".$val['id']."' value='".$val['id']."'>".$val['label']."\n";
                print "<ul>\n";
                if (count($val['id_children']) > 0)
                {
                    foreach($val['id_children'] as $key1=>$val1)
                    {
                        //$table .= print_r($cate_arbo,true);
                        $tmpId = false;
                        foreach($cate_arbo as $key2=>$val2)
                        {
                            if ($val2['id'] == $val1)
                            {
                                $tmpId = $key2;
                                break;
                            }
                        }
                        if ($tmpId  && count($cate_arbo[$tmpId]['id_children']) > 0)
                        {
                            print recurse_tree($val1,$cate_arbo,$arrCat);
                        }
                    }
                }
                print "\n</ul>";
            }
        }
    } else {
        print $langs->trans("NoneCategory");
    }

  //require_once('Var_Dump.php');
  //Var_Dump::Display($cat);
  print "<button id='validateButton' class='butAction'>Valider</button>";

  print "</form>";
  print "</div>";
  print "<div id='tabs-1'>";
  print "<form method='POST' action='".$_SERVER['PHP_SELF']."'>";
  print "<input type='hidden' name='action' value='setMailFrom'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Emetteur";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_FROM."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";


  print "<form method='POST' action='".$_SERVER['PHP_SELF']."'>";
  print "<input type='hidden' name='action' value='setMailGestProd'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Email - Gestionnaire production";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_GESTPROD."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";


  print "<form method='POST' action='".$_SERVER['PHP_SELF']."'>";
  print "<input type='hidden' name='action' value='setMailLogistique'>";
  print "<table cellpadding=15 >";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Email - Gestionnaire logistique";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_GESTLOGISTIQUE."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "<form method='POST' action='".$_SERVER['PHP_SELF']."'>";
  print "<input type='hidden' name='action' value='setMailFinance'>";
  print "<table cellpadding=15>";
  print "<tr><th width=200 style='border:1px Solid' class='ui-widget-header ui-state-default'>Email - Gestionnaire financier";
  print "<td width=320 class='ui-widget-content'><input size=30 name='value' value='".$conf->global->BIMP_MAIL_GESTFINANCIER."'>";
  print "<td width=130 class='ui-widget-content'><button class='butAction'>Modifier</button>";
  print "</table>";
  print "</form>";

  print "</div>";
  print "</div>";

  print <<<EOF
  <script>
  </script>

EOF;

function recurse_tree($idChild,$cate_arbo,$arrCat){
    $html = "";
    foreach($cate_arbo as $key=>$val)
    {
        if ($val['id'] == $idChild)
        {
            $html .= "<li><input ".(in_array($val['id'],$arrCat)?'checked':'')." value='".$val['id']."'  name='cat-".$val['id']."' type='checkbox'>".$val['label']."\n";
            $html .= "<ul>\n";
            //$html.= " (".count($val['id_children']).")";
            if (count($val['id_children']) > 0)
            {
                foreach($val['id_children'] as $key1=>$val1)
                {
                    //$table .= print_r($cate_arbo,true);
                    $tmpId = false;
                    foreach($cate_arbo as $key2=>$val2)
                    {
                        if ($val2['id'] == $val1)
                        {
                            $tmpId = $key2;
                            break;
                        }
                    }
                    if ($tmpId  && count($cate_arbo[$tmpId]['id_children']) > 0)
                    {
//                        $html .= "<li>";
                        $html .= recurse_tree($val1,$cate_arbo,$arrCat);
  //                      $html .= "</li>";
                    } else {
                        //$html .= print_r($val,true);
                        $html .= "<li><input ".(in_array($cate_arbo[$tmpId]['id'],$arrCat)?'checked':'')." value='".$cate_arbo[$tmpId]['id']."'  name='cat-".$cate_arbo[$tmpId]['id']."' type='checkbox'>".$cate_arbo[$tmpId]['label']."\n";
                    }
                }
            }
            $html .= "\n</ul>";
        }
    }
    return ($html);
}

llxFooter();

?>
