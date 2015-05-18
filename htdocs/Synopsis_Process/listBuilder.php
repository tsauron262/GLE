<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 27 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listBuilder.php
  * GLE-1.2
  */


  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');



  $forceUpdate=false;

  if ($_REQUEST['action'] == 'add')
  {
      $listeObj = new listform($db);
      $listeObj->description = $_REQUEST['description'];
      $listeObj->label = $_REQUEST['label'];
      $res = $listeObj->add();
      if ($res > 0)
      {
            header("Location: listBuilder.php?id=".$res);
            exit();
      } else {
        if ($res == -1)
            $msg = "Erreur SQL : ".$listeObj->error;
        else if ($res == -2){
            $msg = "Erreur: ".$listeObj->error;
            $id = -2;
        }
        else
            $msg = "Erreur ind&eacute;finie : ".$listeObj->error;
        $forceCreate='Create';
      }
  }
  if ($_REQUEST['action'] == 'del')
  {
      $listeObj = new requete($db);
      $listeObj->id=$_REQUEST['id'];
      $res = $listeObj->del();
      if ($res){
          header("Location: listQuery.php");
          exit();
      } else {
        $msg = "Erreur dans la supression";
      }
  }

   $js = <<<EOF
        <script>
            jQuery(document).ready(function(){
                jQuery.validator.addMethod(
                    'required',
                    function(value, element) {
                        return value.match(/^[\w\W\d]+$/);
                    },
                    '<br>Ce champ est requis'
                );
                if (jQuery('#createForm')){
                    jQuery('#createForm').validate();
                } else if (jQuery('#modForm')){
                    jQuery('#modForm').validate();
                    jQuery('#modForm').validate().form();
                }
            });
        </script>
        <style>
            #modForm input, #createForm input { width:85%; text-align:center; }
            #modForm textarea, #createForm textarea  { width:95%; }
        </style>
EOF;

    $js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"></script>';

  if($_REQUEST['action'] == "Create" || $forceCreate)
  {
      llxHeader($js,utf8_decode("Nouvelle liste"));

       print "<div class='titre'>Nouvelle liste</div><br/>";
       if($msg){
           print "<div class='error ui-state-error'>".$msg."</div>";
       }
       print "<br/>";
       print "<form id='createForm' action='listBuilder.php?action=add' method='POST'>";
       print "<table cellpadding=15 width=100%>";
       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default'>Label";
       if ($id == -2)
       {
            print "      <td align=center class='ui-widget-content'><input name='label' value='".$_REQUEST['label']."' class='required error'>";
       } else {
            print "      <td align=center class='ui-widget-content'><input name='label' value='".$_REQUEST['label']."' class='required'>";
       }
       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default'>Description";
       print "      <td align=center class='ui-widget-content'><textarea name='description' class='required'>".$_REQUEST['description']."</textarea>";
       print '  <tr>';
       print "      <th class='ui-widget-header' colspan=2><button class='butAction'>Ajouter</button>";
       print "</table></form>";
  } else if ($_REQUEST['id'] > 0 && ($_REQUEST['action'] == 'mod' || $forceUpdate)){
       $listeObj = new listform($db);
       $listeObj->fetch($_REQUEST['id']);
       $js.= <<< EOF
    <style>
        .ui-icon-trash, #preview { cursor: pointer; }
        .ui-icon-carat-2-n-s { cursor: move; }
        #sortable { list-style-type: none; margin: 0; padding: 0;  }
        #sortable li {  margin: 0; padding:0; width: 100%; }
        #sortable li.ui-state-error { height: 36px; border-style: dashed;background-repeat: repeat-x; background-image: url("../Synopsis_Common/css/flick/images/ui-bg_inset-soft_95_fef1ec_1x100.png")}
    </style>

        <script>
        jQuery(document).ready(function(){
            jQuery('#sortable').sortable({
                "grid":[ 20,20 ],
                "delay":500,
                "distance":30,
                "cursor":"move",
                placeholder: 'ui-state-error',
                "top":"-5px",
                "left":"-5px",
            }).disableSelection();
            jQuery('#addMember').click(function(){
                var label = jQuery('#modFormAddElement').find('#label').val();
                var valeur = jQuery('#modFormAddElement').find('#valeur').val();
                var dateNow = new Date();
                var curDate = dateNow.getTime();
                inputHiddenText = "<input type='hidden' name='label-d"+curDate+"' value=\""+label+"\">";
                inputHiddenText += "<input type='hidden' name='valeur-d"+curDate+"' value=\""+valeur+"\">";

                var longHtml = '<table width=100% style="clear:both;"><tr><td width=250>'+inputHiddenText+label+'<td width=250>'+valeur+'<td><button class="butActionDelete" onClick="delListElement(this);">Supprimer</button></tr></table>';
                jQuery('#sortable').append('<li id="sortable_d'+curDate+'" class="ui-state-highlight">'+longHtml+'</li>')
                return(false);
            });
            jQuery('.butActionDelete').mouseout(function(){
                jQuery(this).removeClass('ui-state-default');
            });
        });
        function delListElement(obj)
        {
            jQuery(obj).parents('li.ui-state-highlight').remove();
        }

        function updateForm()
        {
            var data = jQuery('#sortable').sortable("serialize")+"&"+jQuery("#modForm").serialize();
            if (jQuery('#modForm').validate().form()){
                console.log(data);
                jQuery.ajax({
                    url: "ajax/listBuilder-xml_response.php?action=update&id="+listId,
                    data: data,
                    datatype: "xml",
                    type: "POST",
                    cache: false,
                    success:function(msg)
                    {
                        if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text() == 'OK')
                        {
                            location.href="listBuilder.php?id="+listId;
                        } else {
                            var error = jQuery(msg).find('KO').text();
                            jQuery('#error').html(error);
                            jQuery('#error').css('display','block');

                        }

                    },
                })
            }
            return(false);
        }
EOF;
       $js .= 'var listId = '.$_REQUEST['id'].' ;</script>';
       llxHeader($js,utf8_decode("Modification liste"));
       print "<div class='titre'>Modification liste</div><br/>";
       if($msg){
           print "<div class='error ui-state-error'>".$msg."</div><br/>";
       }
       print "<div style='display:none;' class='error ui-state-error' id='error'><br/></div>";

       print "<form id='modForm' onSubmit='return(false);' action='listBuilder.php?action=update&id=".$listeObj->id."' method='POST'><table cellpadding=15 width=100%>";
       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default' width=150>Label";
       print "      <td align=center class='ui-widget-content' width=600><input type='text' class='required' name='label' value=".$listeObj->label.">";

       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default'>Description";
       print "      <td align=center class='ui-widget-content'><textarea name='description' class='required'>".$listeObj->description."</textarea>";
       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default'>Composant";
       print "      <td align=center class='ui-widget-content'>";
       print "        <div style='width:90%' style='clear:both;float: none;'>";
       print "            <table width=100%><tr><th width=250 class='ui-widget-header ui-state-hover'>Titre<th width=250 class='ui-widget-header ui-state-hover'>Valeur<th class='ui-widget-header ui-state-hover'>&nbsp;</table>";
       print "        </div>";

       print "        <div style='width:90%' style='clear:both; float: none;'>";
       print "          <ul id='sortable' style='clear:both; float: none;'>";
       foreach($listeObj->lignes as $key=>$val)
       {
           print "          <li id='sortable_".$val->id."' class='ui-state-highlight'>";
           $inputHiddenText = "<input type='hidden' name='label-".$val->id."' value=\"".$val->label."\">";
           $inputHiddenText .= "<input type='hidden' name='valeur-".$val->id."' value=\"".$val->valeur."\">";

           print "              <table width=100%><tr><td width=250>".$inputHiddenText.$val->label."<td width=250>".$val->valeur."<td><button class='butActionDelete' onClick='delListElement(this);'>Supprimer</button></table>";
           print "          </li>";
       }
       print "          </ul>";
       print "        </div>";
       print "        <div style='width:90%' style='clear:both;' id='modFormAddElement'>";
       print "            <form onSubmit='return(false);'><table width=100%><tr><td width=250><input id='label'><td width=250><input id='valeur'><td><button id='addMember' class='butAction'>Ajouter</button></table></form>";
       print "        </div>";

       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default' colspan=4><button onClick='updateForm(); return(false);'  class='butAction'>Modifier</button>";
       print "                                                              <button onClick='location.href=\"listBuilder.php?id=".$listeObj->id."\"; return(false);' class='butAction'>Annuler</button>";

       print "</table></form>";
  } else if ($_REQUEST['id'] > 0){
       $listeObj = new listform($db);
       $listeObj->fetch($_REQUEST['id']);

       $js .= '<script>';
       $js .= 'var listId = '.$_REQUEST['id'].' ;';
       $js .= <<< EOF
       jQuery(document).ready(function(){
            jQuery('#delDialog').dialog({
                buttons:{
                    "OK": function(){
                        location.href="listBuilder.php?id="+listId+"&action=del"
                    },
                    "Annuler": function(){
                        jQuery('#delDialog').dialog('close');
                    },
                },
                autoOpen: false,
                width: 520,
                minWidth: 520,
                modal: true,
                title: "Supprimer une liste",
            });
       });
       function delDialog(){
            jQuery('#delDialog').dialog('open');
       }
       </script>

EOF;

       llxHeader($js,utf8_decode("Visualisation requête"));
       if($msg){
           print "<div class='error ui-state-error'>".$msg."</div><br/>";
       }

       print "<div class='titre'>Visualisation liste</div><br/>";
       print " <table cellpadding=15 width=100%>";
       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default' width=150>Label";
       print "      <td align=left class='ui-widget-content'>".$listeObj->getNomUrl(1);

       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default'>Description";
       print "      <td align=left class='ui-widget-content'>".$listeObj->description;

//Composant de la liste
      if (count($listeObj->lignes) > 0){
         print '  <tr>';
         print "      <th class='ui-widget-header ui-state-default'>Composants";
         print "      <td align=left class='ui-widget-content'>";
         print "<table cellpadding=10 width=80%>";
         print "<tr><th class='ui-widget-header ui-state-focus'>Libell&eacute;<th class='ui-widget-header ui-state-focus'>Valeur";
         foreach($listeObj->lignes as $key=>$val)
         {
             print "<tr><td class='ui-widget-content'>".$val->label."<td class='ui-widget-content'>".$val->valeur;
         }
         print "</table>";
      }

       print '  <tr>';
       print "      <th class='ui-widget-header ui-state-default' colspan=2><button onClick='location.href=\"listBuilder.php?id=".$listeObj->id."&action=mod\"' class='butAction'>Modifier</button><button onClick='delDialog();' class='butActionDelete'>Supprimer</button>";

       print "</table>";
       print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir effacer cette liste ?</div>";

  } else if(!$_REQUEST['id'] > 0){
      header("Location: listQuery.php");
  }


?>
