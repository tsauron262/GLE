<?php

/*
 */
/**
 *
 * Name : histo_imputations.php
 * BIMP-ERP-2
 */
/*
 * Actuellemnt 
 * 
 * Euros Par heure Réalisé   =      Commande    /     nbHeure Réalisé Total         X     nbHeure Realisé Période Tache
 */





ini_set('max_execution_time', 40000);
ini_set("memory_limit", "1200M");


require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/synopsisprojetplus/class/imputations.class.php');

$statImputations = new statImputations($db);



$statImputations->proccAction();


$statImputations->debutPara = ($statImputations->fromProj ? 'fromProjet=1&id=' . $statImputations->projet->id . '&' : '');

$js = "
<link href='css/imputations.css' type='text/css' rel='stylesheet'>
<script>
jQuery(document).ready(function(){
    jQuery('.div_scrollable_medium tr').each(function(){
        var self = jQuery(this);
        jQuery(this).mouseover(function(){
            self.addClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).addClass('ui-state-hover');
            });
        });
        jQuery(this).mouseout(function(){
            self.removeClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).removeClass('ui-state-hover');
            });
        });
    });
    jQuery('SELECT#userid').change(function(){
        jQuery('SELECT#userid').parents('form').submit();
    });
    jQuery('.tousUser').click(function(){
        window.location = '?" . $statImputations->debutPara . "userid=-2';
        return false;
    });

});
</script>";

llxHeader($js, "Imputations");

dol_htmloutput_mesg($mesg, $mesgs);

if ($statImputations->fromProj) {
    $head = project_prepare_head($statImputations->projet);
    dol_fiche_head($head, 'imputations', $langs->trans("Project"));
//saveHistoUser( $statImputations->projet->id, "projet", $statImputations->projet->ref ) ;
}


foreach ($statImputations->messErreur as $erreur) {
    echo "<div class='error'>" . $erreur . "</div>";
}

//print '    <div id="struct_main" class="activities">';

$statImputations->getMenu();

$statImputations->getTabHead();

$statImputations->getTabBody();

$statImputations->getTabFoot();

$statImputations->getSortie();

$statImputations->getFormDoc();


global $logLongTime;
$logLongTime = false;
llxFooter("<em>Derni&egrave;re modification </em>");

?>
