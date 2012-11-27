<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : demandeInterv-html_response.php
  * GLE-1.2
  */

  //liste les demandes d'interv et le statut


    require_once('../../main.inc.php');

  $id = $_REQUEST['id'];
  $DiId = $_REQUEST['diId'];
  require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
  require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
  $com = new Synopsis_Commande($db);
  $html = new Form($db);
  $res=$com->fetch($id);

  $arrGrpCom = array($id=>$id);
  $arrGrp = $com->listGroupMember(true);
  if($arrGrp && count($arrGrp) > 0)
  foreach($arrGrp as $key=>$commandeMember)
  {
        $arrGrpCom[$commandeMember->id]=$commandeMember->id;
  }


  if ($res>0)
  {
    $com->fetch_group_lines(0,0,0,0,1);

    $requete="SELECT fk_user_target, fk_user_prisencharge , datei, duree, description, fk_statut, note_private, note_public, rowid
                FROM llx_Synopsis_demandeInterv
               WHERE fk_commande IN (".join(',',$arrGrpCom).")";
    if ($DiId>0)
    {
        $requete .= " AND llx_Synopsis_demandeInterv.rowid = ".$DiId;
    }
    $requete .= "
            ORDER BY fk_user_prisencharge, fk_user_target, datei DESC";
//            print $requete;
    $sql=$db->query($requete);
    $rem = -10;
    $num = $db->num_rows($sql);
    while($res=$db->fetch_object($sql))
    {
        $di = new DemandeInterv($db);
        $di->fetch($res->rowid);
//        $rem = $res->fk_user_prisencharge;
        $tmpUser = new User($db);
        print "<table width=900>";
        if ($res->fk_user_prisencharge > 0)
        {
            $tmpUser->fetch($res->fk_user_prisencharge);
            if ($rem!=$res->fk_user_prisencharge)
            {
                print "<tr><td colspan=8>&nbsp;";
                print "<tr><th class='ui-widget-header ui-state-default' colspan=8 valign=center style='font-size:125%;line-height: 2em'>Attribu&eacute; &agrave; ".utf8_encode($tmpUser->getNomUrl(1));
                print "<tr><th class='ui-widget-header ui-state-default'>&nbsp;
                           <th class='ui-widget-header ui-state-default'>Ref.
                           <th class='ui-widget-header ui-state-default'>Effectu&eacute; par
                           <th class='ui-widget-header ui-state-default'>Date
                           <th class='ui-widget-header ui-state-default'>Dur&eacute;e totale
                           <th class='ui-widget-header ui-state-default'>Statut
                           <th class='ui-widget-header ui-state-default'>F. Interv.";
                if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
                {
                    print "    <th class='ui-widget-header ui-state-default'>Action";
                }
            }
            $rem=$res->fk_user_prisencharge;
        } else {
            if ($rem != -1)
            {
                print "<tr><th class='ui-widget-header ui-state-default' colspan=8 valign=center style='font-size:125%; line-height: 2em'>Non Attribu&eacute;";
                print "<tr><th class='ui-widget-header ui-state-default'>&nbsp;
                           <th class='ui-widget-header ui-state-default'>Ref.
                           <th class='ui-widget-header ui-state-default'>Effectu&eacute; par
                           <th class='ui-widget-header ui-state-default'>Date
                           <th class='ui-widget-header ui-state-default'>Dur&eacute;e totale
                           <th class='ui-widget-header ui-state-default'>Statut
                           <th class='ui-widget-header ui-state-default'>F. Interv.";
                if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
                {
                    print "    <th class='ui-widget-header ui-state-default'>Action";
                }
            }
            $rem=-1;
        }
        print "<tr><td valign=top width=30 class='ui-widget-content'><span class='ui-widget-header'><span id='displayDIDet-".$res->rowid."' class='displayDIDet ui-icon ui-icon-circle-triangle-e'></span></span>";
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=130 class='ui-widget-content' >".$di->getNomUrl(1);
        if ($res->fk_user_target > 0)
        {
            $tmpUser->id=$res->fk_user_target;
            $tmpUser->fetch();
            print "<td style='padding:15px 10px 10px 10px;' valign=top width=100 class='ui-widget-content'>".utf8_encode($tmpUser->getNomUrl(1));
        } else {
            print "<td style='padding:15px 10px 10px 10px;' valign=top width=100 class='ui-widget-content'> - ";
        }
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=70 class='ui-widget-content'>".($res->datei>0?date('d/m/Y',strtotime($res->datei)):"");
        $tmpDur = convDur($res->duree);
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=80 class='ui-widget-content'>".$tmpDur['hours']['abs']."h".$tmpDur['minutes']['rel'];
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=80 class='ui-widget-content'> ".utf8_decode($di->getLibStatut(4));
        $fiStr = "";
        $tabFI = $di->getFI();
        foreach($tabFI as $idFI){
            $fi = new Fichinter($db);
            $fi->fetch($idFI);
            $fiStr .= $fi->getNomUrl(1)."<br/>";            
        }
//        $requete1 = "SELECT * FROM Babel_li_interv WHERE di_refid = ".$res->rowid;
//        $sql1 = $db->query($requete1);
//        while ($res1=$db->fetch_object($sql1))
//        {
//            $fi = new Fichinter($db);
//            $fi->fetch($res1->fi_refid);
//            $fiStr .= $fi->getNomUrl(1)."<br/>";
//
//        }
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=100 class='ui-widget-content'> ".$fiStr;
        if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
        {

            print "    <td style='padding:15px 10px 10px 10px;' align=center valign=top width=230 class='ui-widget-content'> ";
            if ($res->fk_user_target > 0)
            {
                print "<button id='rempIDI-".$res->rowid."' class='rempIDI butAction ui-widget-header ui-state-default'>Remp. Interv.</button>";
            } else {
                print "<button id='attribDI-".$res->rowid."' class='attribDI butAction ui-widget-header ui-state-default'>Attr &agrave;</button>";
            }
            print "<button id='modDI-".$res->rowid."' class='modDI butAction ui-widget-header ui-state-default'>Modifier</button>";
            print "<button id='cloneDI-".$res->rowid."' class='cloneDI butAction ui-widget-header ui-state-default'>Cloner</button>";
        }
        print "</table>";
        print "<table width=900>";
    }
    if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
    {
        if ($num==0){ print "<table cellpadding=10>"; }
    }
        print "</table>";
        print "<div id='addDIDial' class='cntAddDIDial'>";
        print "<form id='formAddDI'>";
        print "<table cellpadding=10 width=100%><tr><th class='ui-wiget-header ui-state-default'>Intervenant<td class='ui-widget-content'>";
        utf8_encode($html->select_users(0,'userid',1));
        print "       <tr><th class='ui-widget-header ui-state-default'>Date<td class='ui-widget-content'><input class='datei' name='datei' id='datei'>";
        print "       <tr><th class='ui-widget-header ui-state-default'>Lier &agrave;<td class='ui-widget-content'><select name='comLigneId' id='comLigneId'>";
        print "<option SELECTED value='-1'>S&eacute;lection -></option>";
        foreach($com->lines as $key=>$val)
        {
            print "<option value='".$val->id."'>".utf8_encode($val->ref." ".$val->libelle." (". price($val->total_ht)) ."&euro;)"."</option>";
        }
        print "</select>";
        print "</table>";
        print "</form>";
        print "</div>";

        print "<div id='attrDIDial' class='cntAttrDIDial'>";
        print "<form id='formAttrDI'>";
        print "<table cellpadding=10 width=100%><tr><th class='ui-wiget-header ui-state-default'>Intervenant<td class='ui-widget-content'>";
        $html->select_users(0,'attruserid',1,'',0,false);
        print utf8_encode($html->tmpReturn);
        print "</table>";
        print "</form>";
        print "</div>";

        print "<div id='rempDIDial' class='cntRempDIDial'>";
        print "<form id='formRempDI'>";
        print "<table cellpadding=10 width=100%><tr><th class='ui-wiget-header ui-state-default'>Intervenant<td class='ui-widget-content'>";
        $html->select_users(0,'rempuserid',1,'',0,false);
        print utf8_encode($html->tmpReturn);
        print "</table>";
        print "</form>";
        print "</div>";
  }


  print <<<EOF
  <style>#ui-datepicker-div{ z-index: 10000; }</style>
  <script>
    var RemDI = false;
    var RemStatutArr=new Array();
function cloneDi(id){
    jQuery.ajax({
        url:'ajax/xml/cloneDI-xml_response.php',
        data:"id="+id,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length>0)
            {
                //reload
                reloadResult();
                if (jQuery('.cntAddDIDial').length>1){
                    jQuery('#addDIDial').dialog( "destroy" );
                    jQuery('#addDIDial').remove();
                }
                if (jQuery('.cntAttrDIDial').length>1){
                    jQuery('#attrDIDial').dialog( "destroy" );
                    jQuery('#attrDIDial').remove();
                }
                if (jQuery('.cntRempDIDial').length>1){
                    jQuery('#rempDIDial').dialog( "destroy" );
                    jQuery('#rempDIDial').remove();
                }

                jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                jQuery.ajax({
                    url: "ajax/demandeInterv-html_response.php",
                    data: "id="+comId,
                    cache: false,
                    datatype: "html",
                    type: "POST",
                    success: function(msg){
                        jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                    },
                });
            } else {
                alert('Il y a eu une erreur');
            }
        }
    });
}
    jQuery(document).ready(function(){
        if (jQuery('.cntAddDIDial').length>1){
            jQuery('#addDIDial').dialog( "destroy" );
            jQuery('#addDIDial').remove();
        }
        if (jQuery('.cntAttrDIDial').length>1){
            jQuery('#attrDIDial').dialog( "destroy" );
            jQuery('#attrDIDial').remove();
        }
        if (jQuery('.cntRempDIDial').length>1){
            jQuery('#rempDIDial').dialog( "destroy" );
            jQuery('#rempDIDial').remove();
        }

        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: false,
                    duration: '',
                    constrainInput: false,
                    }, jQuery.datepicker.regional['fr'])
                    );
        jQuery('.datei').datepicker();
        jQuery.validator.addMethod(
                        "FRDateNoReq",
                        function(value, element) {
                            if (value+"x"=="x")
                            {
                                return true;
                            }
                            // put your own logic here, this is just a (crappy) example
                            return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                        },
                        "La date doit &ecirc;tre au format dd/mm/yyyy hh:mm"
                    );
        jQuery.validator.addMethod(
                        "sup0",
                        function(value, element) {
                            // put your own logic here, this is just a (crappy) example
                            return value>0;
                        },
                        "Ce champ est requis"
                    );


        jQuery('#addDIDial').dialog({
            modal: true,
            autoOpen: false,
            title: "Demande d'intervention",
            minWidth: 540,
            width: 540,
            buttons: {
                OK: function(){
                    if (jQuery('#formAddDI').validate({rules: {
                                                    userid: {
                                                        required: true,
                                                    },
                                                    datei: {
                                                        FRDateNoReq: true,
                                                        required: false
                                                    },
                                                    comLigneId: {
                                                        required: true,
                                                        sup0: true,
                                                    },
                                                },
                                                messages: {
                                                    userid: {
                                                      required: "<br>Champ requis"
                                                    },
                                                    datei: {
                                                      FRDate: "<br>Le format de la date est inconnu",
                                                    },
                                                    comLigneId: {
                                                        required: "<br>Champ requis",
                                                    },
                                                }
                    }).form()){
                        var datei=jQuery('#datei').val();
                        var userid = jQuery('#userid').find(':selected').val();
                        var comLigneId = jQuery('#comLigneId').find(':selected').val();
                        location.href=DOL_URL_ROOT+'/Synopsis_DemandeInterv/fiche.php?action=create&datei='+datei+"&comLigneId="+comLigneId+"&userid="+userid;
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function()
            {

            }
        });

        jQuery('#attrDIDial').dialog({
            modal: true,
            autoOpen: false,
            title: "Attribution des demandes d'intervention",
            minWidth: 540,
            width: 540,
            buttons: {
                OK: function(){
                    if (jQuery('#formAttrDI').validate({rules: {
                                                    attruserid: {
                                                        required: true,
                                                    },
                                                },
                                                messages: {
                                                    attruserid: {
                                                      required: "<br>Champ requis"
                                                    },
                                                }
                    }).form()){
                        var userid = jQuery('#attruserid :selected').val();
                        if (userid > 0){
                            var DIid = RemDI;
                            var data = 'userId='+userid+"&DIid="+DIid;
                            var self = this;
                            jQuery.ajax({
                                url:"ajax/xml/attrDI-xml_response.php",
                                cache:false,
                                type:"POST",
                                data:data,
                                datatype:"xml",
                                success:function(msg){
                                    jQuery(self).dialog('close');
//reload
                                    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                    jQuery('#attrDIDial').dialog( "destroy" );
                                    jQuery('#attrDIDial').remove();
                                    jQuery.ajax({
                                        url: "ajax/demandeInterv-html_response.php",
                                        data: "id="+comId,
                                        cache: false,
                                        datatype: "html",
                                        type: "POST",
                                        success: function(msg){
                                            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                        },
                                    });

                                }
                            });
                        }
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function(){
            }
        });
        jQuery('#rempDIDial').dialog({
            modal: true,
            autoOpen: false,
            title: "Remplacement intervenant - demandes d'intervention",
            minWidth: 540,
            width: 540,
            buttons: {
                OK: function(){
                    if (jQuery('#formRempDI').validate({rules: {
                                                    rempuserid: {
                                                        required: true,
                                                    },
                                                },
                                                messages: {
                                                    rempuserid: {
                                                      required: "<br>Champ requis"
                                                    },
                                                }
                    }).form()){
                        var userid = jQuery('#rempuserid').find(':selected').val();
                        var DIid = RemDI;
                        var data = 'userId='+userid+"&DIid="+DIid;
                        var self = this;
                        jQuery.ajax({
                            url:"ajax/xml/rempDI-xml_response.php",
                            cache:false,
                            type:"POST",
                            data:data,
                            datatype:"xml",
                            success:function(msg){
                                jQuery(self).dialog('close');
                            }
                        });
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function(){
            }
        });
        jQuery('#addDI').click(function(){
            jQuery('#addDIDial').dialog('open');
        });
        jQuery('.rempIDI').click(function(){
            var id = jQuery(this).attr('id').replace(/^rempIDI-/,"");
            RemDI = id;
            jQuery('#rempDIDial').dialog('open');

        });
        jQuery('.attribDI').click(function(){
            var id = jQuery(this).attr('id').replace(/^attribDI-/,"");
            //Afficher dialog avec liste intervenant
            RemDI = id;
            jQuery('#attrDIDial').dialog('open');
            //Choisir nouvel intervenant
            //Valider ajax modif

        });
        jQuery('.modDI').click(function(){
            var id = jQuery(this).attr('id').replace(/^modDI-/,"");
            location.href=DOL_URL_ROOT+'/Synopsis_DemandeInterv/fiche.php?id='+id;
        });
        jQuery('.cloneDI').click(function(){
            var id = jQuery(this).attr('id').replace(/^cloneDI-/,"");
            cloneDi(id);
        });


        jQuery(".displayDIDet").click(function(){
            var self =this;
            var id = jQuery(this).attr('id').replace(/^displayDIDet-/,"")
            if (RemStatutArr[id]==0)
            {
                RemStatutArr[id]=1;
                jQuery(this).removeClass("ui-icon-circle-triangle-s");
                jQuery(this).addClass("ui-icon-circle-triangle-e");
                jQuery.ajax({
                    url: "ajax/xml/DetDInter-xml_response.php",
                    datatype: 'xml',
                    type: 'POST',
                    data: 'id='+id,
                    cache: true,
                    success: function(msg){
                        //console.log("close");
                        jQuery('.detToRem'+id).remove();
                    }
                });
            } else {
                RemStatutArr[id]=0;
                jQuery(this).removeClass("ui-icon-circle-triangle-e");
                jQuery(this).addClass("ui-icon-circle-triangle-s");
                jQuery.ajax({
                    url: "ajax/xml/DetDInter-xml_response.php",
                    datatype: 'xml',
                    type: 'POST',
                    data: 'id='+id,
                    cache: true,
                    success: function(msg){
                        var longHtml = "<tr class='detToRem"+id+"'><td>&nbsp;";
                            longHtml += "    <th class='ui-widget-header ui-state-hover'>Date";
                            longHtml += "    <th class='ui-widget-header ui-state-hover'>Dur&eacute;e";
                            longHtml += "    <th class='ui-widget-header ui-state-hover'>Total HT";
                            longHtml += "    <th class='ui-widget-header ui-state-hover'>Type";
                            longHtml += "    <th colspan=5 class='ui-widget-header ui-state-hover'>Description";
                        jQuery(msg).find('DI').each(function(){
                            var rowid = jQuery(this).find('rowid').text();
                            var datei = jQuery(this).find('date').text();
                            var description = jQuery(this).find('description').text();
                            var total_ht = jQuery(this).find('total_ht').text();
                            var type = jQuery(this).find('type').text();
                            var duree = jQuery(this).find('duree').text();
                            var rang = jQuery(this).find('rang').text();

                            longHtml += "<tr class='detToRem"+id+"'>"
                                            +  "<td width=30>&nbsp;<td class='ui-widget-content ui-state-default ' align=center>"+datei
                                            +  "<td class='ui-widget-content ui-state-default ' align=center>"+duree
                                            +  "<td class='ui-widget-content ui-state-default ' colspan=1>"+total_ht+" &euro;"
                                            +  "<td class='ui-widget-content ui-state-default ' colspan=1>"+type
                                            +  "<td class='ui-widget-content ui-state-default ' colspan=5>"+description;
                        });
                        jQuery(self).parent().parent().parent().parent().append(longHtml);
                    }
                });
            }
        })
    });
  </script>

EOF;


function convDur($duration)
{

    // Initialisation
    $duration = abs($duration);
    $converted_duration = array();

    // Conversion en semaines
    $converted_duration['weeks']['abs'] = floor($duration / (60*60*24*7));
    $modulus = $duration % (60*60*24*7);

    // Conversion en jours
    $converted_duration['days']['abs'] = floor($duration / (60*60*24));
    $converted_duration['days']['rel'] = floor($modulus / (60*60*24));
    $modulus = $modulus % (60*60*24);

    // Conversion en heures
    $converted_duration['hours']['abs'] = floor($duration / (60*60));
    $converted_duration['hours']['rel'] = floor($modulus / (60*60));
    if ($converted_duration['hours']['rel'] <10){$converted_duration['hours']['rel'] ="0".$converted_duration['hours']['rel']; } ;
    $modulus = $modulus % (60*60);

    // Conversion en minutes
    $converted_duration['minutes']['abs'] = floor($duration / 60);
    $converted_duration['minutes']['rel'] = floor($modulus / 60);
    if ($converted_duration['minutes']['rel'] <10){$converted_duration['minutes']['rel'] ="0".$converted_duration['minutes']['rel']; } ;
    $modulus = $modulus % 60;

    // Conversion en secondes
    $converted_duration['seconds']['abs'] = $duration;
    $converted_duration['seconds']['rel'] = $modulus;

    // Affichage
    return( $converted_duration);
}

?>
