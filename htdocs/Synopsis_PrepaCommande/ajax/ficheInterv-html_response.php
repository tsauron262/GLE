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
  $DiId = isset($_REQUEST['fiId'])? $_REQUEST['fiId'] : 0;
  require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
  require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
  require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
  $com = new Synopsis_Commande($db);
  $html = new Form($db);
  $res=$com->fetch($id);
    $arrGrpCom = array($com->id=>$com->id);
    $arrGrp = $com->listGroupMember(true);
    foreach($arrGrp as $key=>$commandeMember)
    {
        $arrGrpCom[$commandeMember->id]=$commandeMember->id;
    }


  if ($res>0)
  {
    $com->fetch_group_lines(0,0);

    $requete="SELECT fk_user_author, fk_user_valid , datei, duree, description, fk_statut, note_private, note_public, rowid
                FROM ".MAIN_DB_PREFIX."Synopsis_fichinter
               WHERE fk_commande IN (".join(",",$arrGrpCom).")";
    if ($DiId>0)
    {
        $requete .= " AND ".MAIN_DB_PREFIX."Synopsis_fichinter.rowid = ".$DiId;
    }
    $requete .= "
            ORDER BY fk_user_author, datei DESC";
//            print $requete;
    $sql=$db->query($requete);
    $rem = -10;
    while($res=$db->fetch_object($sql))
    {
        $fi = new FichInter($db);
        $fi->fetch($res->rowid);
//        $rem = $res->fk_user_prisencharge;
        $tmpUser = new User($db);
        print "<table width=100%>";
        if ($res->fk_user_author > 0)
        {
            $tmpUser->fetch($res->fk_user_author);
            if ($rem!=$res->fk_user_author)
            {
                print "<tr><td colspan=8>&nbsp;";
                print "<tr><th class='ui-widget-header ui-state-default' colspan=8 valign=center style='font-size:125%;line-height: 2em'>Effectu&eacute; par ". utf8_encodeRien($tmpUser->getNomUrl(1));
                print "<tr><th class='ui-widget-header ui-state-default'>&nbsp;
                           <th class='ui-widget-header ui-state-default'>Ref.
                           <th class='ui-widget-header ui-state-default'>Date
                           <th class='ui-widget-header ui-state-default'>Dur&eacute;e totale
                           <th class='ui-widget-header ui-state-default'>Statut
                           <th class='ui-widget-header ui-state-default'>F. Interv.";
                if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
                {
                    print "    <th class='ui-widget-header ui-state-default'>Action";
                }
            }
            $rem=$res->fk_user_author;
        } else {
            if ($rem != -1)
            {
                print "<tr><th class='ui-widget-header ui-state-default' colspan=8 valign=center style='font-size:125%; line-height: 2em'>Non Attribu&eacute;";
                print "<tr><th class='ui-widget-header ui-state-default'>&nbsp;
                           <th class='ui-widget-header ui-state-default'>Ref.
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
        print "<tr><td valign=top width=30 class='ui-widget-content'><span class='ui-widget-header'><span id='displayFIDet-".$res->rowid."' class='displayFIDet ui-icon ui-icon-circle-triangle-e'></span></span>";
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=150 class='ui-widget-content' >".$fi->getNomUrl(1);
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=70 class='ui-widget-content'>".date('d/m/Y',strtotime($res->datei));
        $tmpDur = convDur($res->duree);
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=80 class='ui-widget-content'>".$tmpDur['hours']['abs']."h".$tmpDur['minutes']['rel'];
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=80 class='ui-widget-content'> ".$fi->getLibStatut(4);
        $diStr = "";
//        $requete1 = "SELECT * FROM Babel_li_interv WHERE fi_refid = ".$res->rowid;
        $tabDI = $fi->getDI();
        foreach($tabDI as $idDI){
            $di = new DemandeInterv($db);
            $di->fetch($idDI);
            $diStr .= $di->getNomUrl(1)."<br/>";            
        }
//        $sql1 = $db->query($requete1);
//        while ($res1=$db->fetch_object($sql1))
//        {
//            $di = new DemandeInterv($db);
//            $di->fetch($res1->di_refid);
//            $diStr .= $di->getNomUrl(1)."<br/>";
//        }
        print "    <td style='padding:15px 10px 10px 10px;' valign=top width=100 class='ui-widget-content'> ".$diStr;
        if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
        {

            print "    <td style='padding:15px 10px 10px 10px;' align=center  valign=top width=230 class='ui-widget-content'> ";

            if($res->fk_statut == 0)
                print "<button id='validFI-".$res->rowid."' class='validFI butAction ui-widget-header ui-state-default'>Valid. Interv.</button>";

            print "<button id='modFI-".$res->rowid."' class='modFI butAction ui-widget-header ui-state-default'>Modifier</button>";

            print "<button id='rafraichePrixFI-".$res->rowid."' class='rafraichePrixFI butAction ui-widget-header ui-state-default'>Rafraichir prix</button>";
        }
        print "</table>";
        print "<table width=800>";
    }
//    if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
//    {
//        print "<tr><td id='addFI' align=right><button class='butAction'>Nouvelle Fiche</button>";
//    }
        print "</table>";
        print "<div id='addFIDial'>";
        print "<form id='formAddFI'>";
        print "<table cellpadding=10><tr><th class='ui-wiget-header ui-state-default'>Intervenant<td class='ui-widget-content'>";
        $html->select_users(0,'userid',1);
        print "       <tr><th class='ui-widget-header ui-state-default'>Date<td class='ui-widget-content'><input class='datei' name='datei' id='datei'>";
        print "       <tr><th class='ui-widget-header ui-state-default'>Lier &agrave;<td class='ui-widget-content'><select name='comLigneId' id='comLigneId'>";
        print "<option SELECTED value='-1'>S&eacute;lection -></option>";
        foreach($com->lines as $key=>$val)
        {
            print "<option value='".$val->id."'>".$val->ref." ".$val->libelle." (". price($val->total_ht) ."&euro;)"."</option>";
        }
        print "</select>";
        print "</table>";
        print "</form>";
        print "</div>";

  }
  print <<<EOF
  <style>#ui-datepicker-div{ z-index: 10000; }</style>
  <script>
    var RemStatutArr=new Array();
    jQuery(document).ready(function(){
        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: false,
                    duration: '',
                    constrainInput: false,}, jQuery.datepicker.regional['fr']));
        jQuery('.datei').datepicker();
        jQuery.validator.addMethod(
                        "FRDateNoReq",
                        function(value, element) {
                            //console.log(value);
                            if (value+"x"=="x")
                            {
                                //console.log("toto");
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
                        "Ce champs est requis"
                    );

        jQuery('#addFIDial').dialog({
            modal: true,
            autoOpen: false,
            title: "Fiche d'intervention",
            minWidth: 540,
            width: 540,
            buttons: {
                OK: function(){
                    if (jQuery('#formAddFI').validate({rules: {
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
                        location.href=DOL_URL_ROOT+'/fichInter/fiche.php?action=create&datei='+datei+"&comLigneId="+comLigneId+"&userid="+userid;
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function(){
            }
        });
        jQuery('#addFI').click(function(){
            jQuery('#addDIDial').dialog('open');
        });
        jQuery('.modFI').click(function(){
            var id = jQuery(this).attr('id').replace(/^modFI-/,"");
            location.href=DOL_URL_ROOT+'/fichinter/fiche.php?id='+id;
        });
        jQuery('.validFI').click(function(){
            var id = jQuery(this).attr('id').replace(/^validFI-/,"");
            location.href=DOL_URL_ROOT+'/fichinter/fiche.php?id='+id+'&action=confirm_validate&confirm=yes';
        });
        jQuery('.rafraichePrixFI').click(function(){
            var id = jQuery(this).attr('id').replace(/^rafraichePrixFI-/,"");
            location.href=DOL_URL_ROOT+'/fichinter/fiche.php?action=rafraichePrixFI&id='+id;
        });


        jQuery(".displayFIDet").click(function(){
            var self =this;
            var id = jQuery(this).attr('id').replace(/^displayFIDet-/,"")
            if (RemStatutArr[id]==0)
            {
                RemStatutArr[id]=1;
                jQuery(this).removeClass("ui-icon-circle-triangle-s");
                jQuery(this).addClass("ui-icon-circle-triangle-e");
                jQuery.ajax({
                    url: "ajax/xml/DetFInter-xml_response.php",
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
                    url: "ajax/xml/DetFInter-xml_response.php",
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
                        jQuery(msg).find('FI').each(function(){
                            var rowid = jQuery(this).find('rowid').text();
                            var datei = jQuery(this).find('date').text();
                            var description = jQuery(this).find('description').text();
                            var total_ht = jQuery(this).find('total_ht').text();
                            var type = jQuery(this).find('type').text();
                            var duree = jQuery(this).find('duree').text();
                            var rang = jQuery(this).find('rang').text();

                            longHtml += "<tr class='detToRem"+id+"'><td width=30>&nbsp;<td class='ui-widget-content ui-state-default ' align=center>"+datei
                                     + "    <td class='ui-widget-content ui-state-default ' align=center>"+duree
                                     +     "<td class='ui-widget-content ui-state-default ' colspan=1>"+total_ht+" &euro;"
                                     + "    <td class='ui-widget-content ui-state-default ' colspan=1>"+type
                                     + "    <td class='ui-widget-content ui-state-default ' colspan=5>"+description;
                        });
                        jQuery(self).parent().parent().parent().parent().append(longHtml);
                        //console.log(msg);
                    }
                });
            }
        })
    });
  </script>

EOF;
?>
