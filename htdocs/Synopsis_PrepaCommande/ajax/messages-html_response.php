<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 31 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : messages-html_response.php
  * GLE-1.2
  */
  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

  if (!$user->rights->commande->lire) accessforbidden();
  // Securite acces client
  $socid=0;
  if ($user->societe_id > 0)
  {
      $socid = $user->societe_id;
  }
  $commande = new Synopsis_Commande($db);
  if ($user->societe_id >0 && isset($_GET["id"]) && $_GET["id"]>0)
  {
      $commande->fetch((int)$_GET['id']);
      if ($user->societe_id !=  $commande->socid) {
          accessforbidden();
      }
  }

  $id = $_REQUEST['id'];

  //1 tabs par type de message
  print "<div id='messagesTabs'>";
  print "<ul>";
  print "<li><a href='#general'>G&eacute;n&eacute;ral</a></li>";
  if ($user->rights->SynopsisPrepaCom->exped->Afficher)
      print "<li><a href='#logistique'>Logistiques</a></li>";
  if ($user->rights->SynopsisPrepaCom->financier->Afficher)
      print "<li><a href='#finance'>Finances</a></li>";
  if ($user->rights->SynopsisPrepaCom->interventions->Afficher)
      print "<li><a href='#intervention'>Interventions</a></li>";
  print "</ul>";

  //2 Message + conversation
  print "<div id='general'>";
  displayConv($id,false);
  print "</div>";
  if ($user->rights->SynopsisPrepaCom->exped->Afficher)
  {
      print "<div id='logistique'>";
      displayConv($id,'logistique');
      print "</div>";
  }
  if ($user->rights->SynopsisPrepaCom->financier->Afficher)
  {
      print "<div id='finance'>";
      displayConv($id,'finance');
      print "</div>";
  }
  if ($user->rights->SynopsisPrepaCom->interventions->Afficher)
  {
      print "<div id='intervention'>";
      displayConv($id,'intervention');
      print "</div>";
  }
  print "</div>";
  print "
<div id='newMsgDialog' class='cntNewMsgDialog'>
    <form id='newMsg'>
        <table width=100%>
            <tr><td>Nouveau message</td></tr>
            <tr><td><textarea id='message' name='message' style='width:100%;'></textarea></td></tr>
        </table>
    </form>
</div>";

  print "<script>var userId=".$user->id.";";
  print "var comId=".$id.";</script>";

print <<<EOF
<script>
var typeMsg="";
jQuery(document).ready(function(){

    if(jQuery('.cntNewMsgDialog').length > 1){
//        jQuery('#newMsgDialog').dialog( "destroy" );
        jQuery('#newMsgDialog').remove();
    }

    jQuery('#messagesTabs').tabs({
        spinner: 'Chargement ...',
        cache: true,
        fx: { opacity: 'toggle' },
    });
    jQuery('.buttonNewMessage').click(function(){
        var id = jQuery(this).attr('id').replace(/^newMessage-/,"");
        typeMsg = id;
        jQuery('#newMsgDialog').dialog('open');
    });
    jQuery('#newMsgDialog').dialog({
            modal: true,
            autoOpen: false,
            title: "Nouveau message",
            minWidth: 540,
            width: 540,
            buttons: {
                OK: function(){
                    if (jQuery('form#newMsg').validate({rules: {
                                                    message: {
                                                        required: true,
                                                    },
                                                },
                                                messages: {
                                                    message: {
                                                      required: "<br>Champ requis"
                                                    },
                                                }
                    }).form()){
                        var message = jQuery('#message').val();
                        var userid = userId;
                        var data = "message="+message+"&userid="+userid+"&id="+comId+"&typeMsg="+typeMsg;
                        var self = this;
                        jQuery.ajax({
                            url: 'ajax/xml/messages-xml_response.php',
                            data:data,
                            datatype:'xml',
                            type:"POST",
                            cache: false,
                            success: function(msg)
                            {
                                if (jQuery(msg).find('OK').length>0){
                                    jQuery(self).dialog('close');

                                    jQuery('#resDisp').replaceWith('<div id="resDisp"><img src="'+DOL_URL_ROOT+'/Synopsis_Common/images/ajax-loader.gif"/></div>');
                                    jQuery('#newMsgDialog').dialog( "destroy" );
                                    jQuery('#newMsgDialog').remove();
                                    jQuery.ajax({
                                        url: "ajax/messages-html_response.php",
                                        data: "id="+comId,
                                        cache: false,
                                        datatype: "html",
                                        type: "POST",
                                        success: function(msg){
                                            jQuery('#resDisp').replaceWith('<div id="resDisp">'+msg+' </div>');
                                        },
                                    });
                                } else {
                                    jQuery(self).dialog('close');
                                    alert('Il y a eu une erreur !');
                                }
                            }
                        });
                    }
                },
                Annuler: function(){
                    jQuery(this).dialog('close');
                }
            },
            open: function(){
                //jQuery('#message').val('');
            }
    });
});

</script>
EOF;

function displayConv($id,$type=false)
{
    global $db,$user,$commande;
    $commande->fetch($id);
    $arrGrpTmp = $commande->listGroupMember(false);
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_messages WHERE commande_refid = ".$id. " AND type is NULL ORDER BY tms DESC";
    if ($type)
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_messages WHERE commande_refid = ".$id. " AND type = '".$type."' ORDER BY tms DESC";
    }

    if ($arrGrpTmp)
    {
        $arrSql = array();
        foreach($arrGrpTmp as $key=>$val)
            $arrSql[$val->id]=$val->id;
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_messages WHERE commande_refid IN (".join(',',$arrSql). ") AND type is NULL ORDER BY tms DESC";
        if ($type)
        {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_PrepaCom_messages WHERE commande_refid IN (".join(',',$arrSql). ") AND type = '".$type."' ORDER BY tms DESC";
        }
    }
    $sql = $db->query($requete);
    $tmpUser = new User($db);
    print "<table width=100% cellpadding=10>";
    $i=$db->num_rows($sql);;
    while ($res = $db->fetch_object($sql))
    {
        $tmpUser->fetch($res->user_author);
        print "<tr><td rowspan=2 width=20 class='ui-widget-content'>#".$i."<td colspan=2 class='ui-widget-content'><div style='border:1px Solid #0073EA; padding: 10px; font-size: 12pt ;' class='ui-corner-all'>".nl2br($res->message)."</div>";
        print "<tr><th class='ui-widget-header ui-state-default black' width=30%>Par ".utf8_encodeRien($tmpUser->getNomUrl(1))."<td style='font-size: 10px; font-weight:0;' class='ui-state-default ui-wiget-header'>Le ".date('d/m/Y',strtotime($res->tms))." &agrave; ".date('H:i',strtotime($res->tms));
        $i--;
    }
    $display=false;
    switch ($type){
        case false: {
            $display=true;
        }
        break;
        case 'logistique':{
          if ($user->rights->SynopsisPrepaCom->exped->Modifier){
                $display =  true;
          }
        }
        break;
        case 'finance':{
          if ($user->rights->SynopsisPrepaCom->financier->Modifier){
                $display =  true;
          }
        }
        break;
        case 'intervention':{
          if ($user->rights->SynopsisPrepaCom->interventions->Modifier){
                $display =  true;
          }
        }
        break;

    }
    if (!$type){$type = "-general";}else {
        $type = "-".$type;
    }

    if ($display)
        print "<tr><td class='ui-widget-header' colspan=3 align=right><button class='butAction buttonNewMessage' id='newMessage".$type."'>Nouveau Message</button>";
    print "</table>";

}

?>
