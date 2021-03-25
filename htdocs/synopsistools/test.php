<?php

//$chaine = file_get_contents("/data/synchro/test.txt");
//
//        $chaine = str_replace("\x0D\x0A\x20", '', $chaine);
//
////$chaine = str_replace(array("\x0A\x20", "\x0D\x0A\x20"), "", $chaine);
//
//echo "<textarea>".$chaine."</textarea>";
//
//
//die('ll');
//
//
//$file = file_get_contents("/Users/tommy/Downloads/2f332e25-97d0-bc4b-b143-a4af33e58bd8.ics");
//$file = str_replace("\x0A\x20", '', $file);
//die ($file);


require("../main.inc.php");


$ldaphost = "ldaps://91.211.164.250:636/";

$ldaprdn  = 'CN=LDAP ERP GLE,CN=Users,DC=siege,DC=ldlc,DC=com';     // DN ou RDN LDAP
$ldappass = '4@8{4cuGJd';  // Mot de passe associé
//$dir = DOL_DATA_ROOT.'/bimpcore/ca';
//$file = 'ldlcldap.pem';


// Connexion au serveur LDAP
//$ldapconn = null;
//    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
//    ldap_set_option($ldapconn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
$ldapconn = ldap_connect($ldaphost)
    or die("Impossible de se connecter au serveur LDAP.");
//phpinfo();
if ($ldapconn) {

    // Connexion au serveur LDAP
    $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

    // Vérification de l'authentification
    if ($ldapbind) {
        echo "Connexion LDAP réussie.";
    } else {
        echo "Connexion LDAP échouée...<br/>".$dir.$file."<br/>";
    }

    
    
    $sr = ldap_search($ldapconn, 'OU=Olys,OU=Filiales,OU=Groupe LDLC.COM,DC=siege,DC=ldlc,DC=com', '(userPrincipalName=*)');
    $info = ldap_get_entries($ldapconn, $sr);
            
            echo "<br/><br/>".count($info).' result<br/><br/>';
    
    if (ldap_get_option($ldapconn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
        echo "Error Binding to LDAP: $extended_error";
    } else {
        echo "Error Binding to LDAP: No additional information is available.";
    }
            
    echo ldap_error($ldapconn)."<br/><br/>";
}

die('<br/><br/>fin n');

$taux = 3.61;
$coef = 0;
$nbPerdiode = 20;
$capital = 82400;
$vr = 0;
$echoir = 1;
$tauxPM = $taux / 100 / 12;


echo "<br/><br/>";
$loyer = vpm($tauxPM, 0, $nbPerdiode, $capital, -$vr, $echoir);
echo $loyer;
echo "<br/><br/>";
echo va($tauxPM, 0, $nbPerdiode, $loyer, -$vr, $echoir);




echo "<br/><br/>";
$loyer = vpm(0, $coef, $nbPerdiode, $capital, -$vr, $echoir);
echo $loyer;
echo "<br/><br/>";
echo va(0, $coef, $nbPerdiode, $loyer, -$vr, $echoir);




function vpm($taux, $coef, $npm, $va, $vc = 0, $type = 0) {//Calcul loyé avec taux et capital
    if($coef > 0){
        return $coef / 100 * ($va);
    }
    
    
    if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($va) || !is_numeric($vc) || !is_numeric($type)):
        return false;
    endif;

    if ($type > 1 || $type < 0):
        return false;
    endif;

    $tauxAct = pow(1 + $taux, -$npm);

    if ((1 - $tauxAct) == 0):
        return 0;
    endif;

    $vpm = ( ($va + ($vc * $tauxAct)) * $taux / (1 - $tauxAct) ) / (1 + $taux * $type);
    return $vpm;
}

function va($taux, $coef, $npm, $vpm, $vc = 0, $type = 0) {
    if($coef > 0)
        return $vpm / $coef*100;
    
    if (!is_numeric($taux) || !is_numeric($npm) || !is_numeric($vpm) || !is_numeric($vc) || !is_numeric($type)):
        return false;
    endif;

    if ($type > 1 || $type < 0):
        return false;
    endif;

    $tauxAct = pow(1 + $taux, -$npm);

    if ((1 - $tauxAct) == 0):
        return 0;
    endif;

    $va = $vpm * (1 + $taux * $type) * (1 - $tauxAct) / $taux - $vc * $tauxAct;
    return $va;
}

die;


//$_COOKIE['nom'] = "mm";
setcookie("nom", "val5");

echo "<pre>";
print_r($_COOKIE);





die("fin");

require_once(DOL_DOCUMENT_ROOT . "/bimpfinancement/class/BIMP_TOOLS_FINANC.class.php");
llxHeader();



//$capital = 82640;
$taux = 0;
$coef = 0; //7.283;//5.599;
$duree = 4;
$duree2 = 0;
$dureePeriode = 1; //mensuelle
$loyer1 = 3671.24;
$loyer2 = 0;
//$dureePeriode = 1;//mensuelle
//$loyer = 22702.17;
$echoir = true;
$test = 150000;

$coef = 2.447;



$dureeTot = $duree + $duree2;
$loyerMoy = ($loyer1 * $duree + $loyer2 * $duree2) / $dureeTot;

echo "<div class='textField blanc textecentrer'><h2>loyer de base moyen : " . $loyerMoy . " € </h2></div>";


//$capital1 = BIMP_TOOLS_FINANC::calculCapital($loyer1, $duree*12, $dureePeriode, $taux, $coef, $echoir);
//
//
//$capital2 = BIMP_TOOLS_FINANC::calculCapital($loyer2, $duree2*12, $dureePeriode, $taux, $coef, $echoir);
//
//
//$capital = $capital1 + $capital2;
$capital = BIMP_TOOLS_FINANC::calculCapital($loyerMoy, $dureeTot * 12, $dureePeriode, $taux, $coef, $echoir);

echo "<div class='textField blanc textecentrer'><h2>Capital : " . $capital . " € </h2></div>";
echo "<div class='textField blanc textecentrer'><h2>OK : " . (round($capital - $test) == 0 ? "OUI" : "NONNNNNNNNNN" . round($capital - $test)) . " € </h2></div>";

//
//
//$loyerC1 = BIMP_TOOLS_FINANC::calculInteret($capital1, $duree*12, $dureePeriode, $taux, $coef, $echoir);
//
//$loyerC2 = BIMP_TOOLS_FINANC::calculInteret($capital2, $duree2*12, $dureePeriode, $taux, $coef, $echoir);
//
//$loyerC = ($loyerC1 * $duree + $loyerC2 * $duree2) / $dureeTot;
$loyerC = BIMP_TOOLS_FINANC::calculInteret($capital, $dureeTot * 12, $dureePeriode, $taux, $coef, $echoir);

echo "<div class='textField blanc textecentrer'><h2>Loyer calcule : " . $loyerC . " € </h2></div>";
echo "<div class='textField blanc textecentrer'><h2>OK : " . (round($loyerC - $loyerMoy) == 0 ? "OUI" : "NONNNNNNNNNN" . round($loyerC - $loyerMoy)) . " € </h2></div>";












die;




$table_name = "employee";
$backup_file = "/Applications/MAMP/documents/mmmmmm/test.sql";
$db->query("SELECT * INTO OUTFILE '$backup_file' FROM $table_name");




die;



$sql = $db->query("SELECT * FROM `llx_propal_extrafields` WHERE `type` IS NULL ORDER BY `fk_object` ASC");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
while ($ligne = $db->fetch_object($sql)) {
    $propal = new Propal($db);
    $propal->fetch($ligne->fk_object);
    $userT = new User($db);
    $userT->fetch($propal->user_author_id);
    $msg = "Bonjour, suite  a une erreur de ma part, certain devis on perdu leur Secteur, en voici un que vous avez créé " . $propal->getNomUrl(1) . " merci de resaisir le secteur en question. <br/><br/> Désolé de la géne occasioné <br/><br/> Tommy";

    mailSyn2("Secteur Devis", $userT->email, null, $msg);
    echo "<br/>" . $msg . "<br/>";
}



llxFooter();









die;
global $user;
echo "{" . $conf->global->MAIN_SECURITY_HASH_ALGO . "}" . $user->pass_indatabase_crypted . "<br/><br/>";


if ($_REQUEST['action'] == "caisse") {
    $tabVal = array('SAVA', 'AMP', 'ACY', 'ACY', 'ACY', 'B07', 'LYO3', 'LYO6', 'CHY', 'BES', 'BES', 'BES', 'BES', 'CLE', 'CLE', 'CLE', 'GRE', 'MAR', 'MAR', 'MAU', 'MAU', 'MTB', 'MTP', 'MTP', 'NIM', 'NIM', 'NIM', 'PER', 'PER', 'PER', 'STE', 'STP', 'STP', 'VAL');
    $tabVal2 = array();

    foreach ($tabVal as $val) {
        if (isset($tabVal2[$val]))
            $tabVal2[$val] ++;
        else {
            $tabVal2[$val] = 1;
        }
    }

    foreach ($tabVal2 as $val => $nb) {
        if (!$sql = $db->query("SELECT rowid FROM llx_entrepot WHERE label = '" . $val . "';"))
            die("erreur sql ");
        if ($db->num_rows($sql) < 1)
            die("centre introuvable");
        else {
            $ligne = $db->fetch_object($sql);


            for ($i = 1; $i <= $nb; $i++) {
                echo "Caisse " . $i . " " . $val . "<br/>";
                $db->query("INSERT INTO `llx_bc_caisse`(`id_entrepot`, `name`, `status`) VALUES (" . $ligne->rowid . ",'" . "Caisse " . $i . "',0);");
            }
        }
    }
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

// Création d'une réservation pour un transfert: 
$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
//
//foreach(array("resaB") as $resa)
//$errors = $reservation->validateArray(array(
//    'id_entrepot' => 4, // ID entrepot: obligatoire. 
//    'status'      => 201, // cf status
//    'type'        => 2, // 2 = transfert
//    'id_commercial', // id user du commercial (facultatif)
//    'id_equipment'=>110, // si produit sérialisé
//    'id_product', // sinon
//    'id_transfert' => 666,
//    'qty', // quantités si produit non sérialisé
//    'date_from' => date_format(dol_now(), "AAAA-MM-JJ HH:MM:SS"), // date de début de la résa (AAAA-MM-JJ HH:MM:SS) 
//    'note' => $resa // note facultative
//        ));
//
//if (!count($errors)) {
//    $errors = $reservation->create();
//}
//else{
//    echo "erreur".print_r($errors,1);
//}


require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php'; // Si pas déjà require

$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher

$list = $reservation->getList(array(
    'id_transfert' => 666, // ID du transfert
        ), null, null, 'id', 'asc', 'array', array(
    'id', // Mettre ici la liste des champs à retourner.,
    'qty',
    'id_equipment',
    'id_product',
    'status'
        ));

echo "<pre>";
print_r($list);


echo "fin" . print_r($errors, 1);
;
