<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : globalImport.php
  * GLE-1.1
  */

    require_once('pre.inc.php');
    if ($user->rights->GeoBI->GeoBI->Modifie != 1){  accessforbidden(); }

        $param = '&placetype=&country=&lang=&format=XML&style=FULL&indent=true&socid='.$_REQUEST['socid'];
        if ($_REQUEST['id'] > 0)
        {
            $param .= '&id='.$_REQUEST['id'];
        }

        $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                           ".MAIN_DB_PREFIX."societe.address,
                           ".MAIN_DB_PREFIX."societe.cp,
                           ".MAIN_DB_PREFIX."societe.ville,
                           ".MAIN_DB_PREFIX."societe.nom,
                           Babel_GeoBI.lat,
                           Babel_GeoBI.lng,
                           Babel_GeoBI.id as graphId,
                           Babel_GeoBI.countryCode
                      FROM ".MAIN_DB_PREFIX."societe
                 left JOIN Babel_GeoBI on (".MAIN_DB_PREFIX."societe.rowid = Babel_GeoBI.socid)
                     WHERE Babel_GeoBI.lat is null  order by ".MAIN_DB_PREFIX."societe.nom";

        $sql = $db->query($requete);
        $js = "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/ui/ui.progressbar.js'></script>";
        $js.="<script>
        var sl = false;
        var pbar = false;
        var arr = new Array();
        var cntTot = false;";

        $js .= "jQuery(document).ready(function(){

            jQuery('#import').click(function(){
                jQuery('#pgBar').progressbar({ value: 0 });
                jQuery('#import').attr('disabled','disabled');
                jQuery('#import').addClass('ui-state-disabled');
                pbar = parseInt(0);
                jQuery('#pgBar').find('#counter').css('display','inline');
                iter=0;
                jQuery('#mainTable').find('input').each(function(){
                    var id=jQuery(this).attr('id');
                    arr[iter]=id;
                    iter++;
                });
                cntTot = iter;
                sl = setInterval('importData()' ,2000);

            });
        });";
        $param = '&placetype=&country=&lang=&format=XML&option=save&style=FULL&indent=true&socid=';

        $js .= <<<EOF

        function importData()
        {
            var id = arr.pop();
            var cnt = Math.round(parseInt(pbar + 1) * 100 / parseInt(cntTot));
            jQuery('#pgBar').find('#counter').text(cnt+'%');
            jQuery('#pgBar').progressbar( "option", "value",  cnt);
            jQuery('#count2').text( parseInt(pbar+1) +'/'+cntTot);
            pbar = parseInt(pbar)+1;

            jQuery.ajax({
                url: 'gmaps-xmlresponse.php',
                datatype:'xml',
EOF;
    $js .= "data:'".$param."'+id,";
        $js .= <<<EOF
            })


            if (parseInt(pbar) == cntTot ){
                clearInterval(sl);
                location.href='import.php';
            }

        }
EOF;
        $js .= '</script>';

        llxHeader($js,'Import - GeoBI','',true);
        print "<div id='fiche'>";
print <<<EOF
<button style='padding: 5px 10px;' class='butAction ui-corner-all ui-widget-header ui-state-default' onClick="location.href='index.php'">
        <span class='ui-icon ui-icon-arrowreturnthick-1-w' style='margin-top: -1px; margin-right: 3px; float: left;'></span>Retour
  </button><br/><br/>
EOF;

        print "<table cellpadding=15><tr><td>";
        print "<table cellpadding=15 id='mainTable'>";
        print "<tr><th class='ui-widget-header ui-state-default'>Ajouter
                   <th class='ui-widget-header ui-state-default'>Soci&eacute;t&eacute;
                   <th class='ui-widget-header ui-state-default'>Adresse
                   <th class='ui-widget-header ui-state-default'>Code Pays";
        $tmpsoc = new Societe($db);
        while ($res = $db->fetch_object($sql))
        {
            $tmpsoc->fetch($res->rowid);
            print '<tr class="ui-widget-content">
                    <td align=center><input id="'.$res->rowid.'" name="'.$res->rowid.'" checked type="checkbox"></input>
                    <td align=center>'.$tmpsoc->getNomUrl(1)."
                    <td align=center>".$res->address." ".$res->cp." ".$res->ville.'
                    <td align=center>'.$res->countryCode.'';
        }
        print "</table>";
        print "<td valign=top>";
        print "<div style=' width: 300px; height: 200px;'>";
        print "<button id='import' style='padding: 5px 10px;' class='ui-corner-all butAction ui-state-default ui-widget-header'>Lancer l'importation</button>";
        print "<div id='pgBar'><span  id='counter' style='display:none; float: left; position: relative; top: 25%; left: 45%;'></span></div>";
        print "<span id='count2'></span>";
        print "</div>";
        print "</table>";
        print "</div>";

        print "</div>";


?>