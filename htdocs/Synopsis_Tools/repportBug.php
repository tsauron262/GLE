<?php

/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../main.inc.php');

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Signaler un bug");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Signaler un bug"));

if(isset($_POST['action']) && $_POST['action'] == "send"){
    
     $headers = 'From: no-replay@synopsis-erp.com' . "\r\n" .
     'Reply-To: tommy@drsi.fr' . "\r\n" .
     'X-Mailer: PHP/' . phpversion();
    mail("tommy@drsi.fr", "Bug Gle", "Adresse : ".$_POST['oldUrl']." 
        \n Utlisateur : ".$user->rowid."
        \n Message : ".$_POST['text'], $headers);
        dol_htmloutput_mesg("Merci", $mesgs);
}


print '<form method="post">
    <input type="hidden" name="action" value="send"/>
    <input type="hidden" name="oldUrl" value="'.$_SERVER["HTTP_REFERER"].'"/>
     Décrivez brièvement les conditions du bogue svp <br/><br/>
    <textarea name="text" style="width:801px; height:300px"></textarea><br/><br/>
    <input type="submit" class="butAction" name="valider" value="Envoyer"/>
    </form>';



llxFooter();
?>
