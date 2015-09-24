<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : resa.php
  * GLE-1.1
  */

//TODO: -> faire les tache du projet
//      -> faire l'affichage des infos de reservation
//      -> faire test del / modif

$action = $_REQUEST['action'];
$_REQUEST['mainmenu']="Ressources";
$_REQUEST['idmenu']=294716;
$_GET['mainmenu']="Ressources";
$_GET['idmenu']=294716;
require_once('pre.inc.php');
require_once('ressource.class.php');
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Zimbra/ZimbraSoap.class.php');

$ressourceId = $_REQUEST['ressource_id'];

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js .= "<style type='text/css'> INPUT.hasDatepicker { width: 150px;}</style>";
$js .= "<style type='text/css'>.promoteZ , .ui-datepicker{ z-index: 2006; /* Dialog z-index is 1006*/}</style>";
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-latest.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-ui-latest.custom.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/tiptip/jquery.tipTip.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.datetimepicker.js" type="text/javascript"></script>' ;
$js .= ' <script src="'.$jspath.'/jquery.treeview.js" type="text/javascript"></script>';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'/jquery.treeview.css" />';
$js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.DOL_URL_ROOT.'/includes/jquery/css/smoothness/jquery-ui-latest.custom.css" />';
$js .= '<script type="text/javascript">
var resaId = "";
var selOpt = false;
jQuery(document).ready(function(){

jQuery.validator.addMethod(
                "FRDate",
                function(value, element) {
                    // put your own logic here, this is just a (crappy) example
                    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W\d\d\:\d\d$/);
                },
                "La date doit &ecirc;tre au format dd/mm/yyyy hh:mm"
            );

    jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false, dateFormat: "dd/mm/yy",
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: "cal.png",
                        buttonImageOnly: true,
                        showTime: true,
                        duration: "",
                        constrainInput: false,}, jQuery.datepicker.regional["fr"]));

            jQuery("#ui-datepicker-div").addClass("promoteZ");
            jQuery("#ui-timepicker-div").addClass("promoteZ");
            jQuery(".datePicker").datetimepicker();

jQuery("#imputationProj").change(function(){
    jQuery.ajax({
        url: "ajax/resa_ajax.php",
        datatype:"html",
        data:"action=listTask&projId="+jQuery("#imputationProj :selected").val(),
        type:"POST",
        async:false,
        cache: false,
        success: function(msg){
            if (jQuery("#addresaForm").find(\'table\').find("tbody").find(\'#treeTask\').length > 0)
            {
                jQuery("#addresaForm").find(\'table\').find("tbody").find(\'#treeTask\').remove();
            }
            jQuery("#addresaForm").find(\'table\').find("tbody").append("<tr id=\"treeTask\"><td colspan=2>"+jQuery(msg).html()+"</tr>");
            jQuery("#projetTree").treeview({
                collapsed: true,
                animated: "slow",
                prerendered: true,
            });

        }
    });
});

    jQuery("#addresa").click(function(){
        var datedeb=jQuery("#dateDeb").val();
        var datefin=jQuery("#dateFin").val();
        var projId=jQuery("#imputationProj").val();
        var imputationUser=jQuery("#imputation").val();
        var ressourceId='.$ressourceId.';
 if (jQuery("#addresaForm").validate({
                                            rules: {
                                                    dateDeb: {
                                                        FRDate: true,
                                                        required: true,
                                                    },
                                                    dateFin: {
                                                        FRDate: true,
                                                        required: true
                                                    },
                                                    imputation: {
                                                        required: true
                                                    },
                                                },
                                                messages: {
                                                    dateDeb: {
                                                      FRDate: "<br>Le format de la date est inconnu",
                                                      required: "<br>Champ requis"
                                                    },
                                                    dateFin: {
                                                      FRDate: "<br>Le format de la date est inconnu",
                                                      required: "<br>Champ requis"
                                                    },
                                                    imputation: {
                                                        required: "<br>Champ requis"
                                                    },
                                                }
                                            }).form()) {
                                                var extra="";
                                                if (taskId) { extra="&taskId="+taskId; }
                                                jQuery.ajax({
                                                    async: true,
                                                    type: "post",
                                                    url: "ajax/resa_ajax.php",
                                                    data: "projId="+projId+"&action=add&datedeb=" + datedeb + "&datefin=" + datefin+"&ressourceId="+ressourceId+"&imputation="+imputationUser+extra,
                                                    success: function(msg){
                                                        if (jQuery(msg).find(\'KO\').text()==\'Doublon\')
                                                        {
                                                            jQuery(\'#addresaForm\').prepend(\'<div id="errDoublon" class="ui-state-highlight">Cette ressource est d&eacute;j&agrave; r&eacute;serv&eacute;e pour cette date !</div> \');
                                                        } else {
                                                            if (jQuery(\'#errDoublon\')){ jQuery(\'#errDoublon\').remove(); }
                                                            location.href="resa.php?ressource_id="+ressourceId;
                                                        }
                                                    }
                                                }); //close ajax
                                            } // close validator
    });
    jQuery("#modDelResa").change(function(){
        //get info from appointment
        var ressourceId='.$ressourceId.';
        var zimId = jQuery("#modDelResa :selected").val();
        selOpt = jQuery("#modDelResa :selected")
        var post="zimId="+zimId +"&ressourceId="+ressourceId+"&action=get";
        resaId = zimId;
        jQuery.ajax({
            url: "ajax/resa_ajax.php",
            async: true,
            type: "POST",
            data: post,
            datatype: "xml",
            success: function(msg){
                //display in div
                if (jQuery(msg).find("resa").length == 1)
                {
                    var datedeb = jQuery(msg).find("datedeb").text();
                    var datefin = jQuery(msg).find("datefin").text();
                    var zimbraId = jQuery(msg).find("zimbraId").text();
                    var rowid = jQuery(msg).find("rowid").text();
                    var nom = jQuery(msg).find("nom").text();
                    var author = jQuery(msg).find("fk_user_author").text();
                    var imput = jQuery(msg).find("fk_user_imputation").text();
                    var html = "<table width=100% cellpadding=10>";
                        html += "<tr><th class=\'ui-widget-header ui-state-default\'>D&eacute;but";
                        html += "    <td class=\'ui-widget-content\'>"+datedeb;
                        html += "<tr><th class=\'ui-widget-header ui-state-default\'>Fin";
                        html += "    <td class=\'ui-widget-content\'>"+datefin;
                        html += "<tr><th class=\'ui-widget-header ui-state-default\'>Auteur";
                        html += "    <td class=\'ui-widget-content\'>"+author;
                        html += "<tr><th class=\'ui-widget-header ui-state-default\'>Imputer &agrave;";
                        html += "    <td class=\'ui-widget-content\'>"+imput;
                        html += "<tr><th class=\'ui-widget-header ui-state-default\' colspan=2>";
                        html += "    <button style=\"padding: 5px 10px; width: 170px;\" class=\"ui-widget-header ui-state-default ui-corner-all butAction\" onClick=\"initDelButton()\" id=\"effaceResa\">Annuler la r&eacute;servation</button>";
                        html += "</table>";
                    jQuery("#infoResa").replaceWith("<div id=\'infoResa\'>"+html+"</div>");
                } else if (jQuery(msg).find("resa").length > 1)
                {
                    var html  = "<div class=\'ui-state-highlight\'>Il y a plusieurs r&eacute;servations &agrave; cette date!</div>";
                        html += "<table width=100% cellpadding=10>";
                    jQuery(msg).find("resa").each(function(){
                        var datedeb = jQuery(this).find("datedeb").text();
                        var datefin = jQuery(this).find("datefin").text();
                        var zimbraId = jQuery(this).find("zimbraId").text();
                        var rowid = jQuery(this).find("rowid").text();
                        var nom = jQuery(this).find("nom").text();
                        var author = jQuery(this).find("fk_user_author").text();
                        var imput = jQuery(this).find("fk_user_imputation").text();
                            html += "<tr><th class=\'ui-widget-header ui-state-default\'>D&eacute;but";
                            html += "    <td class=\'ui-widget-content\'>"+datedeb;
                            html += "<tr><th class=\'ui-widget-header ui-state-default\'>Fin";
                            html += "    <td class=\'ui-widget-content\'>"+datefin;
                            html += "<tr><th class=\'ui-widget-header ui-state-default\'>Auteur";
                            html += "    <td class=\'ui-widget-content\'>"+author;
                            html += "<tr><th class=\'ui-widget-header ui-state-default\'>Imputer &agrave;";
                            html += "    <td class=\'ui-widget-content\'>"+imput;
                            html += "<tr><th class=\'ui-widget-header ui-state-default\' colspan=2>";
                            html += "    <button style=\"padding: 5px 10px; width: 170px;\" class=\"ui-widget-header ui-state-default ui-corner-all butAction\" onClick=\"initDelButton()\" id=\"effaceResa\">Annuler la r&eacute;servation</button>";
                    });
                    html += "</table>";
                    jQuery("#infoResa").replaceWith("<div id=\'infoResa\'>"+html+"</div>");
                }
            }
        });
    });
    jQuery(\'#effaceDialog\').dialog({
        autoOpen: false,
        width: 560,
        maxWidth: 560,
        minWidth: 560,
        modal: true,
        title: \'Effacer une r&eacute;servation\',
        buttons:{
            \'OK\':function() {
                    var ressourceId='.$ressourceId.';
                    jQuery.ajax({
                        url: "ajax/resa_ajax.php",
                        datatype:"xml",
                        data:"action=del&ressourceId="+ressourceId+"&resaId="+resaId,
                        type:"POST",
                        async:false,
                        cache: false,
                        success: function(msg){
                            if (jQuery(msg).find("OK").length > 0)
                            {
                                jQuery(\'#effaceDialog\').dialog(\'close\');
                                location.href="resa.php?ressource_id="+ressourceId;
                            }

                        }
                    });
                },
             \'Annuler\': function (){
                jQuery(\'#effaceDialog\').dialog(\'close\');
            }
        },
    });
});
function initDelButton()
{
    jQuery(\'#effaceResa\').click(function(){
        jQuery(\'#effaceDialog\').dialog(\'open\');
    });

}
var taskId=false;
function setTask(pTaskId,obj){
    if (jQuery(obj).hasClass("ui-state-highlight"))
    {
        taskId=false;
        jQuery("#treeTask").find("a.ui-state-highlight").removeClass("ui-state-highlight");
    } else {
        taskId = pTaskId
        jQuery("#treeTask").find("a.ui-state-highlight").removeClass("ui-state-highlight");
        jQuery(obj).addClass("ui-state-highlight");
    }
}
</script>
';
// Security check
//    if ($user->societe_id) $socid=$user->societe_id;
//    $result = restrictedArea($user, 'societe', $socid);
    // Initialisation de l'objet Societe

    if (! ( $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa) || $ressourceId."x" == "x")
    {
        accessforbidden();
    }

    //Affiche le calendrier zimbra de la ressource

    $ressource = new Ressource($db);
    $ressource->fetch($ressourceId);
    $get = "?action=iframe";
    foreach($_GET as $key=>$val)
    {
        $get.= "&".$key."=".$val;
    }
    llxHeader($js,$langs->trans("Ressources"),"Ressources","1");
    print '<div class="tabBar">';
    print "<table style='width: 1100px; height: 800px;' cellpadding=15 ><tr><td valign=top>";
    echo '<IFRAME id="iframe" style="width: 800px; height: 800px;"  SRC="iframe.php'.$get.'">';
    echo '</IFRAME>';
    print "</td>";
    print "<td valign=top style='width:400px'>";
    print "<table cellpadding=15 style='background-color: #ffffff; color: black; border-left:2px solid #004081; border-bottom:2px solid #B2D5F8; border-right:2px solid #B2D5F8; border-top:2px solid #004081;'><tr><td>";
    print "<form name='addresaForm' id='addresaForm'>";
    print "<FieldSet class='ui-corner-all' >";
    print "<legend>Nouvelle R&eacute;servation</legend>";
    print "<table width=400 cellpadding=10 ><tbody>";
    print "<tr><td class='ui-widget-header ui-state-default' width=90><em>*</em> ";
    print "Date de d&eacute;but";
    print "</td><td class='ui-widget-content' >";
    print "<input class='datePicker' name='dateDeb' id='dateDeb' style='width: 250px'></input>";
    print "</tr>";
    print "<tr><td class='ui-widget-header ui-state-default' ><em>*</em> ";
    print "Date de fin";
    print "</td><td class='ui-widget-content' >";
    print "<input class='datePicker' name='dateFin' id='dateFin' style='width: 250px'></input>";
    print "</tr>";
    print "<tr><td class='ui-widget-header ui-state-default' ><em>*</em> ";
    print "Imputer &agrave;";
    print "</td><td class='ui-widget-content'>";
    print "<SELECT name='imputation' id='imputation'  style='width: 250px'>";
    print "<option value=''>S&eacute;lection -></option>";
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user where statut = 1";
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        print "<option value='".$res->rowid."'>".$res->firstname . " ". $res->name."</option>";
    }
    print "</SELECT>";
    print "</tr>";
    print "<tr><td class='ui-widget-header ui-state-default' >";
    print "Projets";
    print "</td><td class='ui-widget-content'>";
    if ($_REQUEST['project_id']."x" =="x")
    {
//    print "<input class='imputation' name='imputation' style='width: 250px'></input>";
        print "<SELECT class='imputationProj' name='imputationProj' id='imputationProj' style='width: 250px'>";
        print "<option>S&eacute;lection -></option>";
        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_view.title,
                           ".MAIN_DB_PREFIX."Synopsis_projet_view.rowid,
                           ".MAIN_DB_PREFIX."societe.nom
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_view,
                           ".MAIN_DB_PREFIX."societe
                     WHERE fk_statut = 0
                       AND ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_view.fk_soc
                  ORDER BY ".MAIN_DB_PREFIX."societe.nom";
        $sql = $db->query($requete);
        $rem ="";
        $notInit=false;
        while ($res = $db->fetch_object($sql))
        {
            if ($rem != $res->nom)
            {
                if ($notInit)
                {
                    print "</optgroup>";
                }
                print "<optgroup label='".$res->nom."'>";
                $notInit=true;
            }
            $rem = $res->nom;
            print "<option value='".$res->rowid."'>&nbsp;&nbsp;".$res->title."</option>";
        }
        print "</optgroup>";
    } else {
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
        $proj= new Project($db);
        $proj->fetch($_REQUEST['project_id']);
        print $proj->getNomUrl(1);
        print "<input type='hidden' id='imputation'  name='imputation' value='".$_REQUEST['project_id']."' ></input>";
    }
    print "</tr></tbody>";
    print "<tfoot><tr><td  class='ui-widget-header ui-state-default'  style='text-align: right; padding-right: 40px;' colspan=2>";
    print "<input type='button' class='butAction' id='addresa' value='R&eacute;server'></button>";
    print "</td></tr></tfoot>";
    print "</table>";
    print "</form>";
    print "</FieldSet>";
    print "</td></tr><tr><td>";

    print "<FieldSet class='ui-corner-all' style='width:400px;'>";
    print "<legend>Modifier/Effacer une r&eacute;servation</legend>";
    //Resa
    print "<table cellpadding=10  width=400>";
    print "<tr><td class='ui-widget-header ui-state-default'  width=90 nowrap>R&eacute;servation : ";
    print "</td>";
    print "<td class='ui-widget-content'><SELECT name='modDelResa' id='modDelResa'><OPTION>S&eacute;lection -></OPTION> ";
    //mod / delete
    //Modif switch to SQL
    $requete = "SELECT unix_timestamp(datedeb) as db, unix_timestamp(datefin) as df, zimbraId, fk_user_author  FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa WHERE fk_ressource = ".$ressourceId;
    $sql = $db->query($requete);
    $atleastone = false;
    $atleasttwo = false;
    $gr1 = $gr2 = $gr3 = "";
    while ($res = $db->fetch_object($sql))
    {
        if ($res->fk_user_author == $user->id)
        {
            $gr1 .= "<option value='".$res->zimbraId ."'>Du ".date('d/m/Y H:i',$res->db)." Au ".date('d/m/Y H:i',$res->df)."</option>";
            $atleastone=true;
        } else if ($res->fk_user_imputation == $user->id)
        {
            $gr3 .= "<option value='".$res->zimbraId ."'>Du ".date('d/m/Y H:i',$res->db)." Au ".date('d/m/Y H:i',$res->df)."</option>";
            $atleasttwo=true;
        } else {
            $gr2 .= "<option value='".$res->zimbraId ."'>Du ".date('d/m/Y H:i',$res->db)." Au ".date('d/m/Y H:i',$res->df)."</option>";
        }
    }
    if ($atleastone)
    {
        print "<optgroup label='Mes resa'>";
        print $gr1;
        print "</optgroup>";
    }
    if ($atleasttwo)
    {
        print "<optgroup label='Mes resa'>";
        print $gr3  ;
        print "</optgroup>";
    }
    if ($atleastone || $atleasttwo){
        print "<optgroup label='Autres Resa'>";
    }
        print $gr2;
    if ($atleastone || $atleasttwo){
        print "</optgroup>";
    }
    print "</SELECT>";
    print "</td>";
    print "</tr>";

    print "</table>";
    print "<tr>";
    //Panel Info
    print "<td><FieldSet class='ui-corner-all' style='width:400px;  '>";
    print "<legend>Informations r&eacute;servation</legend>";

    print "<div id='infoResa'></div></fieldSet></th>";
    print "</tr>";

    print "</FieldSet>";
    print "</table>";


    print "</div>"; // end tabBar

print "<div id='effaceDialog'><br/><p>&Ecirc;tes vous sur de vouloir annuler cette r&eacute;servation ?</p></div>";



llxFooter('$Date: 2008/04/28 22:34:41 $ - $Revision: 1.11 $');
?>
