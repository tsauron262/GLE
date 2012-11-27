<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : test.php
  * GLE-1.2
  */
  require_once('pre.inc.php');

$js = <<< EOF
    <style>
        #sortable1, #sortable2 { list-style-type: none; margin: 0; padding: 0; float: left;  }
        #sortable2 li {  margin: 0; padding:0; width: 950px; }
        #sortable2 li.ui-state-error { height: 75px; border-style: dashed;background-repeat: repeat-x; background-image: url("../Synopsis_Common/css/flick/images/ui-bg_inset-soft_95_fef1ec_1x100.png")}
    </style>
    <script>
    jQuery(document).ready(function()
    {
        jQuery('#savButton').click(function(){
            var data = "";
            jQuery('#sortable2').find('li form').each(function(){
                data+= "&"+jQuery(this).serialize();
            });
            data += "&"+jQuery('#sortable2').sortable( "serialize");
            jQuery.ajax({
                url:'ajax/formBuilder-xml_response.php',
                type:"POST",
                datatype:"xml",
                data:"action=saveData&"+data,
                cache: false,
                success: function(msg){
                    console.log(msg);
                }
            });
//            console.log(data);
        });
        jQuery('#supprButton').click(function(){

        });
        jQuery('#sortable1 li').draggable({
            connectToSortable: ".connectedSortable",
            "grid":[ 20,20 ],
            "delay":500,
            revert: true,
            "distance":30,
            "cursor":"move",
            "top":"-5px",
            "left":"-5px",
            "helper":"clone",
            "appendTo":"body",
            start: function(e,ui){
                var type = ui.helper.find('table:first').attr('class');
                if (type=='Item1'){
                    ui.helper.find('table:first tr:first').prepend('<td>Item1</td>');
                } else {
                    ui.helper.find('table:first tr:first').prepend('<td>Item2</td>');
                }
            },
        });
        jQuery('#sortable2').sortable({
            "grid":[ 20,20 ],
            "delay":500,
            "distance":30,
            "cursor":"move",
            placeholder: 'ui-state-error',
            "top":"-5px",
            "appendTo":"#ficheForm",
            "left":"-5px",
            receive: function(event, ui,t)
            {
                var cnt = (jQuery('#sortable2').find('li.ui-state-highlight').length>0?jQuery('#sortable2').find('li.ui-state-highlight').length:0);
                var nextId = parseInt(cnt)+1;
                jQuery('#sortable2').css("height","auto");

                jQuery('#sortable2').find('.ui-draggable').each(function()
                {
                    var type = jQuery(this).find('table:first').attr('class');
                    jQuery(this).attr('id',"sortable_"+nextId);
                    jQuery(this).removeClass('ui-draggable');
                    jQuery(this).removeClass('ui-state-default');
                    jQuery(this).addClass('ui-state-highlight');
                    jQuery(this).find('table:first').wrap('<form onSubmit="return false;"></form>');
                    jQuery(this).find('table:first').attr('width','952');
                    jQuery(this).find('table:first').attr('cellpadding','15');
                    jQuery(this).attr('style','');
                    var srcSel = jQuery('#srcSelClone').clone(1);
                    srcSel.find('select').attr('name','src-'+nextId);
                    srcSel.find('select').removeClass('noSelDeco');
                    jQuery(this).find('table:first td:first')
                                .replaceWith('<td width=75 align=center class="'+type+'"><input type="hidden" name="type-'+nextId+'" value="'+type+'">'+arrCodeToLabel[type]+'</td>\
                                         <td width=100 align=center><input name="titre-'+nextId+'" style="width:75%"></td>\
                                         <td width=225 align=center><textarea name="descr-'+nextId+'" style="width:75%"></textarea></td>\
                                         <td width=150 align=center><input name="dflt-'+nextId+'" style="width:75%"></td>\
                                         <td width=215 align=center>'+srcSel.html()+'</td>\
                                         <td align=center width=35><table><tr><td><span class="ui-icon ui-icon-carat-2-n-s"></span><td><span class="ui-icon ui-icon-trash"></span></table>');
                    jQuery('#sortable2').css("min-height",parseInt(jQuery('#sortable2').height())+75);
                    jQuery('#sortable2 select').selectmenu({style: 'dropdown', maxHeight: 300, menuWidth: 165 });
                });
            },
        }).disableSelection();
    });
    </script>


EOF;

    llxHeader($js,"Constructeur de formulaire");
    print '<div class="ficheForm">';
    print <<<EOF
  <table width=1000 cellpadding=8>
    <tr>
        <td width=250 rowspan=1 valign=top style='min-width: 250px;max-width: 250px;'>
            <table width=100% cellpadding=0>
                <tr>
                    <td width=100% rowspan=1>
                        <div class='ui-widget-header ui-state-hover ui-corner-top' style='padding: 10px 10px;'><span>Type &agrave; ajouter</span></div>
                <tr>
                    <td width=100%>
                        <div class='drag' style='width:99%; border: 1px Solid;' class='ui-widget-content'>
EOF;
    print '  <ul id="sortable1" class="connectedSortable" style="padding-left: 0px; width:250px;">';
    $requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type ORDER BY label";
    $sql = $db->query($requete);
    $arrCodeToLabel=array();
    while ($res=$db->fetch_object($sql))
    {
        $arrCodeToLabel[utf8_encode($res->code)]=utf8_encode($res->label);
        print "<li class='ui-state-default' style='width:234px; padding-top: 5px; padding-bottom: 5px; padding-left: 15px'><table width=100% class='".$res->code."'><tr><td>".$res->label."</td></tr></table></li>";
    }
print <<<EOF
             </ul>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td width=100% rowspan=1>
                        <div class='ui-widget-header ui-state-hover ui-corner-bottom' style='padding: 5px 10px;'></div>
                    </td>
                </tr>
            </table>
        <td width=75% rowspan=1 valign=top style='min-width: 950px;'>
        <script>
EOF;
        print 'var arrCodeToLabel='.json_encode($arrCodeToLabel).';';
?>
        </script>
        <table width=952 cellpadding=15>
            <thead>
                <tr><th width=75 class='ui-widget-header ui-state-default'>Type
                    <th width=100 class='ui-widget-header ui-state-default'>Titre
                    <th width=225 class='ui-widget-header ui-state-default'>Description
                    <th width=150 class='ui-widget-header ui-state-default'>Valeur par d&eacute;faut
                    <th width=215 class='ui-widget-header ui-state-default'>Source
                    <th width=35 class='ui-widget-header ui-state-default'>&nbsp;
            </thead>
        </table>

<ul id="sortable2" class="connectedSortable" style='min-height: 100px; min-width:100%; padding-bottom:5px;'>
</ul>
<button id='savButton' class='butAction'>Sauvegarder</button>
<button id='supprButton' class='butActionDelete'>Supprimer</button>
</table>
</div>
<div style='display:none;'>
<div id="srcSelClone">
<select class="noSelDeco">
<?php
print "<OPTION value=''>Selectionner-></OPTION>";
print "<OPTGROUP label='Requete'>";
$requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete ORDER BY label";
$sql = $db->query($requete);
while($res = $db->fetch_object($sql))
{
    print "<OPTION value='r-".$res->id."'>".$res->label."</OPTION>";
}
print "</OPTGROUP>";
print "<OPTGROUP label='Variable'>";
$requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_global ORDER BY label";
$sql = $db->query($requete);
while($res = $db->fetch_object($sql))
{
    print "<OPTION value='g-".$res->id."'>".$res->label."</OPTION>";
}
print "</OPTGROUP>";
print "<OPTGROUP label='Liste'>";
$requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list ORDER BY label";
$sql = $db->query($requete);
while($res = $db->fetch_object($sql))
{
    print "<OPTION value='l-".$res->id."'>".$res->label."</OPTION>";
}
print "</OPTGROUP>";
print "<OPTGROUP label='Fonction'>";
$requete= "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct ORDER BY label";
$sql = $db->query($requete);
while($res = $db->fetch_object($sql))
{
    print "<OPTION value='f-".$res->id."'>".$res->label."</OPTION>";
}
print "</OPTGROUP>";
?>
</SELECT>
</div>
</div>
</html>