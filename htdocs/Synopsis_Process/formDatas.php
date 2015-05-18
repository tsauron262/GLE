<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 2 mars 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : formDatas.php
  * GLE-1.2
  */


require_once('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");
$js = <<<EOF
    <script>
      jQuery(document).ready(function(){
            jQuery('#tab').tabs({
                spinner:'Chargement',
              fx:{height: "toggle", opacity: "toggle"},
              cache: true
            });
      });
    function iFrameHeight()
    {
        //find the height of the internal page
        var  h = jQuery('#iframeView')[0].contentWindow.document.body.scrollHeight;
        //change the height of the iframe
        jQuery('#iframeView').css('height',h);
    }

    </script>
EOF;
llxHeader($js,'Valeurs de formulaire');

$processDetId = $_REQUEST['processDetId'];
$processDet = new processDet($db);
$processDet->fetch($processDetId);
$processDet->fetch_process();
$process = $processDet->process;
$element_id= $processDet->element_refid;


           print "<table cellpadding=15 width=100%>";
           print "<tr><th class='ui-widget-header ui-state-default'>Nom du process</th>";
           print "    <td class='ui-widget-content'>".$process->getNomUrl(1)."</td>";
           print "    <th class='ui-widget-header ui-state-default'>Nom du formulaire</th>";
           print "    <td class='ui-widget-content'>".($process->formulaire?$process->formulaire->getNomUrl(1):'')."</td>";
           print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;f&eacute;rence</th>";
           print "    <td class='ui-widget-content' colspan=1>".($process->detail[$processDetId]?$process->detail[$processDetId]->getNomUrl(1):"")."</td>";
           print "    <th class='ui-widget-header ui-state-default'>Statut</th>";
           print "    <td class='ui-widget-content' colspan=1>".($process->detail[$processDetId]?$process->detail[$processDetId]->getLibStatut(4):"")."</td>";
           if ($process->detail[$processDetId]->isRevised){
                $arrNextPrev = $process->detail[$processDetId]->getPrevNextRev();
                $prev = $arrNextPrev['prev'];
                $next = $arrNextPrev['next'];
                $procNext = new processDet($db);
                $procPrev = new processDet($db);
                if ($next && $prev){
                    $procNext->fetch($next);
                    $procPrev->fetch($prev);
                    print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision pr&eacute;c&eacute;dente</th>";
                    print "    <td class='ui-widget-content'>".$procPrev->getNomUrl(1)."</td>";
                    print "    <th class='ui-widget-header ui-state-default'>R&eacute;vision suivante</th>";
                    print "    <td class='ui-widget-content'>".$procNext->getNomUrl(1)."</td>";
                } else if ($prev) {
                    $procPrev->fetch($prev);
                    print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision pr&eacute;c&eacute;dente</th>";
                    print "    <td class='ui-widget-content' colspan=3>".$procPrev->getNomUrl(1)."</td>";
                } else if ($next) {
                    $procNext->fetch($next);
                    print "<tr><th class='ui-widget-header ui-state-default'>R&eacute;vision suivante</th>";
                    print "    <td class='ui-widget-content' colspan=3>".$procNext->getNomUrl(1)."</td>";
                }
           }

           if ($process->typeElement && $element_id > 0  && ($process->detail[$processDetId]->validation_number > 1 || $process->detail[$processDetId]->statut > 0 ))
           {
               print "<tr><th class='ui-widget-header ui-state-default'>El&eacute;ment</th>";
               print "    <td class='ui-widget-content' colspan=1>".$process->typeElement->getNomUrl_byProcessDet($element_id,1)."</td>";
               print "    <th class='ui-widget-header ui-state-default'>Suivi validation</th>";
               print "    <td class='ui-widget-content' colspan=1><a href='".DOL_URL_ROOT."/Synopsis_Process/historyValidation.php?filterProcess=".$processDetId."'><table><tr><td><span class='ui-icon ui-icon-extlink'></span></td><td>Suivi</td></table></a></td>";

           } else if ($process->typeElement && $element_id > 0){
               print "<tr><th class='ui-widget-header ui-state-default'>El&eacute;ment</th>";
               print "    <td class='ui-widget-content' colspan=3>".$process->typeElement->getNomUrl_byProcessDet($element_id,1)."</td>";
           }
           print "</table>";
print "<br/><br/>";
//require_once('Var_Dump.php');

//var_dump::display($processDet);

print "<div id='tab'>";
print "<ul>";
print "<li><a href='#form'>Form</a></li>";
print "<li><a href='#amel'>Am&eacute;lior&eacute;</a></li>";
print "<li><a href='#brut'>Brut</a></li>";
print "</ul>";

print "<div id='form'>";
//display le formulaire dans une iframe
print "<iframe id='iframeView' scrolling='NO'  frameborder='0'  onLoad='iFrameHeight();' style='width:1050px; ' SRC='form.php?fromIframe=1&processDetId=".$processDetId."&process_id=".$process->id."'>";
print "</iframe>";
//remplace les textarea par des div et les select par span
print "</div>";

print "<div id='amel'>";

//Mode amélioré

//Si radio Affiche le nom du selectionner

//Si autocomplete  => trouve la valeur
//Si requete => trouve la valeur
//Si liste => trouve la valeur
//Si function => trouve une astuce
//Si global var => affiche la valeur ??


print "<table width=100% cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-default' colspan=1>Nom";
print "    <th class='ui-widget-header ui-state-default' colspan=1>Description";
print "    <th class='ui-widget-header ui-state-default' colspan=1>Valeur";
$starratingRunonce = true;
foreach($processDet->valeur->valeurByModel as $key=>$val)
{
    $requete = "SELECT m.label,
                       m.description,
                       m.src_refid,
                       t.valueIsChecked,
                       t.code,
                       t.isHidden,
                       t.cssClass,
                       t.repeatTag,
                       t.isStarRating,
                       t.jsCode,
                       t.jsScript,
                       t.cssScript
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as m,
                       " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t
                 WHERE m.type_refid = t.id
                   AND m.id = ".$val->model_refid;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $source = false;
    if ($res->src_refid>0){
        $source = new formulaireSource($db);
        $source->fetch($res->src_refid);
    }

    //Si 3/5 stars => affiche les étoiles
    if($res->isStarRating)
    {
        print "<tr><td class='ui-widget-content'>";
        print "   ".($res->label."x"!="x"?$res->label."&nbsp;(<em>".$val->nom."</em>)":"<em>".$val->nom."</em>");
        print "    <td class='ui-widget-content'>";
        print "   ".$res->description;
        print "    <td class='ui-widget-content'>";

        for ($i=0;$i<$res->repeatTag;$i++)
        {
            if ($i == $val->valeur)
                print "<input CHECKED type='radio' class='".$res->cssClass."' name='".$val->nom."' id='".$val->nom."'>";
            else
                print "<input type='radio' class='".$res->cssClass."' name='".$val->nom."' id='".$val->nom."'>";
        }
        if ($starratingRunonce)
        {
            print "<link href='".DOL_URL_ROOT.$res->cssScript."' rel='stylesheet' type='text/css'>";
            print "<script src='".DOL_URL_ROOT.$res->jsScript."' type='text/javascript'></script>";
            print "<script>".DOL_URL_ROOT.$res->jsCode."</script>";
            print "<script>jQuery(document).ready(function(){ jQuery('#amel').find('input.star-rating-applied').each(function(){ jQuery(this).rating('disable');  });  })</script>";
            $starratingRunonce = false;
        }
        continue;
    }

    print "<tr><td class='ui-widget-content'>";

    //Si champs caché => affiche une icone en plus
    if($res->isHidden > 0){
        print "<table><tr><td><span class='ui-icon ui-icon-radio-on' title='champs cach&eacute;'><td>";
    }

    print "   ".($res->label."x"!="x"?$res->label."&nbsp;(<em>".$val->nom."</em>)":"<em>".$val->nom."</em>");
    if($res->isHidden > 0){
        print "</table>";
    }

    print "    <td class='ui-widget-content'>";
    print "   ".$res->description;
    print "    <td class='ui-widget-content'>";
    //Si checked oui / non
    if($source && $source->type != 'f')
    {
        $ret = $source->uniqElem->getValue($val->valeur);
        if (is_array($ret))
            foreach($ret as $key=>$val) print $val;

        if ($ret < 0) print $source->uniqElem->error;
    } else if($source && $source->type == 'f')
    {
        $ret = $source->uniqElem->getValue($val->valeur);
        if ($ret?print$ret:'');

        if ($ret < 0) print $source->uniqElem->error;
    } else {
        if ($res->valueIsChecked > 0 && $val->valeur > 0)
        {
            print "OUI";
        } else if ($res->valueIsChecked > 0 && !$val->valeur > 0)
        {
            print "NON";
        } else {
            //Si input / textarea => affiche la valeur
            print "   ".$val->valeur;
        }
    }
}
print "</table>";
print "</div>";
//Mode Brute
print "<div id='brut'>";
print "<table width=100% cellpadding=10>";
print "<tr><th class='ui-widget-header ui-state-default' colspan=1>Nom";
print "    <th class='ui-widget-header ui-state-default' colspan=1>Description";
print "    <th class='ui-widget-header ui-state-default' colspan=1>Valeur";
foreach($processDet->valeur->valeurByModel as $key=>$val)
{
    print "<tr><td class='ui-widget-content'>";
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE id = ".$val->model_refid;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    print "   ".$res->label;
    print "    <td class='ui-widget-content'>";
    print "   ".$res->description;
    print "    <td class='ui-widget-content'>";
    print "   ".$val->valeur;
}
print "</table>";
print "</div>";

print "</div>";


llxfooter();
?>