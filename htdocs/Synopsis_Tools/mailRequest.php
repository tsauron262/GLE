<?php

require '../main.inc.php';


if(isset($_REQUEST['action']) && $_REQUEST['action'] == "annulObj" && isset($_SERVER["HTTP_REFERER"]) && $_SERVER["HTTP_REFERER"] != ''){
    mailSyn("tommy@drsi.fr", "Demande annulation", "Annuler Obj : ".$_SERVER["HTTP_REFERER"]);
    header("Location:" . $_SERVER["HTTP_REFERER"]);
}


echo "Rien a faire";