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

if (isset($_POST['action']) && $_POST['action'] == "send") {
    bug($user, $_POST['text'], $_POST['oldUrl']);
}
if (isset($_GET['action']) && $_GET['action'] == "setResolu") {
    
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsistools_bug where rowid = " . $_GET['id'];
    $sql = $db->query($requete);
    $obj = $db->fetch_object($sql);
    
    
    $message = 'Bonjour votre bug signalé sur GLE est passé au statut résolu. '."\n\n"
            .'Si le bug réapparaît ou est toujours présent, merci de le resignaler : '."\n\n"
            .'Message : '.$obj->text;

    $userT = new User($db);
    $userT->fetch($obj->fk_user);
    mailSyn($userT->email, "Bug Gle résolu", $message);
    
    
    $requete = "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug set resolu = 1 where rowid = " . $_GET['id'];
    $db->query($requete);
}
if (isset($_GET['action']) && $_GET['action'] == "setAnnuler") {
    
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsistools_bug where rowid = " . $_GET['id'];
    $sql = $db->query($requete);
    $obj = $db->fetch_object($sql);
    
    
    $message = 'Bonjour votre bug signalé sur GLE est passé au statut Annulé. '."\n\n"
            .'Message : '.$obj->text;

    $userT = new User($db);
    $userT->fetch($obj->fk_user);
    mailSyn($userT->email, "Bug Gle Annulé", $message);
    
    
    $requete = "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug set resolu = 2 where rowid = " . $_GET['id'];
    $db->query($requete);
}


if (isset($_GET['action']) && $_GET['action'] == "setInfo") {
    
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsistools_bug where rowid = " . $_GET['id'];
    $sql = $db->query($requete);
    $obj = $db->fetch_object($sql);
    
    
    $message = 'Bonjour je n\'ai pas pu traiter votre bug signalé sur GLE merci de le resignaler avec plus d\'informations ou de contacter votre assistance technique. '."\n\n"
            .'Message : '.$obj->text;

    $userT = new User($db);
    $userT->fetch($obj->fk_user);
    mailSyn($userT->email, "Bug Gle Précisions", $message);
    
    
    $requete = "UPDATE ".MAIN_DB_PREFIX."synopsistools_bug set resolu = 3 where rowid = " . $_GET['id'];
    $db->query($requete);
}

function getBug($user) {
    global $db, $langs;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."synopsistools_bug";
    if (!$user->rights->SynopsisTools->Global->adminBug)
        $requete .= " WHERE fk_user = " . $user->id;
    if ($user->rights->SynopsisTools->Global->adminBug)
        $requete .= " ORDER BY resolu ASC, rowid desc";
    else
        $requete .= " ORDER BY rowid desc";
    $sql = $db->query($requete);
    $html = '';

    if($db->num_rows($sql) > 0){
    echo "<br/><br/>";
    print_titre($langs->trans("Vos ancien ticket de bug"));
    echo "<br/>";
    $html .= '<table class="noborder fullTable"><tr class="liste_titre">';
    if ($user->rights->SynopsisTools->Global->adminBug)
        $html .= "<th>Utilisateur</th>";
    $html .= "<th>Résolu</th><th>Message</th></tr>";
    $pair = false;
    while ($data = $db->fetch_object($sql)) {
        if ($pair)
            $class = "pair";
        else
            $class = "impaire";
        $pair = !$pair;
        $html .= "<tr class='" . $class . "'>";

        if ($user->rights->SynopsisTools->Global->adminBug) {
            $userT = new User($db);
            $userT->fetch($data->fk_user);
            $html .= "<td>" . $userT->getNomUrl() . "</td>";
        }


        $html .= "<td>";
        
            $pictoS = img_picto($langs->trans("Annulé"), 'delete');
            $pictoI = img_picto($langs->trans("Informations"), 'history');
        if ($data->resolu == 1)
            $picto = img_picto($langs->trans("Résolu"), 'on');
        elseif ($data->resolu == 2)
            $picto = $pictoS;
        elseif ($data->resolu == 3)
            $picto = $pictoI;
        else
            $picto = img_picto($langs->trans("Non traité"), 'off');
        if ($user->rights->SynopsisTools->Global->adminBug && !$data->resolu)
            $html .= '<a href="?action=setResolu&id=' . $data->rowid . '"> ' . $picto . ' </a>';
        else
            $html .= $picto;
        if ($user->rights->SynopsisTools->Global->adminBug && !$data->resolu == 2)
            $html .= ' <a href="?action=setAnnuler&id=' . $data->rowid . '"> ' . $pictoS . ' </a>';
        if ($user->rights->SynopsisTools->Global->adminBug && $data->resolu < 1)
            $html .= ' <a href="?action=setInfo&id=' . $data->rowid . '"> ' . $pictoI . ' </a>';
        $html .= "</td>";
        
        $html .= "<td class='resize'>" . str_replace("\n", "<br/>", $data->text) . "</td>";
        
        $html .= "</tr>";
    }
    $html .= "</table>";
    }
    return $html;
}

function bug($user, $text, $adresse) {
    global $db;
    $message = "<a href='" . $adresse . "'>Adresse : " . dol_trunc($adresse, 80) . "</a>
        \n Utlisateur : " . $user->getNomUrl() . "
        \n Message : " . $text;

    $requete = "INSERT into ".MAIN_DB_PREFIX."synopsistools_bug (fk_user, text) VALUES (" . $user->id . ", '" . addslashes($message) . "');";
    $db->query($requete);

    mailSyn("tommy@drsi.fr", "Bug Gle", $message);
    dol_htmloutput_mesg("Merci", $mesgs);
}

print '<form method="post">
    <input type="hidden" name="action" value="send"/>
    <input type="hidden" name="oldUrl" value="' . str_replace($dolibarr_main_url_root, DOL_URL_ROOT, $_SERVER["HTTP_REFERER"]) . '"/>
     Décrivez brièvement les conditions du bogue svp <br/><br/>
     
Attention si vous rencontrer des probléme d\'affichage sur l\'agenda, merci de vider le cache de votre navigateur puis de faire un nouvel essaie avant de signaler le bug.<br/><br/>



    <textarea name="text" style="width:770px; height:300px"></textarea><br/><br/>
    <input type="submit" class="butAction" name="valider" value="Envoyer"/>
    </form>';

echo getBug($user);



llxFooter();
?>
