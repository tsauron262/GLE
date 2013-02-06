<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 25 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.1
  */

  require_once('pre.inc.php');
  //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@Synopsis_Tools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

//if (! ($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa))
//{
//    accessforbidden();
//}
// Initialisation de l'objet Societe
$soc = new Societe($db);
$soc->fetch($socid);



$jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
$jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
$css = DOL_URL_ROOT."/Synopsis_Common/css";
$imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

$js .= "<script type='text/javascript' src='".$jspath."/jquery.treeview.min.js'></script>";
$js .= "<script type='text/javascript' src='".$jspath."/jquery.jeditable.js'></script>";
$js .= "<link rel='stylesheet' href='".$css."/jquery.treeview.css'></link>";

$js .= "<style type='text/css'>body { position: static; }  #sidetree{ max-width: 30%; max-height: 450px; float: left; margin-left: 30px; margin-right: 50px; } #fragment-1 { min-height: 500px;}  } </style>";
$js .= ' <script>
  jQuery(document).ready(function() {
    jQuery("#tabs").tabs();
    jQuery("#tree").treeview({
                collapsed: false,
                animated: "medium",
                control:"#sidetreecontrol",
                prerendered: true,
            });
  });

function showPlaquette(plaquId)
{
//Affiche le cartouche document de la plaquette + Apercu si imagick dispo
    jQuery.ajax({
        url: "ajax/infoPlaq-xmlresponse.php",
        data: "id="+plaquId,
        datatype:"xml",
        type: "POST",
        success: function(msg)
        {
            var filename = jQuery(msg).find("filename").text();
            var filelabel = jQuery(msg).find("filelabel").text();
            var filesize = jQuery(msg).find("filesize").text();
            var filemime = jQuery(msg).find("filemime").text();
            var fileurl = jQuery(msg).find("fileurl").text();
            longHtml = "<table cellpadding=10><tr><th class=\'ui-widget-header ui-state-default\' colspan=2><span class=\'edit_label\'>"+filelabel+"</span></th>";
            longHtml += "      <tr><td class=\'ui-widget-header ui-state-default\' >Nom du fichier</td><td class=\'ui-widget-content\'>"+filename+"</td>";
            longHtml += "      <tr><td class=\'ui-widget-header ui-state-default\' >Taille</td><td class=\'ui-widget-content\'>"+filesize+"</td>";
            longHtml += "      <tr><td class=\'ui-widget-header ui-state-default\' >Type</td><td class=\'ui-widget-content\'>"+filemime+"</td>";
            longHtml += "      <tr><td class=\'ui-widget-header ui-state-default\' >URL</td><td class=\'ui-widget-content\' >"+fileurl+"</td>";
            jQuery("#sidetree").parent().find("#detailDiv").remove();
            jQuery("#sidetree").parent().append("<div id=\"detailDiv\" style=\"float: left; margin-left: 5px; \">"+longHtml+"</div>");
            initEditLabel(plaquId);
        }
    });
}

';
$js .=<<<EOF
    function initEditLabel(plaquId)
    {
        jQuery('.edit_label').editable('ajax/labelPlaq-xmlresponse.php', {
                 cancel    : 'Annuler',
                 submit    : 'OK',
                 indicator : '<img src="img/ajax-loader.gif">',
                 tooltip   : 'Editer',
                 placeholder : 'Cliquer pour &eacute;diter',
                 onblur : 'cancel',
                 width: '120px',
                 submitdata : {id: plaquId},
                 data : function(value, settings)
                 {
                      var retval = value; //Global var
                      if (retval == html_entity_decode(settings.placeholder))
                      {
                            retval ='';
                      }
                      return retval;
                 },
             });
    }
    function html_entity_decode(str) {
          var ta=document.createElement("textarea");
          ta.innerHTML=str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
          return ta.value;
    }


EOF;
$js .= '
</script>';

llxHeader($js,'Envoie de plaquette',true);
?>
<div id="tabs">
    <ul>
        <li><a href="#fragment-1"><span>Plaquette</span></a></li>
        <li><a href="#fragment-2"><span>Mod&egrave;le de mail</span></a></li>
        <li><a href="#fragment-3"><span>Historique</span></a></li>
    </ul>
    <div id="fragment-1">
<?php
//TreeView des plaquettes :>
$requete = "SELECT ".MAIN_DB_PREFIX."ecm_document.filename,
                   ".MAIN_DB_PREFIX."ecm_document.rowid as id
              FROM ".MAIN_DB_PREFIX."ecm_document ,
                   ".MAIN_DB_PREFIX."ecm_directories
             WHERE ".MAIN_DB_PREFIX."ecm_document.fk_directory = ".MAIN_DB_PREFIX."ecm_directories.rowid
               AND ".MAIN_DB_PREFIX."ecm_directories.label = 'Plaquettes'";
$sql = $db->query($requete);
print '<div id="sidetree">';
print '  <div class="treeheader ui-corner-top ui-widget-header ui-state-default" style="padding: 5px;">&nbsp;</div>';

print '  <ul class="treeview ui-widget-content" id="tree" style="padding: 10px; overflow-x: hidden; overflow-y: hidden;">';
print '    <li class="collapsable last"><div class="hitarea collapsable-hitarea"></div><strong>Plaquettes</strong>';
print '    <ul style="display: block;">';
$count=$db->num_rows($sql);
$iter=0;
while ($res=$db->fetch_object($sql))
{
    $iter++;
    $extra="";
    if ($iter == $count) { $extra = 'last';}
    print '        <li class="'.$extra.'"><a href="javascript:showPlaquette('.$res->id.')">'.$res->filename.'</a></li>';
}
print '    </ul></li>';
print '    </ul>';
print '  <div id="sidetreecontrol" class="ui-corner-bottom ui-widget-content ui-state-default" style="font-size: 75%; padding: 10px; text-align: center;"> <a href="?#">R&eacute;duire tout</a> | <a href="?#">Etendre tout</a> </div>';
print ' </div>';
?>
    </div>
    <div id="fragment-2">
<?php
    print "<table><tr>";
    print "<td><select id='chooseModele'><option value='-1'>S&eacute;lection-></option><option value='1'>Modele 1</option><option value='2'>Modele 2</option></select>";
    print "<td><button style='padding: 5px 10px;' id='chooseModeleButton' class='butAction ui-state-default ui-widget-header ui-corner-all'><span style='float:left; margin-right: 3px; margin-top: -2px;' class='ui-icon ui-icon-mail-closed'></span>Afficher</button>";
    print "</table>";
    print "<div class='subfiche'>";
    print "<div id='content'>";
    print "</div>";
    print "</div>";
    print "<script>";
    print "var DOL_URL_ROOT='".DOL_URL_ROOT."';";
    print "var myemail='".$user->email."';";
    print "var fckParam='AutoDetectLanguage=true";
    print "&ToolbarLocation=In&ToolbarStartExpanded=true";
    print "&UserFilesPath=/viewimage.php?modulepart%3Dfckeditor%26file%3D";
    print "&UserFilesAbsolutePath=".DOL_DOCUMENT_ROOT."/documents/fckeditor/";
    print "&LinkBrowser=true&ImageBrowser=true";
    print "&CustomConfigurationsPath=".DOL_URL_ROOT."/theme/babel/fckeditor/fckconfig.js";
    print "&SkinPath=".DOL_URL_ROOT."/theme/babel/fckeditor/"."';";
    print <<<EOF
    jQuery(document).ready(function(){
        jQuery.validator.addMethod(
            'requiredNoBR',
            function(value, element) {
                return value.match(/^[\w\W\d]+$/);
            },
            '&nbsp;&nbsp;Ce champ est requis'
        );
        jQuery('#chooseModele').change(function(){
            changeModele();
        });
        jQuery('#chooseModeleButton').click(function(){
            changeModele();
        });

        jQuery('#helpButtonDialog').dialog({
            autoOpen: false,
            title: 'Aide',
            minWidth: 520,
            position: ["100","200"],
            width: 520,
            buttons:{
                "Ok": function() {
                    jQuery(this).dialog('close');
                },
            },
        });
    });

    function changeModele()
    {
        jQuery('div.subfiche').find('.ui-state-highlight').toggle('slide');
        if (jQuery('div.subfiche').find('.ui-state-highlight').parent())
        {
            jQuery('div.subfiche').find('.ui-state-highlight').parent().remove();
        }
        jQuery('div.subfiche').find('.ui-state-highlight').remove();
        var id = jQuery('#chooseModele').find(':selected').val();
        if (id > 0)
        {
            var longHtml = drawModeleMail(id);
            //console.log(longHtml);
            jQuery('#content').replaceWith('<div id="content">'+longHtml+'</div>');
            animateButton();
            jQuery('#testButton').unbind('click');
            jQuery('#testButton').click(testButton);
            jQuery('#saveButton').unbind('click');
            jQuery('#saveButton').click(function(){
                saveDatas();
            });
            jQuery('#helpButton').unbind('click');
            jQuery('#helpButton').click(function(){
                jQuery('#helpButtonDialog').dialog('open');
            })

        } else {
            jQuery('#content').replaceWith('<div id="content"></div>');
            animateButton();
        }
    }
    function animateButton(){
        jQuery(document).ready(function(){jQuery('.butAction').mouseover(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover');});jQuery('.butAction').mouseout(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default');});jQuery('.butAction-rev').mouseover(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default');});jQuery('.butAction-rev').mouseout(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover');});});
    }
    function saveDatas(noeffect)
    {

        var label = jQuery('#label').val();
        var id = jQuery('#modelId').val();
        var sujet = jQuery('#subject').val();
        var contentArr = get_selection_plain("modeleMail");
        var contentHTML = encodeURIComponent(contentArr['html']);
        var contentTXT = contentArr['txt'];
        jQuery.ajax({
            datatype: "xml",
            data: "label="+label+"&id="+id+"&content="+contentHTML+"&sujet="+sujet,
            type: 'POST',
            url: 'ajax/saveModel-xmlresponse.php',
            success:function(msg)
            {
                if (!noeffect)
                {
                    jQuery('div.subfiche').find('.ui-state-highlight').toggle('slide')
                    if (jQuery('div.subfiche').find('.ui-state-highlight').parent()) { jQuery('div.subfiche').find('.ui-state-highlight').parent().remove(); }
                    jQuery('div.subfiche').find('.ui-state-highlight').remove();
                    jQuery('div.subfiche').prepend('<div style="display: none; padding: 3px 10px;" class="ui-state-highlight"><span class="ui-icon ui-icon-info" style="margin-right: 3px; margin-top: -2px; float: left"></span>Enregistrement effectu&eacute;</div>');
                    jQuery('div.subfiche').find('.ui-state-highlight').toggle('slide')
                }
            }
        });
    }

    function testButton()
    {
        saveDatas(true);
        jQuery('#testForm').validate();
        jQuery('#formMail').toggle('slide',testButtonOnAfterSlide);
    }

    function testButtonOnAfterSlide()
    {
        jQuery('#sendTestButton').unbind('click');
        jQuery('#sendTestButton').click(testButtonClick);
    }
    function testButtonClick()
    {
        var dest = jQuery('#dest').val();
        var subject = jQuery('#sujet').val();
        var modelId = jQuery('#modelId').val();

        jQuery.ajax({
            datatype: "xml",
            data: "subject="+subject+"&to="+dest+"&id="+modelId,
            type: 'POST',
            async: false,
            url: 'ajax/sendMail.php',
            success:function(msg)
            {
                jQuery('div.subfiche').find('.ui-state-highlight').toggle('slide')
                jQuery('div.subfiche').find('.ui-state-highlight').parent().remove();
                jQuery('div.subfiche').find('.ui-state-highlight').remove();
                jQuery('div.subfiche').prepend('<div style="padding: 3px 10px; display:none;" class="ui-state-highlight"><span class="ui-icon ui-icon-info" style="margin-right: 3px; margin-top: -2px; float: left"></span>Envoi effectu&eacute;</div>');
                jQuery('div.subfiche').find('.ui-state-highlight').toggle('slide')
                jQuery('#formMail').toggle('slide');
                jQuery("#sendTestButton").unbind('click');
            }
        });
    }

    function get_selection_plain(instance_name) {

        var oFCKeditor = FCKeditorAPI.GetInstance(instance_name);
        var html = oFCKeditor.GetHTML();
        var div = document.createElement('div');
        var text = jQuery(div).append(html).text();

        var arr=new Array();
            arr['html']=html;
            arr['txt']=text;

        return arr;


    }


    function drawModeleMail(pId){
        var Model = new Array();
        var html = "";
        jQuery.ajax({
            async: false,
            cache: false,
            dataType: 'xml',
            data: 'action=list&id='+pId,
            type: 'POST',
            url: 'ajax/modelMail.php',
            success: function(msg){
                jQuery(msg).find('models').each(function(){
                    var label = jQuery(this).find('label').text();
                    var mailModel = jQuery(this).find('model').text();
                    var subject = jQuery(this).find('subject').text();
                    var id = jQuery(this).find('id').text();
                    Model[id]=new Array();
                    Model[id]['label']=label;
                    Model[id]['model']=mailModel;
                    Model[id]['subject']=subject;
                });
            },
        });

        html = "<div class='ui-corner-all'><H2 style='padding: 5px 10px; ' class='ui-widget-header ui-state-hover ui-corner-all'>"+Model[pId]['label']+"</H2></div>";
        //html += "<textarea id=''></textarea>";
        html += "<table cellpadding=10><tr><td>";
        html += "<button style='padding: 5px 10px;' id='testButton' class='butAction ui-state-default ui-widget-header ui-corner-all'> \
                    <span style='float:left; margin-right: 3px; margin-top: -2px;' class='ui-icon ui-icon-mail-open'> \
                    </span>Enregistrer & Tester \
                  </button>";
        html += "<td>";
        html += "<button style='padding: 5px 10px;' id='saveButton' class='butAction ui-state-default ui-widget-header ui-corner-all'> \
                    <span style='float:left; margin-right: 3px; margin-top: -2px;' class='ui-icon ui-icon-disk'> \
                    </span>Enregistrer \
                  </button>";
        html += "</table>";

        html += "<div id='formMail' style='display: none;'><fieldset><legend>Mail de test</legend><form id='testForm' onsubmit='return(false);'>";
        html += "<table><tr><td>Destinataire<td><input class='requiredNoBR email' name='dest' id='dest' value="+myemail+"></input>";
        html += "<tr><td>Sujet<td><input name='sujet' class='requiredNoBR' id='sujet' value='"+Model[pId]['subject']+"'></input>";
        html += "<tr><td colspan=2 align=center><button id='sendTestButton' style='padding: 5px 10px;' \
                         class='butAction ui-widget-header ui-corner-all ui-state-default'><span class='ui-icon ui-icon-mail-closed' style='float: left; margin-right: 3px; margin-top: -2px;'></span>Tester l'envoi</button>";
        html += "</table></form>";
        html += "</fieldset></div>";
        html += "<table cellpadding=10><tr><th class='ui-widget-header ui-state-default'>Label<td class='ui-widget-content'>";
        html += "<input type='text' name='label' id='label' value='"+Model[pId]['label']+"'></input>";
        html += "<tr><th class='ui-widget-header ui-state-default'>Sujet du mail<td class='ui-widget-content'>";
        html += "<input type='text' name='subject' id='subject' value='"+Model[pId]['subject']+"'></input>";
        html += "<input type='hidden' style='display:none;' id='modelId' value='"+pId+"'></input>";
        html += '</table>';

        html += '<br/>';
        html += "<button style='padding: 5px 10px;' id='helpButton' class='butAction ui-state-default ui-widget-header ui-corner-all'> \
                    <span style='float:left; margin-right: 3px; margin-top: -2px;' class='ui-icon ui-icon-help'> \
                    </span>Aide \
                  </button>";
        html += '<br/>';
        html += '<br/>';

        html += "<input type='hidden' style='display:none;' id='modeleMail__Config' name='modeleMail__Config' value='"+fckParam+"'></input>";
        html += '<input type="hidden" style="display:none;" name="modeleMail" id="modeleMail" value="'+Model[pId]["model"].replace(/"/,'\\"')+'"></input>';
        html += '<iframe id="modeleMail__Frame" src="'+DOL_URL_ROOT+'/includes/fckeditor/editor/fckeditor.html?InstanceName=modeleMail&Toolbar=Default" width="100%" height="280" frameborder="0" scrolling="no" ></iframe>';
        return(html);
    }
EOF;
    print "</script>";
?>
    <div id="helpButtonDialog">
        <table width=500>
            <tr><th class='ui-widget-header ui-state-default'>Genre du destinataire</th>
                <td class='ui-widget-content'>##GENRE_DEST##
                <td class='ui-widget-content'>MR
            <tr><th class='ui-widget-header ui-state-default'>Nom du destinataire</th>
                <td class='ui-widget-content'>##NOM_DEST##
                <td class='ui-widget-content'>Dupont
            <tr><th class='ui-widget-header ui-state-default'>Pr&eacute;om du destinataire</th>
                <td class='ui-widget-content'>##PRENOM_DEST##
                <td class='ui-widget-content'>Pierre
            <tr><th class='ui-widget-header ui-state-default'>Email du destinataire</th>
                <td class='ui-widget-content'>##EMAIL_DEST##
                <td class='ui-widget-content'>MR
            <tr><th class='ui-widget-header ui-state-default'>Label / Nom plaquette</th>
                <td class='ui-widget-content'>##LABEL##
                <td class='ui-widget-content'>Plaquette GLE
            <tr><th class='ui-widget-header ui-state-default'>Pr&eacute;nom</th>
                <td class='ui-widget-content'>##MON_PRENOM##
                <td class='ui-widget-content'><?php echo $user->prenom ?>
            <tr><th class='ui-widget-header ui-state-default'>Nom</th>
                <td class='ui-widget-content'>##MON_NOM##
                <td class='ui-widget-content'><?php echo $user->nom ?>
            <tr><th class='ui-widget-header ui-state-default'>Email</th>
                <td class='ui-widget-content'>##MON_EMAIL##
                <td class='ui-widget-content'><?php echo $user->email ?>
            <tr><th class='ui-widget-header ui-state-default'>T&eacute;l de bureau</th>
                <td class='ui-widget-content'>##MON_TELBUREAU##
                <td class='ui-widget-content'><?php echo $user->office_phone ?>
            <tr><th class='ui-widget-header ui-state-default'>Fax</th>
                <td class='ui-widget-content'>##MON_FAX##
                <td class='ui-widget-content'><?php echo $user->office_fax ?>
            <tr><th class='ui-widget-header ui-state-default'>Mobile</th>
                <td class='ui-widget-content'>##MON_MOBILE##
                <td class='ui-widget-content'><?php echo $user->user_mobile ?>

            <tr><th class='ui-widget-header ui-state-default'>Ma soci&eacute;t&eacute;</th>
                <td class='ui-widget-content'>##MA_SOCIETE_NOM##
                <td class='ui-widget-content'><?php echo $mysoc->nom ?>
            <tr><th class='ui-widget-header ui-state-default'>Email de ma soci&eacute;t&eacute;</th>
                <td class='ui-widget-content'>##MA_SOCIETE_EMAIL##
                <td class='ui-widget-content'><?php echo $mysoc->email ?>
            <tr><th class='ui-widget-header ui-state-default'>Tel de ma soci&eacute;t&eacute;</th>
                <td class='ui-widget-content'>##MA_SOCIETE_TEL##
                <td class='ui-widget-content'><?php echo $mysoc->tel ?>
            <tr><th class='ui-widget-header ui-state-default'>Fax de ma soci&eacute;t&eacute;</th>
                <td class='ui-widget-content'>##MA_SOCIETE_FAX##
                <td class='ui-widget-content'><?php echo $mysoc->fax ?>


         </table>
    </div>

    </div>
    <div id="fragment-3">
        Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
        Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
        Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.
    </div>
</div>

