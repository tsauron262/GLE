<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : card.php
  * BIMP-ERP-1.2
  */
//Fiche litige

//TODO si
// virer commande contrat (aka de service)
// si facture => SAV, litiges, retour OK KO
// si livraison / expedition => SAV, litiges, retour OK KO
// si livraison / expedition => SAV, litiges, retour OK KO
// si contratGA => SAV, litiges, retour OK KO


//TODO : statut
//       avncement
//       pointage matériel
//       retour partiel
//       retour total
//       Litige et reglement
//       Link to SAV

require_once('pre.inc.php');
require_once('retour.class.php');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$msg=false;
$retourId= $_REQUEST['id'];
$js = '<script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>';
$js .= '<script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>';
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$action = $_REQUEST['action'];

if ($action=="create")
{
    $fk_soc=$_REQUEST['fk_soc'];
    $element_id=$_REQUEST['element_id'];
    $element_type=$_REQUEST['element_type'];
    $retour = new Retour($db);
    $retour->fk_soc = $fk_soc;
    $retour->element_id = $element_id;
    $retour->element_type = $element_type;
    $retourId=$retour->create();
    if (!$retourId)
    {
        $msg = $retour->error;
    }
}
if ($action == "update")
{
    $fk_soc = $_REQUEST['societe_id'];
    $element_type = $_REQUEST['type'];
    $element_id = $_REQUEST['element_id'];
    $user_author_id = $_REQUEST['user_author_id'];
    $user_resp_id = $_REQUEST['user_resp_id'];
    if ($user_resp_id < 0) $user_resp_id="NULL";
    $cause = $_REQUEST['cause'];

    $retour = new Retour($db);
    if (preg_match('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/',$_REQUEST['date_retour'],$arr))
    {
        $retour->date_retour = "'".$arr[3].'-'.$arr[2].'-'.$arr[1].' '.($arr[4]>0?$arr[4]:"00").':'.($arr[5]>0?$arr[5]:"00")."'";
    } else {
        $retour->date_retour ="NULL";
    }
    $retour->fk_soc = $fk_soc;
    $retour->element_id = $element_id;
    $retour->element_type = $element_type;
    $retour->user_author_id = $user_author_id;
    $retour->user_resp_id = $user_resp_id;
    $retour->cause = $cause;
    $retour->id = $retourId;
    $retour->update();
    if (!$retourId)
    {
        $msg = $retour->error;
    }

}
if ($action=="validateProdList")
{
    $retour = new Retour($db);
    $retour->fetch($retourId);
    $typeRef = $_REQUEST['typeRef'];
    //recupere les données du retour
    require_once('Var_Dump.php');
    Var_Dump::Display($_REQUEST);
    $retour->ProdList = array();
    foreach($_REQUEST as $key=>$val){
        if (preg_match('/^ProdRetour-([0-9]+)/',$key,$arrTmp))
        {
            $retour->ProdList[$arrTmp[1]]=$val;
        }
    }
    $retourId=$retour->validateProdList($retourId,$typeRef);
    if (!$retourId)
    {
        $msg = $retour->error;
    }

}
if($_REQUEST['action']=='validate')
{
    $retour = new Retour($db);
    $retour->fetch($retourId);
    $retour->validate();

}

$js .= "<script>";
$js .= "var retourId = ".$retourId.";\n";
$js .=<<<EOF
jQuery(document).ready(function(){
    jQuery.datepicker.setDefaults(jQuery.extend({
        showMonthAfterYear: false,
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        buttonImage: 'cal.png',
        buttonImageOnly: true,
        showTime: true,
        duration: '',
        constrainInput: false,
    }, jQuery.datepicker.regional['fr']));
    jQuery('.datepicker').datepicker();
});

EOF;
$js .= "</script>";
if ($action == 'edit')
{
    $js .= <<<EOF
<script>
function reinit(){
    jQuery('#element_id').remove();
    jQuery("#type").selectmenu("value", -1); // Put it back on the first option.
    jQuery("#type").selectmenu("open");
}
jQuery(document).ready(function(){
    jQuery('#type').change(function(){
        var selType = jQuery(this).find(':selected').val();
        var socid = jQuery('#societe_id').val();
        jQuery.ajax({
            url: "ajax/listElementRetour_xml-response.php",
            data: "action=list1type&type="+selType+"&socid="+socid,
            datatype: "xml",
            type: "POST",
            cache: true,
            success: function(msg){
                var html = "";
                html += "<SELECT name='element_id' id='element_id'>"
                jQuery(msg).find(selType).each(function(){
                    var ref = jQuery(this).find('ref').text();
                    var id = jQuery(this).find('id').text();
                    var dc = jQuery(this).find('date').text();
                    html += "<option value='"+id+"'>"+ref+" ("+dc+")</option>";
                });
                html +="</SELECT>";
                jQuery("#elementIdDiv").replaceWith('<div id="elementIdDiv">'+html+'</div>');
                jQuery("#elementIdDiv").find('SELECT').selectmenu({style: 'dropdown', maxHeight: 300 });
            }
        });
    })
});
    </script>
EOF;
}


llxHeader($js,'Retour Produit','',1);
print " <br>";
print "  <div class='titre'>Espace retour produit</div>";
print " <br>";

if ($msg)
{
    print "<div class='ui-state-error'>".$msg."</div>";
}

if ($retourId>0)
{
    //Visualisation
    $retour = new Retour($db);
    if ($retour->fetch($retourId)){
        //Cartouche
        if ($action!="edit" && $action!="create")
        {
            if ($retour->element_type=='commande')
            {
                require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
                $obj = new Commande($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='facture')
            {
                require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                $obj = new Facture($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='livraison')
            {
                require_once(DOL_DOCUMENT_ROOT."/livraison/class/livraison.class.php");
                $obj = new Livraison($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='contrat')
            {
                require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
                $obj = new Contrat($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='contratSAV')
            {
                require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/contratSAV.class.php");
                $obj = new ContratSAV($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='contratGA')
            {
                require_once(DOL_DOCUMENT_ROOT."/Babel_GA/contratGA.class.php");
                $obj = new ContratGA($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='commande fournisseur')
            {
                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
                $obj = new CommandeFournisseur($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }
            if ($retour->element_type=='facture fournisseur')
            {
                require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
                $obj = new FactureFournisseur($db);
                $obj->fetch($retour->element_id);
                $ref = $obj->getNomUrl(1);
            }

            print "<table cellpadding=15 width=900>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Client : </th><td class='ui-widget-content'>".$retour->soc->getNomUrl(1)."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Element : </th><td class='ui-widget-content'>".$langs->Trans($retour->element_type).": ".$ref."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Date de cr&eacute;ation</th><td class='ui-widget-content'>".date('d/m/Y H:i',$retour->date_creation)."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Date du retour: </th><td class='ui-widget-content'>".($retour->date_retour > 0?date('d/m/Y H:i',$retour->date_retour):"")."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Auteur: </th><td class='ui-widget-content'>".$retour->user_author->getNomUrl(1)."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Responsable: </th><td class='ui-widget-content'>".($retour->user_resp->nom."x"!="x"?$retour->user_resp->getNomUrl(1):"")."</td>";
//TODO si cause indéterminé, mettre un champs select / autocomplete pour la cause
//TODO Nécéssaire pour la validation d'avoir une cause
            print "       <tr><th class='ui-widget-header ui-state-default'>Cause: </th><td class='ui-widget-content'>".($retour->cause."x"!="x"?$retour->cause:"Ind&eacute;termin&eacute;e")."</td>";
//TODO If droit de modifier
//TODO autres bouttons
            print "       <tr><th align='right' class='ui-widget-header ui-state-default' colspan=2>
                                    <button onClick='location.href=\"".$_SERVER['PHP_SELF']."?id=".$retourId."&action=validate\"' class='ui-widget-header ui-state-default butAction ui-corner-all'>Valider</button>
                                    <button onClick='location.href=\"".$_SERVER['PHP_SELF']."?id=".$retourId."&action=edit\"' class='ui-widget-header ui-state-default butAction ui-corner-all'>Modifier</button>
                              </th>";
            print "</table>";

            //Produits
                print <<<EOF
                <script>
                    function allLitige()
                    {
                        changeState(3);
                    }
                    function allSAV()
                    {
                        changeState(2);
                    }
                    function allKO()
                    {
                        changeState(4);
                    }
                    function allOK()
                    {
                        changeState(1);
                    }
                    function changeState(num)
                    {
                        jQuery('.SelBxProd').each(function(){
                            jQuery(this).selectmenu('value',num);
                        });

                    }
                    function validateProdList()
                    {
                        //GEt Data Prod
                        var data = "";
                        var typeRef;
                        jQuery('#prodTable').find('tr').each(function(){
                            var id = jQuery(this).find('select.SelBxProd').attr('id');
                            if (id>0)
                            {
                                var retourStatut = jQuery(this).find('select.SelBxProd').find(':selected').val();
                                typeRef = jQuery(this).find('#typeReferent').val();
                                data += "&ProdRetour-"+id+"="+retourStatut;
                            }
                        });
                        //Send datas
                        location.href = "card.php?id="+retourId+"&action=validateProdList"+data+"&typeRef="+typeRef;
                    }
                </script>

EOF;
                print "<br/>";
                print "<table id='prodTable' cellpadding=10 width=900>";
                print "<thead><tr><th width=5% class='ui-widget-content ui-state-default'>Retour OK</th>
                                  <th width=20% class='ui-widget-content ui-state-default'>Serial</th>
                                  <th width=20% class='ui-widget-content ui-state-default'>Infos</th>
                                  <th class='ui-widget-content ui-state-default'>Description</th>
                                  <th width=10% class='ui-widget-content ui-state-default'>Montant</th>
                       </thead><tbody>";
                switch ($retour->element_type){
//TODO change check box en select :
//:> retour
//:> pas de retour
//:>litige
//:>sav
//:>sav
                    case 'commande':
                    {
                        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = '".$retour->element_id."'";
                        $sql = $db->query($requete);
                        while ($res = $db->fetch_object($sql))
                        {
                            if ($res->fk_product > 0)
                            {
                                $retour->getProdSerial('commande',$res->rowid);
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($res->fk_product);
                                $selBoxRetour = "<SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'>";
                                $selBoxRetour .= "<OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                                if ($retour->cause != "SAV")
                                {
                                    $selBoxRetour .= "<OPTION value='OK'>Retour OK</OPTION>";
                                    $selBoxRetour .= "<OPTION value='Litige'>Litige</OPTION>";
                                    $selBoxRetour .= "<OPTION value='KO'>Retour KO</OPTION>";
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                } else {
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                }
                                $selBoxRetour .= "</SELECT>";
                                $selBoxRetour .= "<input type='hidden' id='typeReferent' value='commande'></input>";

                                print "<tr><td align='center' class='ui-widget-content' valign=top>".$selBoxRetour;
                                if (strlen($retour->extraSerialInfo['commande'][$res->rowid]['serial']) > 0)
                                {
                                    print "     <td class='ui-widget-content'>".$retour->extraSerialInfo['commande'][$res->rowid]['serial'] . " <br/>facture du ". date('d/m/Y',$retour->extraSerialInfo['facture'][$res->rowid]['date_creation']). "<br/>Fin SAV ".date('d/m/Y',$retour->extraSerialInfo['commande'][$res->rowid]['date_fin_SAV']);
                                    print "     <td class='ui-widget-content'>";
                                } else {
                                    print "<td colspan=2 class='ui-widget-content' align=center valign=top>-</td>";
                                }

                                $tmpProd = new Product($db);
                                $tmpProd->fetch($res->fk_product);
                                print "<tr><td align='center' class='ui-widget-content'><SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'><OPTION value='-1'>S&eacute;lectionner-></OPTION><OPTION value='OK'>Retour OK</OPTION><OPTION value='SAV'>SAV</OPTION><OPTION value='Litige'>Litige</OPTION><OPTION value='KO'>Retour KO</OPTION></SELECT><td class='ui-widget-content'>".$tmpProd->getNomUrl(1) . "<td class='ui-widget-content' align='center'>".price($tmpProd->price)."&euro;";
                            } else {
                                print "<tr><td align='center' class='ui-widget-content'><SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'><OPTION value='-1'>S&eacute;lectionner-></OPTION><OPTION value='OK'>Retour OK</OPTION><OPTION value='SAV'>SAV</OPTION><OPTION value='Litige'>Litige</OPTION><OPTION value='KO'>Retour KO</OPTION></SELECT><td class='ui-widget-content'>".$res->description . "<td class='ui-widget-content' align='center'>".price($res->total_ht)."&euro;";
                            }
                        }
                    }
                    break;
                    case 'facture':
                    {
                       $requete = "SELECT * FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture = '".$retour->element_id."'";
                        $sql = $db->query($requete);
                        while ($res = $db->fetch_object($sql))
                        {
                            //recup du serial
                            if ($res->fk_product > 0)
                            {
                                $retour->getProdSerial('facture',$res->rowid);
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($res->fk_product);
                                $selBoxRetour = "<SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'>";
                                $selBoxRetour .= "<OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                                if ($retour->cause != "SAV")
                                {
                                    $selBoxRetour .= "<OPTION value='OK'>Retour OK</OPTION>";
                                    $selBoxRetour .= "<OPTION value='Litige'>Litige</OPTION>";
                                    $selBoxRetour .= "<OPTION value='KO'>Retour KO</OPTION>";
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                } else {
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                }
                                $selBoxRetour .= "</SELECT>";
                                $selBoxRetour .= "<input type='hidden' id='typeReferent' value='facture'></input>";

                                print "<tr><td align='center' class='ui-widget-content' valign=top>".$selBoxRetour;
                                if (strlen($retour->extraSerialInfo['facture'][$res->rowid]['serial']) > 0)
                                {
                                    print "     <td class='ui-widget-content'>".$retour->extraSerialInfo['facture'][$res->rowid]['serial'] . " <br/>facture du ". date('d/m/Y',$retour->extraSerialInfo['facture'][$res->rowid]['date_creation']). "<br/>Fin SAV ".date('d/m/Y',$retour->extraSerialInfo['facture'][$res->rowid]['date_fin_SAV']);
                                    print "     <td class='ui-widget-content'>";
                                } else {
                                    print "<td colspan=2 class='ui-widget-content' align=center valign=top>-</td>";
                                }
                                print "     <td class='ui-widget-content' valign=top>".$tmpProd->getNomUrl(1) . " <br/>". $tmpProd->description. "
                                            <td class='ui-widget-content' valign=top align='center'>".price($tmpProd->price)."&euro;";
                            } else {
                                print "<tr><td align='center' class='ui-widget-content' valign=top><SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'><OPTION value='-1'>S&eacute;lectionner-></OPTION><OPTION value='OK'>Retour OK</OPTION><OPTION value='SAV'>SAV</OPTION><OPTION value='Litige'>Litige</OPTION><OPTION value='KO'>Retour KO</OPTION></SELECT><td class='ui-widget-content'>".$res->description . "<td class='ui-widget-content' align='center' valign=top>".price($res->total_ht)."&euro;";
                            }
                        }
                    }
                    break;
                    case 'livraison':
                    {
                       $requete = "SELECT * FROM ".MAIN_DB_PREFIX."livraisondet WHERE fk_livraison = '".$retour->element_id."'";
                        $sql = $db->query($requete);
                        while ($res = $db->fetch_object($sql))
                        {
                            //recup du serial
                            if ($res->fk_product > 0)
                            {
                                $retour->getProdSerial('livraison',$res->rowid);
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($res->fk_product);

                                $selBoxRetour = "<SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'>";
                                $selBoxRetour .= "<OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                                if ($retour->cause != "SAV")
                                {
                                    $selBoxRetour .= "<OPTION value='OK'>Retour OK</OPTION>";
                                    $selBoxRetour .= "<OPTION value='Litige'>Litige</OPTION>";
                                    $selBoxRetour .= "<OPTION value='KO'>Retour KO</OPTION>";
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                } else {
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                }
                                $selBoxRetour .= "</SELECT>";
                                $selBoxRetour .= "<input type='hidden' id='typeReferent' value='livraison'></input>";

                                print "<tr><td align='center' class='ui-widget-content' valign=top>".$selBoxRetour;
                                if (strlen($retour->extraSerialInfo['livraison'][$res->rowid]['serial']) > 0)
                                {
                                    print "     <td class='ui-widget-content'>".$retour->extraSerialInfo['livraison'][$res->rowid]['serial'] . " <br/>Exp&eacute;di&eacute; le ". date('d/m/Y',$retour->extraSerialInfo['livraison'][$res->rowid]['date_creation']). "<br/>Fin SAV ".date('d/m/Y',$retour->extraSerialInfo['livraison'][$res->rowid]['date_fin_SAV']);
                                    print "     <td class='ui-widget-content'>";
                                } else {
                                    print "<td colspan=2 class='ui-widget-content' align=center valign=top>-</td>";
                                }
                                print "     <td class='ui-widget-content' valign=top>".$tmpProd->getNomUrl(1) . " <br/>". $tmpProd->description. "
                                            <td class='ui-widget-content' valign=top align='center'>".price($tmpProd->price)."&euro;";
                            } else {
                                print "<tr><td align='center' class='ui-widget-content' valign=top><SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'><OPTION value='-1'>S&eacute;lectionner-></OPTION><OPTION value='OK'>Retour OK</OPTION><OPTION value='SAV'>SAV</OPTION><OPTION value='Litige'>Litige</OPTION><OPTION value='KO'>Retour KO</OPTION></SELECT><td class='ui-widget-content'>".$res->description . "<td class='ui-widget-content' align='center' valign=top>".price($res->total_ht)."&euro;";
                            }
                        }
                    }
                    break;
                    case 'contratGA':
                    case 'contrat':
                    {
                       $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.rowid,
                                          ".MAIN_DB_PREFIX."contratdet.fk_product,
                                          ".MAIN_DB_PREFIX."contratdet.total_ht,
                                          ".MAIN_DB_PREFIX."contratdet.description,
                                          Babel_retourdet.fk_statut
                                     FROM ".MAIN_DB_PREFIX."contratdet
                                LEFT JOIN Babel_retourdet ON Babel_retourdet.element_id = ".MAIN_DB_PREFIX."contratdet.rowid AND Babel_retourdet.element_type LIKE 'contrat%'
                                    WHERE fk_contrat = '".$retour->element_id."'";
                        $sql = $db->query($requete);
                        require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
                        $contrat = new Contrat($db);
                        $contrat->fetch($retour->element_id);
                        while ($res = $db->fetch_object($sql))
                        {
                            if ($res->fk_product > 0)
                            {
                                $retour->getProdSerial('contrat',$res->rowid);
                                $tmpProd = new Product($db);
                                $tmpProd->fetch($res->fk_product);
                                $selBoxRetour = "<SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'>";
                                $selBoxRetour .= "<OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                                if ($retour->cause != "SAV")
                                {
                                    $selBoxRetour .= "<OPTION value='OK'>Retour OK</OPTION>";
                                    $selBoxRetour .= "<OPTION value='Litige'>Litige</OPTION>";
                                    $selBoxRetour .= "<OPTION value='KO'>Retour KO</OPTION>";
                                    $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                } else {
                                    if ($res->fk_statut > 0)
                                    {
                                        $selBoxRetour .= "<OPTION SELECTED value='SAV'>SAV</OPTION>";
                                    } else {
                                        $selBoxRetour .= "<OPTION value='SAV'>SAV</OPTION>";
                                    }
                                }
                                $selBoxRetour .= "</SELECT>";
                                $selBoxRetour .= "<input type='hidden' id='typeReferent' value='contrat'></input>";
                                print "<tr><td align='center' class='ui-widget-content'>".$selBoxRetour."</td>";
                                if (strlen($retour->extraSerialInfo['contrat'][$res->rowid]['serial']) > 0)
                                {
                                    $arrTmp = $contrat->getTypeContrat();
                                    print "     <td class='ui-widget-content'>".$retour->extraSerialInfo['contrat'][$res->rowid]['serial'] . "<td class='ui-widget-content'>
                                                    <table><tr><th style='color: white;'>Contrat (".$arrTmp['Nom'].")<tr><td>".$contrat->getNomUrl(1)."
                                                    <tr><th style='color: white;'>Fin SAV<tr><td> ".date('d/m/Y',$retour->extraSerialInfo['contrat'][$res->rowid]['date_fin_SAV'])
                                                   .'</table>';
                                } else {
                                    print "<td colspan=2 class='ui-widget-content' align=center valign=top>-</td>";
                                }
                                print "<td class='ui-widget-content'>".$tmpProd->getNomUrl(1) .' '. $tmpProd->libelle .  "<td class='ui-widget-content'>".price($res->total_ht)."&euro;";
                            } else {
                                print "<tr><td align='center' class='ui-widget-content'><SELECT class='SelBxProd' name='".$res->rowid."' id='".$res->rowid."'><OPTION value='-1'>S&eacute;lectionner-></OPTION><OPTION value='OK'>Retour OK</OPTION><OPTION value='SAV'>SAV</OPTION><OPTION value='Litige'>Litige</OPTION><OPTION value='KO'>Retour KO</OPTION></SELECT><td class='ui-widget-content'>".$res->description . "<td class='ui-widget-content' align='center' nowrap>".price($res->total_ht)."&euro;";
                            }
                        }
                    }
                    break;
                }
//                if($retour->fk_statut==1)
//                {
                    print "<tfoot>";
                    print "<tr><th colspan=5 align=right>";
                    if ($retour->cause != "SAV")
                    {
                        print "            <button onClick='allLitige()' class='butAction ui-widget-header ui-state-default ui-corner-all'>Retour Total Litige</button>";
                        print "            <button onClick='allKO()' class='butAction ui-widget-header ui-state-default ui-corner-all'>Retour Total KO</button>";
                        print "            <button onClick='allOK()' class='butAction ui-widget-header ui-state-default ui-corner-all'>Retour Total OK</button>";
                    } else {
                        print "            <button onClick='allSAV()' class='butAction ui-widget-header ui-state-default ui-corner-all'>Retour Total SAV</button>";
                    }
                    print "            <button onClick='validateProdList()' class='butAction ui-widget-header ui-state-default ui-corner-all'>Valider les produits</button>";
                    print "    </th>";
                    print "</tfoot>";
//                }
                print "</table>";

        } else if ($action=='edit' || $action=='create') {
            require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
            $html = new Form($db);
            $ref = "";
            print "<form action='card.php?id=".$retourId."&action=update' method='POST'>";
            print "<table cellpadding='15' width='80%'><tbody>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Client : </th>";
            print "<td class='ui-widget-content' colspan=2>".$html->select_company($retour->fk_soc,'societe_id','',1,false,"reinit();")."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Element : </th><td class='ui-widget-content'><select name='type' id='type'>";
            print "<option value='-1'>S&eacute;lection -></option>";
            $date = "";
            $ref = "";
            $secondSelect = "";
            if ($retour->element_type=='commande')
            {
                $requete = "SELECT rowid, ref, unix_timestamp(date_commande) as dc FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc=".$retour->soc->id;
                $sql = $db->query($requete);
                $secondSelect = "<SELECT name='element_id' id='element_id'>";
                while($res = $db->fetch_object($sql))
                {
                    if ($retour->element_id == $res->rowid)
                    {
                        $secondSelect.= "<option SELECTED value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    } else {
                        $secondSelect.= "<option value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    }
                }
                $secondSelect .= "</SELECT>";

                print "               <option SELECTED value='commande'>Commande</option>";
            } else {
                print "               <option value='commande'>Commande</option>";
            }
            if ($retour->element_type=='facture')
            {
                $requete = "SELECT rowid, ref as ref, unix_timestamp(datef) as dc FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc=".$retour->soc->id;
                $sql = $db->query($requete);
                $secondSelect = "<SELECT name='element_id' id='element_id'>";
                while($res = $db->fetch_object($sql))
                {
                    if ($retour->element_id == $res->rowid)
                    {
                        $secondSelect.= "<option SELECTED value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    } else {
                        $secondSelect.= "<option value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    }
                }
                $secondSelect .= "</SELECT>";
                print "               <option SELECTED value='facture'>Facture</option>";
            } else {
                print "               <option value='facture'>Facture</option>";
            }
            if ($retour->element_type=='livraison')
            {
                $requete = "SELECT rowid, ref, unix_timestamp(date_livraison) as dc FROM ".MAIN_DB_PREFIX."livraison WHERE fk_soc=".$retour->soc->id;
                $sql = $db->query($requete);
                $secondSelect = "<SELECT name='element_id' id='element_id'>";
                while($res = $db->fetch_object($sql))
                {
                    if ($retour->element_id == $res->rowid)
                    {
                        $secondSelect.= "<option SELECTED value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    } else {
                        $secondSelect.= "<option value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    }
                }
                $secondSelect .= "</SELECT>";
                print "               <option SELECTED value='livraison'>Livraison</option>";
            } else {
                print "               <option value='livraison'>Livraison</option>";
            }
            if (preg_match('/contrat/',$retour->element_type))
            {
                $requete = "SELECT type,
                                   rowid,
                                   ref,
                                   unix_timestamp(date_contrat) as dc
                              FROM ".MAIN_DB_PREFIX."contrat
                             WHERE is_financement=0
                               AND fk_soc=".$retour->soc->id. "
                          ORDER BY type";
                $sql = $db->query($requete);
                $secondSelect = "<SELECT name='element_id' id='element_id'>";
                $remOptGrp = "";
                    require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
                while($res = $db->fetch_object($sql))
                {
                    $extra = "";
                    if ($res->type != $rem)
                    {
                        $contTmp = new Contrat($db);
                        $contTmp->typeContrat=$res->type;
                        $TypeArr = $contTmp->getTypeContrat();
                        $secondSelect.= "<optgroup label='".$TypeArr['Nom']."'>";
//                        $rem = $res->type;
                    }
                    if ($retour->element_id == $res->rowid)
                    {
                        $secondSelect.= "<option SELECTED value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    } else {
                        $secondSelect.= "<option value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    }
                    if ($res->type != $rem)
                    {
                        print "</optgroup>";
                        $rmp = $res->type;
                    }
                }
                $secondSelect .= "</SELECT>";
                print "               <option SELECTED value='contrat'>contrat</option>";
            } else {
                print "               <option value='contrat'>contrat</option>";
            }
            if ($retour->element_type=='contratGA')
            {
                $requete = "SELECT rowid, ref, unix_timestamp(date_contrat) as dc FROM ".MAIN_DB_PREFIX."contrat WHERE is_financement=1 AND fk_soc=".$retour->soc->id;
                $sql = $db->query($requete);
                $secondSelect = "<SELECT name='element_id' id='element_id'>";
                while($res = $db->fetch_object($sql))
                {
                    if ($retour->element_id == $res->rowid)
                    {
                        $secondSelect.= "<option SELECTED value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    } else {
                        $secondSelect.= "<option value='".$res->rowid."'>".$res->ref. " - (".date('d/m/Y',$res->dc).")</option>";
                    }
                }
                $secondSelect .= "</SELECT>";
                print "               <option SELECTED value='contratGA'>Location</option>";
            } else {
                print "               <option value='contratGA'>Location</option>";
            }
            print "           </select>";
            //Need 2nd select
            print "             <td class='ui-widget-content'>";
            print "             <div id='elementIdDiv'>";
            print $secondSelect;
            print "             </div>";
            print "          </td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Date de cr&eacute;ation</th><td  colspan=2 class='ui-widget-content'>".date('d/m/Y H:i',$retour->date_creation)."</td>";
            print "       <tr><th class='ui-widget-header ui-state-default'>Date du retour: </th><td colspan=2 class='ui-widget-content'><input type='text' name='date_retour' class='datepicker' value='".($retour->date_retour > 0?date('d/m/Y H:i',$retour->date_retour):date('d/m/Y H:i',$retour->date_creation))."'></td>";
            $html->select_users($retour->user_author->id,'user_author_id',1,'',0,false);
            print "       <tr><th class='ui-widget-header ui-state-default'>Auteur: </th><td colspan=2 class='ui-widget-content'>".$html->tmpReturn."</td>";
            $html->select_users($retour->user_resp->id,'user_resp_id',1,"",0,false);
            print "       <tr><th class='ui-widget-header ui-state-default'>Responsable: </th><td colspan=2 class='ui-widget-content'>".$html->tmpReturn."</td>";
        //Cause du retour
            print "       <tr><th  class='ui-widget-header ui-state-default'>Cause du retour<td colspan=2 class='ui-widget-content'>";
            print "           <SELECT id='cause' name='cause'> ";
            if ($retour->cause == 'retour' || $retour->cause .'x' == 'x')
            {
                print "             <OPTION SELECTED value='retour'>Retour pr&eacute;vu</OPTION>";
            } else {
                print "             <OPTION value='retour'>Retour pr&eacute;vu</OPTION>";
            }
            if ($retour->cause == 'litige')
            {
                print "             <OPTION SELECTED value='litige'>Litige</OPTION>";
            } else {
                print "             <OPTION value='litige'>Litige</OPTION>";
            }
            if ($retour->cause == 'SAV')
            {
                print "             <OPTION SELECTED value='SAV'>SAV</OPTION>";
            } else {
                print "             <OPTION value='SAV'>SAV</OPTION>";
            }
            print "           </SELECT>";
            print "<tr><th colspan=3 class='ui-widget-header ui-state-default'><button style='padding:5px 10px;' class='butAction ui-corner-all ui-state-default ui-widget-header' name='cancel' onClick='location.href=\"".$_SERVER['PHP_SELF']."?id=".$retourId."\";return(false);'>Annuler</button>";
            print "                  <button style='padding:5px 10px;' class='butAction ui-corner-all ui-state-default ui-widget-header' name='save'>Enregistrer</button></th>";
            print "</tbody></table>";
        }
    } else {
         print "<div class='ui-state-error'>Erreur de lecture ".$retour->error."</div>";
    }

    //Edition
} else {
    print "<div class='ui-state-error'>Erreur d'id</div>";
}
?>