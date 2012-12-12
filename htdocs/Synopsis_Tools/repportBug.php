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
    
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Tools_bug where rowid = " . $_GET['resolu'];
    $sql = $db->query($requete);
    $obj = $db->fetch_object($sql);
    
    
    $headers = 'From: no-replay@synopsis-erp.com' . "\r\n" .
            'Reply-To: tommy@drsi.fr' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    $message = 'Bonjour votre bug signalé sur GLE est passé au statut résolu. \n \n Message : '.$obj->text;

    $userT = new User($db);
    $userT->fetch($obj->fk_user);
    mailSyn($userT->email, "Bug Gle résolu", $message, $headers);
    
    
    $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_Tools_bug set resolu = 1 where rowid = " . $_GET['resolu'];
    $db->query($requete);
}

function getBug($user) {
    global $db, $langs;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_Tools_bug";
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
    $html .= '<table class="noborder"><tr class="liste_titre">';
    if ($user->rights->SynopsisTools->Global->adminBug)
        $html .= "<th>Utilisateur</th>";
    $html .= "<th>Message</th><th>Résolu</th></tr>";
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

        $html .= "<td>" . str_replace("\n", "<br/>", $data->text) . "</td>";

        $html .= "<td>";
        if ($data->resolu)
            $picto = img_picto($langs->trans("Résolu"), 'on');
        else
            $picto = img_picto($langs->trans("Non traité"), 'off');
        if ($user->rights->SynopsisTools->Global->adminBug && !$data->resolu)
            $html .= '<a href="?action=setResolu&resolu=' . $data->rowid . '">' . $picto . '</a>';
        else
            $html .= $picto;
        $html .= "</td>";
        $html .= "</tr>";
    }
    $html .= "</tabel>";
    }
    return $html;
}

function bug($user, $text, $adresse) {
    global $db;
    $headers = 'From: no-replay@synopsis-erp.com' . "\r\n" .
            'Reply-To: tommy@drsi.fr' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    $message = "<a href='" . $adresse . "'>Adresse : " . $adresse . "</a>
        \n Utlisateur : " . $user->getNomUrl() . "
        \n Message : " . $text;

    $requete = "INSERT into ".MAIN_DB_PREFIX."Synopsis_Tools_bug (fk_user, text) VALUES (" . $user->id . ", '" . addslashes($message) . "');";
    $db->query($requete);

    mailSyn("tommy@drsi.fr", "Bug Gle", $message, $headers);
    dol_htmloutput_mesg("Merci", $mesgs);
}

print '<form method="post">
    <input type="hidden" name="action" value="send"/>
    <input type="hidden" name="oldUrl" value="' . $_SERVER["HTTP_REFERER"] . '"/>
     Décrivez brièvement les conditions du bogue svp <br/><br/>
    <textarea name="text" style="width:801px; height:300px"></textarea><br/><br/>
    <input type="submit" class="butAction" name="valider" value="Envoyer"/>
    </form>';

echo getBug($user);



llxFooter();
?>
