<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 24 oct. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : fiche.php
  * GLE-1.2
  */

  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
  $msg =  "";
  if (!$user->rights->commande->commande->group) accessforbidden();
  $id = $_REQUEST['id'];
  $comGrp = new CommandeGroup($db);

  if ($_REQUEST['action']=="create")
  {
      $name = $_REQUEST['nom'];
      $id = $comGrp->add($name);
      if ($id < 0)
      {
         header('location: nouveau.php');
      } else {
         header('location: fiche.php?id='.$id);
      }

  }

  $comGrp->fetch($id);

  $modify = false;
  if ($_REQUEST['action']=="Modify")
  {
        $modify=true;
  }
  if ($_REQUEST['action'] == 'delete')
  {
      $ret=$comGrp->delete();
      if ($ret<0)
      {
          $msg = "<div class='ui-error error'>Supression impossible</div>";
      } else {
         header('location: ../index.php');
      }
  }
  if ($_REQUEST['action']=="validModif")
  {
      $comGrp->nom = $_REQUEST['grpName'];
      $ret = $comGrp->update();
      $comGrp->fetch($id);
      if ($ret<0)
      {
        $msg = "<div class='ui-error error'>Mise à jour impossible</div>";
      }
  }
  if ($_REQUEST['action'] == "addLine"){
      $ret = $comGrp->addCom($_REQUEST['comId']);
      $comGrp->fetch($id);
      if ($ret<0)
      {
        $msg = "<div class='ui-error error'>Ajout impossible</div>";
      }
  }
  if ($_REQUEST['action'] == "deleteLine"){
    $ret = $comGrp->delCom($_REQUEST['comId']);
    if ($ret<0)
    {
        $msg = "<div class='ui-error error'>Suppression impossible</div>";
    }
    $comGrp->fetch($id);
  }

  $js = "<script>";
  $js .= " var comGrpId = ".$comGrp->id.";";
  $js .= <<<EOF
  var DeleteComId = 0;
  jQuery(document).ready(function(){
    jQuery('select#socid').change(function(){
        updateComList(jQuery(this).find(':selected').val());
    });

  jQuery('#dialogGroupDelete').dialog({
        autoOpen: false,
        modal: true,
        title: 'Effacer le groupe',
        buttons: {
            OK: function() {
EOF;
  $js .= "   location.href='".$_SERVER['PHP_SELF']."?action=delete&id=".$comGrp->id."';";
  $js .= <<<EOF
            },
            Annuler:function() {
                jQuery(this).dialog('close');
            }
        }
    });

  jQuery('.dialogConfirmDelete').dialog({
        autoOpen: false,
        modal: true,
        title: 'Effacer la commande du groupe',
        buttons: {
            OK: function() {
EOF;
  $js .= "   location.href='".$_SERVER['PHP_SELF']."?action=deleteLine&id=".$comGrp->id."&comId='+DeleteComId;";
  $js .= <<<EOF
            },
            Annuler:function() {
                jQuery(this).dialog('close');
            }
        }
    });


  });

  var ajax_updater_postFct= function(socId){
    updateComList(socId);
  }

  function updateComList(socId)
  {
      if (socId > 0)
      {
        jQuery.ajax({
            url: "ajax/listCommande-xml_response.php?id="+socId+"&comGrpId="+comGrpId,
            data:"",
            datatype:"xml",
            type:"POST",
            cache: true,
            success: function(msg){
                jQuery('#commande_refid').replaceWith('<SELECT id="commande_refid"></SELECT>');
                if (jQuery(msg).find('commande').length==0) jQuery('#commande_refid').append('<option value="-1">Pas de commande trouv&eacute;</option>');
                else jQuery('#commande_refid').append('<option value="-1">S&eacute,lectionner une commande</option>');
                jQuery(msg).find('commande').each(function(){
                    var id = jQuery(this).attr('id');
                    var val = jQuery(this).text();
                    jQuery('#commande_refid').append('<option value="'+id+'">'+val+'</option>');
                });
                jQuery('#commande_refid').selectmenu({style: 'dropdown', maxHeight: 300 });
            }
        });
      } else {
          jQuery('#commande_refid').replaceWith('<SELECT id="commande_refid"></SELECT>');
          jQuery('#commande_refid').append('<option value="-1">S&eacute,lectionner une soci&eacute;t&eacute;</option>');
          jQuery('#commande_refid').selectMenu({style: 'dropdown', maxHeight: 300 });
      }
  }

  function ajouter()
  {
    var val=jQuery('#commande_refid').find(':selected').val();
    if (!val > 0)
    {
        alert('Merci de choisir une commande');
    } else {
EOF;
$js .=  " location.href='".$_SERVER['PHP_SELF']."?action=addLine&comId='+val+'&id='+comGrpId; ";
$js .= <<<EOF
    }
  }
function deleteGroup(){
        jQuery("#dialogGroupDelete").dialog("open");
}

function deleteLine(comId){
    DeleteComId=comId;
    jQuery('.dialogConfirmDelete').dialog('open');
}

EOF;
  $js .= "</script>";

  llxHeader($js,"Groupe de commande");
  print "<div class='dialogConfirmDelete'>&Ecirc;tes vous sure de bien vouloir supprimer la commande du groupe ?</div>";
  print "<div id='dialogGroupDelete'>&Ecirc;tes vous sure de bien vouloir supprimer le groupe de commande ?</div>";
  if (!$id > 0) {
    $msg="<div class='ui-error error'>Id groupe invalide</div>";
    print $msg;
    exit;
  }
    saveHistoUser($comGrp->id, "groupCom",$comGrp->ref);

  if (strlen($msg) > 0) print $msg;

  print "<div class='titre'>Les groupes de commandes</div><br/>";
  if ($modify)
  {
       print "<form id='grpForm' method='POST' action='".$_SERVER['PHP_SELF']."?id=".$comGrp->id."'>";
       print "<input type='hidden' name='action' value='validModif' />";
  }
  print "<table cellpadding=15 width=100%>";

  print "<tr><th class='ui-widget-header ui-state-default' width=20%>Ref group";
  if ($modify)
  {
      print "    <td class='ui-widget-content'><input name='grpName' class='required' value='".$comGrp->nom."'>";
  } else {
      print "    <td class='ui-widget-content'>".$comGrp->getNomUrl(1);
  }

  print "<tr><th class='ui-widget-header ui-state-default' width=20%>Nombre d'&eacute;l&eacute;ment";
  print "    <td class='ui-widget-content'>".$comGrp->qteInGrp;

  print "</table>";
  if ($modify)
  {
       print "</form>";
       print "<div id='dialogValid'>&Ecirc;tes vous sure de bien vouloir modifier le groupe ?</div>";
       print <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#grpForm').validate({
        rules: {
            grpName: 'required'
        },
        messages: {
            grpName: '  Ce champs est requis'
        }
    });
    jQuery('#dialogValid').dialog({
        autoOpen: false,
        modal: true,
        title: 'Modifier le groupe',
        buttons: {
            OK: function() {
                jQuery('#grpForm').submit();
            },
            Annuler:function() {
                jQuery(this).dialog('close');
            }
        }

    })
});
function valider()
{
    if(jQuery('#grpForm').validate({
        rules: {
            grpName: 'required'
        },
        messages: {
            grpName: 'Ce champs est requis'
        }
    }).form()){
        jQuery("#dialogValid").dialog("open");
    }
}
</script>
EOF;

  }

  if ($modify)
  {
      print "<div class='tabsAction'>";
      print "<button onClick='location.href=\"".$_SERVER['PHP_SELF']."?id=".$comGrp->id."\"; return false;' class='ui-button'>Annuler</button>";
      print "<button onClick='valider();return false;' class='ui-button'>Valider</button>";
      print "</div>";
  } else {
      print "<div class='tabsAction'>";
      print "<button onClick='location.href=\"".$_SERVER['PHP_SELF']."?action=Modify&id=".$comGrp->id."\"' class='ui-button'>Modifier</button>";
      print "<button onClick='deleteGroup()' class='ui-button'>Supprimer</button>";
      print "</div>";
  }

  if (!$modify)
  {
      require_once(DOL_DOCUMENT_ROOT."/html.form.class.php");
      $html =  new Form($db);
      print "<br/>";
      print "<table width=100%>";
      print "<tr class='liste_titre'><td colspan=4 class='liste_titre'>Commandes</td>";
      print "<tr><th>Ref commande</th><th>Soci&eacute;t&eacute;</th><th>Total HT</th><th>&nbsp;</th>";
      $bool=false;
      foreach($comGrp->commandes as $key=>$val)
      {
            print "<tr ".$bc[$bool].">";
            print "   <td>".$val->getNomUrl(1);
            print '<td>'.$val->societe->getNomUrl(1);
            print "<td align='right'>".price($val->total_ht).'&euro;';
            print "<td align='center'><a href='#' onClick='deleteLine(".$val->id.");'>".img_delete('Effacer la commande du groupe')."</a>";
          $bool = !$bool;
      }
      print "<tr><th colspan=4 class='liste_titre'>Ajoute un &eacute;l&eacute;ment</th>";
      $selected = $val->commande[0]->societe->id;
      print "<tr class='ui-widget-content'><td>".$html->select_societes($selected,'socid','',$showempty=1,false,"");

      print "</td><td colspan=1><SELECT name='commande_refid' id='commande_refid'><option value=-1>S&eacute;lectionner une soci&eacute;t&eacute;</option></SELECT></td><td><button onClick='ajouter();return false;' class='ui-button'>Ajouter</button></td><td>";
      print "</table>";
      print "<br/>";
      print "<table width=100%>";
      print "<tr class='liste_titre'><td colspan=3 class='liste_titre'>Produits</td>";
      print "<tr><th>Produit</th><th>Prix Produit unitaire</th><th>Quantit&eacute;</th>";
      $arrProd = array();
      foreach($comGrp->commandes as $key=>$val)
      {
        $val->fetch_lines();
        foreach($val->lignes as $key1=>$val1)
        {
            if ($val1->fk_product>0)
            {
                $arrProd[$val1->fk_product]++;
            }
        }
      }
      require_once(DOL_DOCUMENT_ROOT.'/product.class.php');
      foreach($arrProd as $key=>$val)
      {
          $tmpProd = new Product($db);
          if ($tmpProd->fetch($key) && $tmpProd->ref."x" != "x")
              print "<tr ".$bc[$bool]."><td>".$tmpProd->getNomUrl(1).'<td align="center">'.price($tmpProd->total_ht).'&euro;'."<td align='center'>".$val;
          $bool = !$bool;
      }
      print "</table>";

  }

?>
