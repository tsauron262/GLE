<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 3 avr. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ficheNdf.php
  * dolibarr-24dev
  */

  //livegrid => list des reglements pour 1 mois
  // bouton statut
  // bouton ajoute sur la periode


require_once('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/deplacement.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
$langs->load("synopsisGene@Synopsis_Tools");

$id =$_REQUEST['id'];

//if (!isset ($_SESSION)) session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<?php

if ($user->rights->TechPeople->ndf->Affiche != 1 && $user->rights->TechPeople->ndf->AfficheMien  != 1 && $user->rights->TechPeople->ndf->Valid != 1)
{
    print  $user->rights->TechPeople->ndf->AfficheMien ;
    print "<div class='ui-state-error'>Ce module ne vous est pas accessible</div>";
    exit(0);
}

if ($_REQUEST['action']=='generatePdf')
{
    require_once(DOL_DOCUMENT_ROOT."/core/modules/deplacement/modules_deplacement.php");
    $ndf = new Ndf($db);
    $ndf->fetch($_REQUEST['id']);
    deplacement_pdf_create($db, $ndf->id, 'Babel');
}
//<html>
//<head>
//<title>GLE - Note de frais</title>
$js ='<script src="'.DOL_URL_ROOT.'/Synopsis_Common/js/rico2/src/prototype.js" type="text/javascript"></script>
<script src="'.DOL_URL_ROOT.'/Synopsis_Common/js/rico2/src/rico.js" type="text/javascript"></script>';
//print $js;

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes')
{
    require_once('deplacement.class.php');
    $ndf = new Ndf($db);
    $ndf->fk_user_valid = $user->id;
    $ndf->fetch($_REQUEST['id']);
    $ndf->setProcessing();
    header('Location:ficheNdf.php?id='.$_REQUEST['id']);
    exit(0);
}
if ($_REQUEST['action'] == 'confirm_validateNdf' && $_REQUEST['confirm'] == 'yes')
{
    require_once('deplacement.class.php');
    $ndf = new Ndf($db);
    $ndf->fetch($_REQUEST['id']);
    $ndf->fk_user_valid = $user->id;
    $ndf->setValidate();
    header('Location:ficheNdf.php?id='.$_REQUEST['id']);
    exit(0);
}
if ($_REQUEST['action'] == 'confirm_refuseNdf' && $_REQUEST['confirm'] == 'yes')
{
    require_once('deplacement.class.php');
    $ndf = new Ndf($db);
    $ndf->fetch($_REQUEST['id']);
    $ndf->fk_user_valid = $user->id;
    $ndf->setRefused();
    header('Location:ficheNdf.php?id='.$_REQUEST['id']);
    exit(0);
}
  llxHeader($js);
  print_fiche_titre($langs->trans("D&eacute;tails ndf"));
  require_once('deplacement.class.php');
  $ndf = new Ndf($db);
  $ndf->fetch($_REQUEST['id']);

  $head = ndf_prepare_head();
  dol_fiche_head($head, "Mois", $langs->trans("Ndf"));

$periode = $ndf->periode;

$sqltext= "";
if ($user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->Valid)
{

$sqltext="select ".MAIN_DB_PREFIX."deplacement.rowid as CustomerID,
                 ".MAIN_DB_PREFIX."deplacement.dated  as mr,
                 ".MAIN_DB_PREFIX."societe.nom  as socname,
                 ".MAIN_DB_PREFIX."deplacement.lieu  as lieu,
                 ".MAIN_DB_PREFIX."c_deplacement.code as st,
                 km as km,
                 prix_ht as total,
                 (tva_taux/100) as tva,
                 ".MAIN_DB_PREFIX."deplacement.note  as dnote,
                 ".MAIN_DB_PREFIX."societe.rowid  as socid
            FROM ".MAIN_DB_PREFIX."deplacement, ".MAIN_DB_PREFIX."societe, ".MAIN_DB_PREFIX."c_deplacement, Babel_ndf
           WHERE ".MAIN_DB_PREFIX."deplacement.fk_soc = ".MAIN_DB_PREFIX."societe.rowid
             AND ".MAIN_DB_PREFIX."c_deplacement.id = ".MAIN_DB_PREFIX."deplacement.type_refid
             AND ".MAIN_DB_PREFIX."deplacement.dated > '".$periode."'
             AND Babel_ndf.id = ".$id."
             AND Babel_ndf.fk_user_author = ".$ndf->fk_user_author."
             AND ".MAIN_DB_PREFIX."deplacement.fk_user_author = ".$ndf->fk_user_author."
             AND ".MAIN_DB_PREFIX."deplacement.dated < date_add('".$periode."',INTERVAL 1 MONTH )
        group by ".MAIN_DB_PREFIX."deplacement.rowid
        order by dated DESC";

} else  if ($user->rights->TechPeople->ndf->AfficheMien)
{
$sqltext="select ".MAIN_DB_PREFIX."deplacement.rowid as CustomerID,
                 ".MAIN_DB_PREFIX."deplacement.dated  as mr,
                 ".MAIN_DB_PREFIX."societe.nom  as socname,
                 ".MAIN_DB_PREFIX."deplacement.lieu  as lieu,
                 ".MAIN_DB_PREFIX."c_deplacement.code as st,
                 km as km,
                 prix_ht as total,
                 (tva_taux/100) as tva,
                 ".MAIN_DB_PREFIX."deplacement.note  as dnote,
                 ".MAIN_DB_PREFIX."societe.rowid  as socid
            FROM ".MAIN_DB_PREFIX."deplacement, ".MAIN_DB_PREFIX."societe, ".MAIN_DB_PREFIX."c_deplacement, Babel_ndf
           WHERE ".MAIN_DB_PREFIX."deplacement.fk_soc = ".MAIN_DB_PREFIX."societe.rowid
             AND ".MAIN_DB_PREFIX."c_deplacement.id = ".MAIN_DB_PREFIX."deplacement.type_refid
             AND ".MAIN_DB_PREFIX."deplacement.dated > '".$periode."'
             AND Babel_ndf.id = ".$id."
             AND Babel_ndf.fk_user_author = ".$user->id."
             AND ".MAIN_DB_PREFIX."deplacement.fk_user_author = ".$user->id."
             AND ".MAIN_DB_PREFIX."deplacement.dated < date_add('".$periode."',INTERVAL 1 MONTH )
        group by ".MAIN_DB_PREFIX."deplacement.rowid
        order by dated DESC";
} else {
    exit ;
}


//print $sqltext;


require_once "applib.php";

OpenDB();

$_SESSION['GLE_NdfGrid']=$sqltext;
CloseApp();

require "chklang2.php";
require "settings.php";
?>

<script type='text/javascript'>
Rico.loadModule('LiveGridAjax','LiveGridMenu','GLE.css');
<?php
setStyle();
?>

var GLE_NdfGrid;
MyCustomColumn = Class.create();

MyCustomColumn.prototype = {

  initialize: function() {
    this._img=[];
  },
  _create: function(gridCell,windowRow) {
    this._img[windowRow]=RicoUtil.createFormField(gridCell,'img',null,this.liveGrid.tableId+'_img_'+this.index+'_'+windowRow);
    this._clear(gridCell,windowRow);
  },
  _clear: function(gridCell,windowRow) {
    var img=this._img[windowRow];
    img.style.display='none';
    img.src='';
  },
  _display: function(v,gridCell,windowRow) {
    var img=this._img[windowRow];
    var trad = new Array();
    trad[0]="StatusTripDraft";
    trad[1]="StatusTripProcessing";
    trad[2]="StatusTripValidated";
    trad[3]="StatusTripClose";
    var picto = new Array();
    picto[0]=0;
    picto[1]=2;
    picto[2]=3;
    picto[3]=4;
    var url = "<?php print DOL_URL_ROOT; ?>/theme/auguria/img/statut"+picto[v]+".png";
    this._img[windowRow].src=url;
    this._img[windowRow].align="absmiddle";
    this._img[windowRow].title = trad[v];
    img.style.display='';
    img.parentNode.innerHTML+= " "+trad[v];
  }
}

MyCustomColumn1 = Class.create();

MyCustomColumn1.prototype = {

  initialize: function(href,target,prefix) {
    this._href=href;
    this._target=target;
    this._anchors=[];
    this._prefixGLE=prefix;
  },

  _create: function(gridCell,windowRow) {
    this._anchors[windowRow]=RicoUtil.createFormField(gridCell,'a',null,this.liveGrid.tableId+'_a_'+this.index+'_'+windowRow);
    if (this._target) this._anchors[windowRow].target=this._target;
    this._clear(gridCell,windowRow);
  },

  _clear: function(gridCell,windowRow) {
    this._anchors[windowRow].href='';
    this._anchors[windowRow].innerHTML='';
  },

  _display: function(v,gridCell,windowRow) {
    if (this._prefixGLE)
    {
        //var col0=this.liveGrid.buffer.getWindowValue(windowRow,5);
        this._anchors[windowRow].innerHTML= this._prefixGLE+" "+ v;
    } else {
        this._anchors[windowRow].innerHTML=  v.formatDate("dd mmm yyyy");
    }

    var getWindowValue=this.liveGrid.buffer.getWindowValue.bind(this.liveGrid.buffer);
    this._anchors[windowRow].href=this._href.replace(/\{\d+\}/g,
      function ($1) {
        var colIdx=parseInt($1.substr(1));
        return getWindowValue(windowRow,colIdx);
      }
    );
  }

}

//translation
MyCustomColumn2 = Class.create();

MyCustomColumn2.prototype = {

  initialize: function(c) {
    this._colnum = c;
  },

  _create: function(gridCell,windowRow) {
    this._clear(gridCell,windowRow);
      },

  _clear: function(gridCell,windowRow) {
    gridCell.innerHTML = "";
      },

  _display: function(v,gridCell,windowRow) {
    var getWindowValue=this.liveGrid.buffer.getWindowValue.bind(this.liveGrid.buffer);
    var ArrTrans = new Array();

<?php

$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."c_deplacement
             WHERE active=1";
if ($resql=$db->query($requete))
{
    while ($res=$db->fetch_object($resql))
    {
        $display=$res->code;
        if ($langs->Trans($res->code) == $res->code)
        {
            $display = $res->libelle;
        } else {
            $display = $langs->Trans($res->code);
        }
        print "ArrTrans['".$res->code."']='".$display."';\n";
    }
}

?>
//alert(windowRow + " "+this._colnum+" "+ArrTrans[v]);
    this.liveGrid.buffer.setWindowValue(windowRow,this._colnum,ArrTrans[v]);
    gridCell.innerHTML = this._format(ArrTrans[v]);
  }

}

Rico.onLoad( function() {
  yrboxes=document.getElementsByName('year');
  var opts = {
    <?php GridSettingsScript(); ?>,
    prefetchBuffer: true,
    frozenColumns: 4,
    panels:['Description','Détails'],
    headingSort   : 'hover',
    columnSpecs   : [ { canHide: 0, visible: false, canDrag:false},
                      {Hdg:"Date", width:100,ClassName:'aligncenter', type: "date",dateFmt:"dd mmm yyyy",control: new MyCustomColumn1("<?php print DOL_URL_ROOT; ?>/Babel_TechPeople/deplacements/fiche.php?id={0}")},
                      {Hdg:"Soci&eacute;t&eacute;", width:250,control: new MyCustomColumn1("<?php print DOL_URL_ROOT; ?>/soc.php?socid={9}",'',"<img height='12pt' border='0' src='<?php print DOL_URL_ROOT; ?>/theme/auguria/img/object_company.png'></img>")},
                      {Hdg:'Lieu', width:300},
                      {Hdg:"Type", control:new MyCustomColumn2(4),ClassName:"alignright"},
                      {Hdg:"Distance", type:"number",ClassName:"alignright",suffix:" km  ",thouSep:" ",decPoint:",",decPlaces:"0"},
                      {Hdg:"Montant HT", type:'number', suffix:'&euro;', decPlaces:2,thouSep:" ",ClassName:"alignright",},
                      {Hdg:"Tx Tva", type:'number', suffix:'%', decPlaces:1, multiplier:100,ClassName:"alignright",},
                      {Hdg:"Note", width:500,ClassName:"alignright",},
                      { canHide: 0, visible: false, canDrag:false},
                      ]
  };
  var buffer=new Rico.Buffer.AjaxSQL('ricoXMLquery.php', {TimeOut:<?php print array_shift(session_get_cookie_params())/60 ?>});
  GLE_NdfGrid=new Rico.LiveGrid ('GLE_NdfGrid', buffer, opts);
  GLE_NdfGrid.menu=new Rico.GridMenu(<?php GridSettingsMenu(); ?>);
  //setFilter();
});


</script>

<style type="text/css">
.ricoLG_top div.ricoLG_col {
  white-space:nowrap;
}
</style>

</head>

<body>

<?php
    if ($mesg) print $mesg."<br>";

    print '<table class="border" width="100%">';
    print "<colgroup>";
    print "<col width='20%'/>";
    print "<col width='30%'/>";
    print "<col width='20%'/>";
    print "<col width='30%'/>";
    print "</colgroup>";

    print "<tr>";
    print '<td class="ui-widget-header ui-state-default">'.$langs->trans("Periode").'</td>
           <td class="ui-widget-content" colspan = 3>';
    print date('m/Y',$ndf->tsperiode);
    print '</td></tr>';

    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Nom").'</td>
               <td class="ui-widget-content">';
    $tuser = new User($db);
    $tuser->fetch($ndf->fk_user_author);
    print $tuser->getNomUrl(1);
    print '</td><td  class="black ui-widget-header ui-state-default" colspan="1">'.$langs->trans('Seuil').'</td>
                <td class="ui-widget-content">';
    $totKmMois = $ndf->getKm(1);
    $totKmAnn = $ndf->getKm(3);
    $totKm = $totKmAnn + $totKmMois;
    print $ndf->getSeuil($totKm);
    print '</td></tr>';

    print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("Total mensuel").'</td>
               <td class="ui-widget-content">';
    print $totKmMois . " km";
    print '</td>';
    print '<td class="black ui-widget-header ui-state-default">'.$langs->trans("Total annuel valid&eacute;").'</td>
           <td class="ui-widget-content">';

    print $totKmAnn. " km";
    print '</td></tr>';

    print "<tr><td class='ui-widget-header ui-state-default'>".$langs->trans('Total mensuel HT (hors trajet)')."</td>";
    $somHorsKM;
    $requete = "SELECT ifnull(sum(prix_ht),0) as st
                 FROM ".MAIN_DB_PREFIX."deplacement
                WHERE fk_user_author = ".$ndf->fk_user_author . "
                  AND month(".MAIN_DB_PREFIX."deplacement.dated) = ".$ndf->periode_month . "
                  AND year(".MAIN_DB_PREFIX."deplacement.dated) = " . $ndf->periode_year;
    if ($resql = $db->query($requete))
    {
        $somHorsKM = $db->fetch_object($resql)->st;
    }
    print "<td class='ui-widget-content'>".price(round($somHorsKM,2))."&euro;</td>";
    print "<td class='ui-widget-header ui-state-default black'>".$langs->trans('Total mensuel valid&eacute; TTC')."</td>";
    print "<td class='ui-widget-content'>".price(round($ndf->total_ttc,2))."&euro;</td></tr>";



    print '</table>';


print "<table id='explanation' border='0' cellpadding='0' cellspacing='5' style='clear:both'><tr valign='top'><td>";
?>
</td>


<p class="ricoBookmark"><span id="GLE_NdfGrid_bookmark">&nbsp;</span></p>
<div style="max-width: 100%; width: 100%; height: 80%;">
<div id="GLE_NdfGrid" class="ricoLiveGrid" style="max-width: 100%; width: 100%;" cellspacing="0" cellpadding="0" >
<!--<thead>
   <tr id="GLE_NdfGrid_main">
      <th>Id#</th>
      <th>Date</th>
      <th>Soci&eacute;t&eacute;</th>
      <th>Lieu</th>
      <th>Type</th>
      <th>Distance</th>
      <th>Montant HT</th>
      <th>Tx Tva</th>
      <th>Note</th>
  </tr>
</thead-->
</div>
<!--
<textarea id='GLE_NdfGrid_debugmsgs' rows='5' cols='80'>
-->
</div>

<?php

print '<div class="tabsAction">';
//
        print "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$ndf->id."&action=generatePdf>G&eacute;n&eacute;rer</a>";
if ($ndf->statut == 1|| $ndf->statut == 4 || $ndf->statut == 0)
{
        print '<a class="butAction" ';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
        {
            $url = $_SERVER["PHP_SELF"].'?id='.$ndf->id.'&action=confirm_validate&confirm=yes';
            print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.dol_escape_js($langs->trans('ConfirmDemandeValid')).'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
        } else {
            print 'href="'.$_SERVER["PHP_SELF"].'?id='.$ndf->id.'&amp;action=validate"';
        }
        print '>'.$langs->trans('Demande de validation').'</a>';

}
if ($ndf->statut == 2)
{
        print '<a class="butAction" ';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
        {
            $url = $_SERVER["PHP_SELF"].'?id='.$ndf->id.'&action=confirm_validateNdf&confirm=yes';
            print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.dol_escape_js($langs->trans('ConfirmValidateNdf')).'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
        } else {
            print 'href="'.$_SERVER["PHP_SELF"].'?id='.$ndf->id.'&amp;action=validateNdf"';
        }
        print '>'.$langs->trans('Validate').'</a>';
        print '<a class="butAction" ';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
        {
            $url = $_SERVER["PHP_SELF"].'?id='.$ndf->id.'&action=confirm_refuseNdf&confirm=yes';
            print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.dol_escape_js($langs->trans('ConfirmRefuseNdf')).'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
        } else {
            print 'href="'.$_SERVER["PHP_SELF"].'?id='.$ndf->id.'&amp;action=refuseNdf"';
        }
        print '>'.$langs->trans('Refuser').'</a>';

}



        print "</div>";
        /*
        * Documents generes
        */
//        var_dump($ndf);
        $filename=sanitize_string($ndf->ref);
        $filedir = $conf->deplacement->dir_output.'/'.sanitize_string($ndf->ref);
        //print $ndf->ref." <BR>";
//        $filedir=$conf->deplacement->dir_output . "/" . sanitize_string($deplacement->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$ndf->id;
//        $genallowed=$user->rights->deplacement->creer;
//        $delallowed=$user->rights->deplacement->supprimer;
        $genallowed = $user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->AfficheMien;
        $delallowed = $user->rights->TechPeople->ndf->Affiche || $user->rights->TechPeople->ndf->AfficheMien;

        $var=true;

$html = new Form($db);
$formfile = new FormFile($db);

        $somethingshown=$formfile->show_documents('deplacement',$filename,$filedir,$urlsource,$genallowed,$delallowed,'Babel');



?>
</body>
</html>

<?php


//function demandeInterv_prepare_head($obj)
//{
//  global $langs, $conf, $user;
//  $langs->load("ndf");
//
//  $h = 0;
//  $head = array();
//    $head[$h][0] = DOL_URL_ROOT.'/Babel_TechPeople/deplacements/index.php';
//    $head[$h][1] = $langs->trans('List');
//    $head[$h][2] = 'list';
//    $h++;
//
//  $head[$h][0] = DOL_URL_ROOT.'/Babel_TechPeople/deplacements/ficheNdf.php?id='.$obj->id;
//  $head[$h][1] = $langs->trans("Mois en cours");
//  $head[$h][2] = 'Mois';
//  $h++;
//
//  $head[$h][0] = DOL_URL_ROOT.'/Babel_TechPeople/deplacements/fiche.php??action=create';
//  $head[$h][1] = $langs->trans('Ajout d\'une ndf');
//  $head[$h][2] = 'Ajoute';
//    $h++;
//
//
//  return $head;
//}


?>