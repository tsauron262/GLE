<?php
/*
  */
 /**
  *
  * Name : fctBuilder.php
  * GLE-1.2
  */
  require_once('pre.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');
  if(!$user->rights->process->configurer){
        accessforbidden();
  }

  $id = $_REQUEST['id'];
  $action = $_REQUEST['action'];
  //1 Affiche la class, le classFile et Affiche la fonction et ces parametres
$js = <<< EOF
    <style>
        .ui-icon-trash, #preview { cursor: pointer; }
        #sortable li { cursor: move;}
        .ui-icon-carat-2-n-s { cursor: move; }
        #sortable, #notsortable { list-style-type: none; margin: 0; padding: 0;   }
        #sortable li.ui-state-error { height: 36px; border-style: dashed;background-repeat: repeat-x; background-image: url("../Synopsis_Common/css/flick/images/ui-bg_inset-soft_95_fef1ec_1x100.png")}
    </style>
    <script>


EOF;
    if ($id > 0)
        $js .= "var fctId = ".$id.";";
    $js .= <<< EOF
    jQuery(document).ready(function()
    {
        jQuery('#sortable').sortable({
            "grid":[ 20,20 ],
            "delay":500,
            "distance":30,
            "cursor":"move",
            placeholder: 'ui-state-error',
            "top":"-5px",
            "left":"-5px",
        }).disableSelection();


        jQuery.validator.addMethod(
            'requiredIfChecked',
            function(value, element) {
                if(jQuery("input[name=printVarInsteadOdReturn]").attr('checked')){
                    return value.match(/^[\w\W\d]+$/);
                } else {
                    return true;
                }

            },
            '<br>Ce champ est requis dans le cas de <br/>l\'affichage d\'une variable au lieu du retour'
        );


        jQuery.validator.addMethod(
            'required',
            function(value, element) {
                return value.match(/^[\w\W\d]+$/);
            },
            '<br>Ce champ est requis'
        );

        jQuery('#modForm').validate();
        jQuery('#createForm').validate();
        jQuery('#formAddVars').validate();

        reInit();
       jQuery('#buttonAddVars').click(function(){
            var i = jQuery('#sortable').find('li').length;
            i++;
            jQuery('#sortable').append('<li id="sortable_'+i+'" class="ui-state-focus"><table cellpadding=10 width="100%"><tr><td><span><span class="ui-icon ui-icon-carat-2-n-s"></span></span><td>'+i+'<td><input type="text" name="params-'+i+'" class="required" value="'+jQuery('#newVar').val()+'"><td><span><span class="ui-icon ui-icon-trash"></span></span></tr></table></li>');
            reInit();
       })
       jQuery('#delFonction').dialog({
            autoOpen: false,
            width: 520,
            minWidth: 520,
            modal: true,
            title: "Suppression de la fonction",
            buttons: {
                "OK": function(){
                    jQuery.ajax({
                        url:"ajax/fctBuilder-xml_response.php",
                        type:"POST",
                        datatype:'xml',
                        data:"action=del&id="+fctId,
                        cache: false,
                        success:function(msg){
                            if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text())
                            {
                                var id = jQuery(msg).find('OK').text();
                                location.href='listFct.php';
                            } else {
                                alert('Il y a une erreur');
                            }
                        }

                    })
                    jQuery('#delFonction').dialog('close');
                },
                "Annuler":function(){
                    jQuery('#delFonction').dialog('close');
                }
            }

        })


    });

    function delFct(){
        jQuery('#delFonction').dialog('open');
    }

    function reInit(){
        jQuery('.ui-icon-trash').each(function(){
            jQuery(this).mouseover(function(){
                jQuery(this).parent().addClass('ui-state-error ui-corner-all').css("border","0");
            });
            jQuery(this).mouseout(function(){
                jQuery(this).parent().removeClass('ui-state-error ui-corner-all').css("border","0");
            });
            jQuery(this).click(function(){
                jQuery(this).parents('li').remove();
            });
       });
        jQuery('.ui-icon-carat-2-n-s').each(function(){
            jQuery(this).mouseover(function(){
                jQuery(this).parent().addClass('ui-state-error ui-corner-all').css("border","0");
            });
            jQuery(this).mouseout(function(){
                jQuery(this).parent().removeClass('ui-state-error ui-corner-all').css("border","0");
            });
       });

    }
    function saveAddData()
    {
        if (jQuery('#createForm').validate().form())
        {
            var data = jQuery('#createForm').serialize();
            jQuery.ajax({
                url:"ajax/fctBuilder-xml_response.php",
                type:"POST",
                datatype:'xml',
                data:"action=add&"+data,
                cache: false,
                success:function(msg){
                    if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text() > 0)
                    {
                        var id = jQuery(msg).find('OK').text();
                        location.href='fctBuilder.php?action=Modifier&id='+id;
                    } else {
                        alert('Il y a une erreur');
                    }
                }
            });
        }
    }
    function saveModForm()
    {
        if (jQuery('#modForm').validate().form())
        {
            var data = jQuery('#modForm').serialize();
                data += "&"+jQuery('#sortable').sortable("serialize");
            jQuery.ajax({
                url:"ajax/fctBuilder-xml_response.php",
                type:"POST",
                datatype:'xml',
                data:"action=mod&id="+fctId+"&"+data,
                cache: false,
                success:function(msg){
                    if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text() > 0)
                    {
                        var id = jQuery(msg).find('OK').text();
                        location.href='fctBuilder.php?id='+id;
                    } else {
                        alert('Il y a une erreur');
                    }
                }
            });
        }

    }
    </script>

EOF;
    
    $js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"></script>';
  llxHeader($js,'Constructeur de fonction');

    switch ($action){
        case 'Modifier':
        {
             print "<div class='titre'>Modifier une fonction</div><br/>";
             if($msg){
                print "<div class='error ui-state-error'>".$msg."</div>";
             }
             print "<br/>";

             print "<form id='modForm' action='formBuilder.php?action=modFct' onSubmit='return false;' method=POST>";
             print "<table cellpadding=15>";
             $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct WHERE id = ".$id;
             $sql = $db->query($requete);
             while($res = $db->fetch_object($sql))
             {
                  $fct = new fct($db);
                  $fct->fetch($res->id);

                   print "<tr><th class='ui-state-default ui-widget-header'>Nom</th><td colspan=1 class='ui-widget-content'><input type='text' name='label' class='required' value='".$fct->label."'></td>";
                   print "    <th class='ui-state-default ui-widget-header'>Description</th><td class='ui-widget-content'><input type='text' name='description' class='required' value='".$fct->description."'></td>";

                   print "<tr><th class='ui-state-default ui-widget-header'>Class</th><td class='ui-widget-content'><input type='text' name='class' class='required' value='".$fct->class."'></td>";
                   print "    <th class='ui-state-default ui-widget-header'>Fonction/M&eacute;thode</th><td class='ui-widget-content'><input name='fct' type='text' class='required' value='".$fct->fct."'></td>";
                   print "<tr><th class='ui-state-default ui-widget-header'>Affiche une variable au lieu du retour de la fonction</th><td class='ui-widget-content'><input type='checkbox' name='printVarInsteadOdReturn' ".($fct->printVarInsteadOdReturn==1?'Checked':'')."></td>";
                   print "    <th class='ui-state-default ui-widget-header'>Variable &agrave; afficher</th><td class='ui-widget-content'><input type='text' name='VarToBePrinted' class='requiredIfChecked' value='".$fct->VarToBePrinted."'></td>";
                   print "<tr><th class='ui-state-default ui-widget-header'>D&eacute;clar&eacute; dans le fichier<br/>(<em>Relatif &agrave; la racine DOL_DOCUMENT_ROOT</em>) </th><td colspan=3 class='ui-widget-content'><input class='required' type='text' name='fileClass' value='".$fct->fileClass."'></td>";
                   print "<tr><th class='ui-state-default ui-widget-header'>Posttraitement pour l'affichage des valeurs</th><td colspan=3 class='ui-widget-content'><textarea name='postTraitementValue'>".$fct->postTraitementValue."</textarea>";
                   print "<tr><th class='ui-state-default ui-widget-header'>Param&egrave;tres</th><td colspan=3 style='padding-left: 50px; padding-right: 50px;' class='ui-widget-content' align=center>";
                   print "<center><ul id='notsortable'>";
                   print "<li class='ui-state-focus' >";
                   print "<table cellpadding=10 width=100%>";
                   print "<tr><th class='ui-widget-header ui-state-default'>&nbsp;
                              <th class='ui-widget-header ui-state-default'>Rang
                              <th class='ui-widget-header ui-state-default'>Nom
                              <th class='ui-widget-header ui-state-default'>Nom HTML
                              <th class='ui-widget-header ui-state-default'>Action";
                   print "</table>";
                   print "</li>";
                   print "</ul></center>";
                   print "<center><ul id='sortable'>";

                   $arrParams = preg_split('/\|\|/',$fct->params);
                   $i=0;
                   foreach($arrParams as $key=>$val)
                   {
                        $i++;
                        print "<li class='ui-state-focus' id='sortable_".$i."'>";
                        print "<table cellpadding=10 width=100%>";
                        print "<tr><td><span><span class='ui-icon ui-icon-carat-2-n-s'></span></span><td>".$i."<td><input type='text' class='required' name='params-".$i."' value='".$val."'><td><input type='radio' name='paramsForHtmlName' value='".$val."' ".($val==$fct->paramsForHtmlName?" Checked ":"")."><td><span><span class='ui-icon ui-icon-trash'></span></span>";
                        print "</table>";
                        print "</li>";
                   }
                   print "</ul></center>";
                   print "<div style='width:100%; clear: both;'>";
                   print "<form id='formAddVars' onSubmit='return false;'><table width=100%><tr>";
                   print "<td><input type='text' id='newVar' name='newVar'>";
                   print "<td><button id='buttonAddVars' class='butAction'>Ajouter</button>";
                   print "</table></form>";
                   print "</div>";
             }
             print "<tr><th class='ui-widget-header' colspan=4><button onClick='saveModForm();' class='butAction'>Modifier</button><button onClick='location.href=\"fctBuilder.php?id=".$id."\"; return(false);' class='butAction'>Annuler</button>";
             print "</table>";
             print "</form>";
        }
        break;
        case 'Create':
        {
             print "<div class='titre'>Nouvelle fonction</div><br/>";
             if($msg){
                print "<div class='error ui-state-error'>".$msg."</div>";
             }
             print "<br/>";
             print "<form id='createForm' action='formBuilder.php?action=addFct' onSubmit='return(false);' method=POST>";

             print "<table cellpadding=15>";
             print "<tr><th class='ui-state-default ui-widget-header'>Nom</th><td colspan=1 class='ui-widget-content'><input class='required' type='text' name='label' value='".$res->label."'></td>";
             print "    <th class='ui-state-default ui-widget-header'>Description</th><td class='ui-widget-content'><input class='required' type='text' name='description' value='".$res->description."'></td>";
             print "<tr><th class='ui-widget-header' colspan=4><button onClick='saveAddData(); return false;' class='butAction'>Ajouter</button>";
             print "</table>";
             print "</form>";
        }
        break;
        default:
        {
             print "<div class='titre'>Fonction source pour formulaire</div><br/>";
             if($msg){
                print "<div class='error ui-state-error'>".$msg."</div>";
             }
             print "<br/>";

             print "<table cellpadding=15>";
             $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct WHERE id = ".$id;
             $sql = $db->query($requete);
             while($res = $db->fetch_object($sql))
             {
                  $fct = new fct($db);
                  $fct->fetch($res->id);

                  print "<tr><th class='ui-state-default ui-widget-header'>Nom</th><td colspan=1 class='ui-widget-content'>".$fct->getNomUrl(1)."</td>";
                  print "    <th class='ui-state-default ui-widget-header'>Description</th><td class='ui-widget-content'>".$fct->description."</td>";

                  print "<tr><th class='ui-state-default ui-widget-header'>Class</th><td class='ui-widget-content'>".$fct->class."</td>";
                  print "    <th class='ui-state-default ui-widget-header'>Fonction/M&eacute;thode</th><td class='ui-widget-content'>".$fct->fct."</td>";
                  print "<tr><th class='ui-state-default ui-widget-header'>Affiche une variable au lieu du retour de la fonction</th><td class='ui-widget-content'>".($fct->printVarInsteadOdReturn==1?'Oui':'Non')."</td>";
                  print "    <th class='ui-state-default ui-widget-header'>Variable &agrave; afficher</th><td class='ui-widget-content'>".$fct->VarToBePrinted."</td>";
                  print "<tr><th class='ui-state-default ui-widget-header'>D&eacute;clar&eacute; dans le fichier<br/>(<em>Relatif &agrave; la racine DOL_DOCUMENT_ROOT</em>) </th><td colspan=3 class='ui-widget-content'>".$fct->fileClass."</td>";
                  print "<tr><th class='ui-state-default ui-widget-header'>PostTraitement de la valeur</th><td colspan=3 class='ui-widget-content'>".$fct->postTraitementValue."</td>";
                  print "<tr><th class='ui-state-default ui-widget-header'>Param&egrave;tres</th><td colspan=3 style='padding-left: 50px; padding-right: 50px;' class='ui-widget-content' align=center>";
                  $arrParams = preg_split('/\|\|/',$fct->params);
                  $i=0;
                  print "<center><ul id='notsortable'>";
                  foreach($arrParams as $key=>$val)
                  {
                       $i++;
                       print "<li class='ui-widget-content' id='sortable_".$i."'>";
                       print "<table cellpadding=10 width=100%>";
                       print "<tr><td width=32>".$i."<td>".$val;
                       if ($val == $fct->paramsForHtmlName) print '&nbsp;&nbsp;&nbsp;(nom HTML)';

                       print "</table>";
                       print "</li>";
                  }
                  print "</ul></center>";
             }
             print "<tr><th class='ui-widget-header' colspan=4><button onClick='location.href=\"fctBuilder.php?action=Modifier&id=".$id."\"' class='butAction'>Modifier</button><button onClick='delFct();' class='butActionDelete'>Supprimer</button>";
             print "</table>";
             print "<div id='delFonction'>";
             print "&Ecirc;tes vous s&ucirc;r de vouloir supprimer cette fonction ?";
             print "</div>";
        }
        break;
    }



  //2 permet d'ajouter/ retirer des parametres
  //3 printVarInsteadOdReturn & VarToBePrinted

?>
