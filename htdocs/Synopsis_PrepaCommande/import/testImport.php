<?php

/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 24 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : testImport.php
 * BIMP-ERP-1.2
 */
$maxFileImport = 20;
$tempDeb = microtime(true);
global $tabStat;
$tabStat = array('c' => 0, 'd' => 0, 'pc' => 0, 'pd' => 0, 'pcd' => 0, 'ef' => 0);

ini_set('max_execution_time', 40000);
ini_set("memory_limit", "1200M");
$displayHTML = true;
if ($_REQUEST['modeCli'] == 1) {
    $displayHTML = false;
}
require_once('pre.inc.php');
//  require_once('Var_Dump.php');


define("NOT_VERIF", true);

require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

$dir = $conf->global->BIMP_PATH_IMPORT;
//Contrat
//ChangeLog
//Ajouter le statut expédition sur l'écran principale
//Intervention (fiche) saisie multiple des lignes de contrats pour les interventions de maintenances.
//Changement de la page d'accueil des contrats=> mise en place du dashboards et des widgets (conf 23)
//Modification du modele de mail pour le changement de statut de preparation de commande.
//Ajout d'un boutton dans le detail des lignes de contrat pour supprimer une ligne de contrat en mode brouillon (sinon il faut la cloturer)
//UTF8 pour les mail de la messagerie "prepa commande"
//Expedition => statut notifier
//Liste prépa commande / 10/50 Derniers contrat à saisir
//Problème icone dans commande/card.php?id= (contrat et déplacement)
//Notification de nouvelle intervention :> sujet manquant (mauvais codage des caractères accentués)
//Widget Logistique => toutes expedition à préparer. => Lister les commandes avec date de livraison, le dépot à, => faire 4 colonnes :> La ref commande, la date de livraison, le destinataire deu tas à monter etat financier (icone) + Attention, commanbde expédié si total(effacé)
//Renouvellemenent dans modif société faute de frappe
//Prob association FI / prepacommande à posteriori
//Mise en place dashboard ventes->expédition
//Ajouter une note pour les expéditions + livraisons
//Synchro pass ldap
//Droit de modifier DI après validation
//validation expedition :> envoi mail to l'user de la pile (cf table BIMP_site) cc GESTPROD,
//prob stat produit avec type contrat
//changer nom dialog dans validation financière + validation logistique
//utf8encode sur add de livraison => prob KO
//Historique de navigation + debug DI
//Prob prepacommande :> si SAV prob car pas de produits => prob!!!!! =>
//Import contrat => le type doit être à 4 au lieu de 0 en SAV
//Notification sur changement de statut de FI => si pris en charge
//Menu top => menu déroulant
//Changelog du 13/12
// interface config type interv
// si interv par contrat de maintenance et qte > 0 => alors decompte + affichage decompte dans fiche et fiche detail et fiche interv(contrat)
// interv restant au ticket
// ajouter 2 type Interv => hotline et telemaintenance => si selectionner ticket -- sinon visite -- =>
// renouveller contrat avec ticket = reinit total Ticket ??
// highlight si tkt < seuil a definir => GMAO_TKT_RESTANT_WARNING a configurer
// error si tkt < 0
// fiche FI dans contrat => FI par type
// tests modif FI apres validadtion si droit
// lier interv à contrat a posteriorie
// Liste fiche intervention => lien vers commande et contrat
// Nouveau droit :> rattacher une FI à une commande ou un contrat
// debug droit => changement de numéro pour droit de modifier FI apres validation
// ContratDetail => reorganisation de l'interface
// ContratDetail => debug accents
// ContratDetail => contrat "illimité", pris en charge du nouveau systeme pour la maintenance (interface produit)
// Contrat => debug interface (mod/add, accents, ', fk_contrat_prod pas pris en compte si SAV, doublon dans la requete sql de getLineDet )
// Info Contrat => debug interface + reorg + doubleclique=> acces au details
//
// prob Hotline + ticket, et intervention + ticket =>  idem dans contrat
// Renouvellement de contrats mettre 45j avant
// enlever les zero dans les qté à 0 et prix à zéro => mauvais modele utilisation de Tesla recommandé
// enlever les zero dans les qté à 0 et prix à zéro
// prob  accents dans message (prepacommande)
// Dans contart fiche si contrat de maintenance sans visite sur site => KO
// Total HT inactif dans contrat
// Verif contrat ticket illimité
// Widget contrat ref KO
// Interventions-html_response.php => virer le prix
// GetResults-html_response.php => Prob => pas à jour sous total recap
// Commercial signataire de contrat Mr chassing
// Interface produit
//ChangeLog du 9/12
//BI install nouveaux rapports
// lié contrat_prop et contrat à la creation du contrat via l'interface
// accents et ' ds sla et clause
// dflt TVA 19.6
// voir si modif contrat n'écrase pas les paramètres déjà setter au chargement (prix, qte ...)
// Nb Visite / an sur contrat/fiche => KO si mis à 10 dans modifier , reste à 0 en mode view
// prepacom module contrat => Bouton ajouter KO + modification KO apres ajout auto
// perte du commandedet dans l'interface modifier de contratMixte - non reproductible
// add ancre dans DI et FI pour navigation facile (ascenseur)
// prepacommande.php.id=3 => trier extraKey Voir avec Florence + createDI
// date ko en mode edit fiche
// fiche interv =>  2colonnes pour l'affichage + trie ordre FI
// demande Interv=> voir trie Florence
// addslash quality et extra
// ' ds interv fiche extra, fiche et qualité
//1 - Ref et
//2 Tiers
//3- Commandes et
//4 préparation
//5- Date et
//6 durée totale
//7- Attribué à
//8 effectué par
//9- Etat intervention
//10 date prochain RDV
//11 Installation et
//12 Intervention
//13 Heures d'arrivée AM
//14  et Heures de départ AM
//15 Heures d'arrivée PM
//16 et Heures de départ PM
//17 Préconisation (agrandir le champ de saisie si c'est possible pour meilleur confort de travail
//18 Proposition contrat.
//Interv-prepa => bouton creer qui disparait
//Intervention: agrandi les champs extra de type textarea
//Intervention => prepacommande => Recap Marge
//Total :> Vendu => prend le 3 presta.i comme étant => total vendu
//                  prend le dép prévu et la presta prévu => total Prevu
//                  prend le dép prévu et la presta réalisé => total Réalisé
//+ recap :> différence entre vendu / réalisé
//+ warning highlight => si Prévu > Vendu
//+ warning erreur => Si Réalisé > Vendu
//+ si vendu OK => image en plus
// agrandir le select box de produit/contrat dans add/mod dialog contrat => param dans adfmin/produit.php
// suppr pdf contrat
// modif index.php (contrat)
// Note public accent preepacom
// Note public editable ds fiche commande(prepacom)
// total HT filtre sur les contrats Actif
// ref client/code client ds pdf contrat
// stats produit => redirige vers liste_orig.php
// liste_orig.php => prob marche pas avec contrat et deplacement
// Contrat contrat/card.php => prob ordre select_user
// Remettre liste au lieu de carte dans product/liste.php recherche par ref
// Contrat -> commandedet => prob accent
// total ht ds contrat bug
// contrat en double si non rattaché à un produit http://192.168.1.10/BIMP-ERP/contrat/card.php?id=3
// limite contrat warning 30j
// BI stats trimestre + annuel
// BI doubleValue in duration (2 rapport 4 ss rapport)
//Changelog du 7/12
//Case forfait dans modèle Pluton
//Pour le PDF FI, je vais regarder ce qui cloche dans le champs préconisation du technicuen (longtext au lieu de varchar)
//Sous total temps et totalHT => dans vendu OK / KO
//Font pdf Calibri => prob minuscule/majuscule dans la $familly de la font italic
//Rajoute formation interne dans les types d'interventions
//liste des utilisateurs dans configPrix.php
//note public => onClick => modify
//accent prob dans date => fiche interv => idem dans qualité et surment index.php => liste FI decembre
//rapport.php => synopsisdemandeinterv => prenom / nom dans le menu déroulant
//Vendu par gamme => valign=top
//Modif modele Einstein
//PrepaCommande champs intervenant => pas trier dans le bon ordre (prenom nom)
//Date dans FI=> prob accent dans liste et card.php
//LDAP
//ChangeLog du 29/11/2010
//Stats rapport BI intervention par mois % intervenant % type Interv % societe
//Stats php Demande et fiche Interv % intervenant % mois % societe
//Droit de voir toutes les DI dans les rapports PHP
//Droit de voir toutes les FI dans les rapports PHP
//Debug BI et BI+ECM
//Adaptation jasper 3.7
//Historique renouvellement contrat (/contrat/info.php?id=)
// delete avenant contrat marche pas
// prorata temporis
// ticket intervention count intervention lié à la ligne de commande
// prob create FI et createDI =< pas de fk_contrat ni de fk_contratdet
// Reset prix DI->FI => pas pour deplacement=> recalculer le deplacement
// DI->FI > perd la reference vers la commande
// DI et FI => adaptation des interfaces pour les ocntrats mixtes
// bug prepacommande tableau totaux
// droit de voir le total dans les FI
// total lignes pour les FI
// suppr detail Quality DI
// ref de produit avec / et \
// Fiche d'interventions PDF
// :> Fiche intervention :> passage de demande interv à fiche intervention => reset ttemps param de conf->global + modif code
// :> Dans contrat mettre le type de contrat à proposer => creer un chmaps dans la fiche d'interv =>champs radio
// :> Interlocuteur => contact client => champs FI (contact commande)
// pa16 + cbk +pa17 :> Raison champs à rajouter avec Terminé / en cours / Attente client
// pa21 a pa24:> !! Horaire début et fin par 1/2 journée
// :> Préco du technicien a rajouter
// :> Remarque du client => champs à rajouter
// :> Attention case à cocher pour réservé client
// pa4 :> tarification => FPR30
// pa4 :> cout horaire seulement au temps passé => FPR30
// pa5 :> nb heure :> qty de temps dans l'intervention dans le FPR30
// pa6 :> Total Mo :> Total Euro main d'oeuvre
// pa7 :> déplacement :> la somme des déplacement de la FI
// pa8 :> Pièce :> euros HT :> = Somme du matériél de la comande
// pa9 :> Inter. Urgent :> Somme en Euro :> dans la commande :> Code FPR40
// pa10 :> Récupération de données :> prix en euro Code inconnu A voir avec Florence
// pa11 :> Total HT de tout
// pa12 :> TVA + TTC
// pa13 :> champs total bon à rajouter x2
// ChangeLog du 23/11
//Rajouter un lien entre interv et prepa de commande
//Faute accent dans demande à gauche et dans attribué a
//Changer la présentation des contrats si 4 applecare => alors 4 lignes de contrat
//Interface spécifique pour les commerciaux pour voir tous les éléments d'un contrat
//Logistique => afficher Num semaine si provisoire, sinon date complete
//Bug dans ajouter au contrat => ne fait rien
//Prenom - nom
//Total du tableau, les frais de déplacement total KO
//expédition: validation de l'expédition correspond pas à la fiche expédition
//expédition : tableau produit éxpédié dans prepa commande => prob accents
//Logistique notification si date de dispo dépasser
//Qte à 1 dans demande interv par défault
//Logistique :> numéro de la semaine dans js calendar
//Deplacement fichier OK et KO
//Verif que le destinataire des mails de notification inclut bien l'auteur de la commande

$webContent = "";
if ($displayHTML) {
    llxHeader();
}


if (is_dir($dir)) {

    $filename = $dir . "temp/.importRunning";

    if (file_exists($filename)) {
        if (isset($_REQUEST['deblocker']) && $_REQUEST['deblocker'])
            unlink($filename);
        else {
            $dateI = date("d-m-Y H:i", file_get_contents($filename));
            print "Un import est d&eacute;j&agrave; en cours depuis : " . $dateI . "<br/><br/>";
            $dateDeblock = date("d-m-Y H:i", file_get_contents($filename) + 60 * 3);
            if ($dateDeblock < date("d-m-Y H:i"))
                echo "<a href='?deblocker=true'>Déblocker</a>";
            else
                echo "Deblockage possible aprés " . $dateDeblock;
            exit;
        }
    }
    touch($filename);
    file_put_contents($filename, time());
    $mailContent = "";
    $mailSumUpContent = array('nbFile' => 0, 'nbLine' => 0, 'nbLigneModif' => 0, 'commande' => array());
    $fileArray = array();
    $imported = "/imported/";

    $premiereDesc = true;

    global $langs, $conf;

    $webContent .= " <a href='index.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Retour</span></a>";
//$arrayImport = array();
//1 ouvre le rep et trouve les fichiers



    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Chargement catégorie                                                 |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */
//$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label = 'Famille'"; // AND ( level=2 OR level is null)";
//$sql = requeteWithCache($requete);
//if ($sql) {
//    $res = fetchWithCache($sql);
//    $gammeCatId = $res->rowid;
//}
//else
    $gammeCatId = getCat("Famille");
    $familleCatId = getCat("Gamme");
    $collectionCatId = getCat("Collection");
    $natureCatId = getCat("Nature");
    $selectBIMPCatId = getCat("Selection Bimp");
//$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label = 'Gamme'"; // AND ( level=2 OR level is null)";
//$sql = requeteWithCache($requete);
//$res = fetchWithCache($sql);
//$familleCatId = $res->rowid;
//$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label = 'Collection'"; // AND ( level=2 OR level is null)";
//$sql = requeteWithCache($requete);
//$res = fetchWithCache($sql);
//$collectionCatId = $res->rowid;
//$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label = 'Nature'"; // AND ( level=2 OR level is null)";
//$sql = requeteWithCache($requete);
//$res = fetchWithCache($sql);
//$natureCatId = $res->rowid;
//$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label LIKE '%lection Bimp'"; // AND ( level=2 OR level is null)";
//$sql = requeteWithCache($requete);
//$res = fetchWithCache($sql);
//$selectBIMPCatId = $res->rowid;


    global $gammeCatId, $familleCatId, $collectionCatId, $collectionCatId, $natureCatId, $selectBIMPCatId;

//  exit();
    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Import                                                               |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */
    $mailContent .= "<table width=600 cellpadding=10>" . "\n";
//  $debugLigne=120;
//  $idebug=0;




    $mailContent .= "Liste des fichiers: ";


    $mailHeader = "<div><table border=0 width=700 cellpadding=20 style='border-collapse: collapse;'><tr><td><img height=100 src='" . DOL_URL_ROOT . "/theme/" . $conf->theme . "/Logo-72ppp.png'/></div>" . "\n";
    $mailHeader .= "<td valign=bottom><div style='color: #0073EA; font-size: 25pt;'>Rapport d'importation</div><br/>" . "\n";
    $mailHeader .= "</table>" . "\n";
    $mailHeader .= "<table  border=0 width=700 cellpadding=10 style='border-collapse: collapse;'>" . "\n";
    $mailHeader .= "<tr><th style='background-color: #0073EA; color: #fff;' colspan=3>Les commandes ajout&eacute;es / modifi&eacute;es" . "</td>" . "\n";

    function fileToTab($file) {
        $tabVal = array();
        $tabEntete = array();
        $content = file_get_contents($file);
        $lines = explode("
", $content);
        $i = 0;
        foreach ($lines as $key => $val) {
            $i++;
            $elems = explode("	", $val);
            $j = 0;
            foreach ($elems as $elem) {
                $j++;
                if ($i == 2)
                    $tabEntete[$j] = $elem;
                elseif ($i != 1)
                    $tabVal[$i - 2][$tabEntete[$j]] = $elem;
            }
        }
        return $tabVal;
    }

    $cntFile = -2;
    if (is_dir($dir)) {
        $tabImportOK = array('commande' => array(), 'propal' => array());
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false && $cntFile <= $maxFileImport) {
                $OKFile = true;
                $remArrayComLigne = array(); // array contenant les commandes importer dans le fichier, pour supprimer les lignes en trops
                $File = array();
                $cntFile++;
                if ($file == ".." || $file == '.')
                    continue;
                if (preg_match('/^\./', $file))
                    continue;
                //2 ouvre le fichier
                if (is_readable($dir . "/" . $file) && is_file(($dir . "/" . $file))) {
                    if (isset($_REQUEST['deblocker']) && $_REQUEST['deblocker']) {
                        $resultMv = rename($dir . "/" . $file, $dir . 'quarantaine/' . $file);
                        echo "<div class='erreur' style='color:red;'>Le fichier " . $file . " a été mie en quarentine.</div><br/><br/>";
                        $_REQUEST['deblocker'] = false;
                        continue;
                    }
                    $webContent .= "<div class='titre'>fichier : " . $file . "</div>";
                    $mailContent .= '<div style="color: 00FFC6;">' . $file . '</div>' . "\n";
                    $fileArray[] = $file;
                    //iconv file
                    $webContent .= "<table width=600 cellpadding=10><tr><th class='ui-state-default ui-widget-header'>Conversion du fichier</th>";
                    exec("/usr/bin/perl -p -e 's/\r/\n/g' < " . $dir . "/" . $file . "  > " . $dir . "temp/" . $file . ".preparse");
                    exec("/usr/bin/iconv -f MAC -t UTF8 " . $dir . "temp/" . $file . ".preparse > " . $dir . "temp/" . $file . ".iconv");
                    if (is_file($dir . "temp/" . $file . ".iconv")) {
                        $webContent .= "<td class='ui-widget-content'>OK</td>";
                        if ($file == "user.txt") {
                            $tabVal = fileToTab($dir . "temp/" . $file . ".iconv");
                            foreach ($tabVal as $val) {
                                if ($val['PriGMocMail'] == '')
                                    echo "Pas d'email pour : " . $val['PriLib'] . "<br/>";
                                else {
                                    $tabMail = array($val['PriGMocMail'],
                                        str_replace("cicenter", "bimp", $val['PriGMocMail']),
                                        str_replace("bimp", "cicenter", $val['PriGMocMail']),
                                        str_replace("cicervice", "bimp", $val['PriGMocMail']),
                                        str_replace("bimp", "cicervice", $val['PriGMocMail']));
                                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE email IN ('" . implode("','", $tabMail) . "')";
                                    $result = $db->query($sql);
                                    if ($db->num_rows($result) == 0) {
                                        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE ref_ext IN ('" . $val['PRIID'] . "')";
                                        $result = $db->query($sql);
                                        if ($db->num_rows($result) == 1)
                                            echo "Pas de correspondance mail correction par id dans " . MAIN_DB_PREFIX . "user " . $val['PriGMocMail'] . " - ID - " . $val['PRIID'] . "<br/>";
                                    }

                                    if ($db->num_rows($result) > 0) {
//                            if(isset($val['GleId'])){
                                        $ligne = $db->fetch_object($result);
//                                    echo $val['PRIID'] . " " . $ligne->rowid . "<br/>";
                                        setElementElement("idUser8Sens", "idUserGle", $val['PRIID'], $ligne->rowid);
//                            foreach($val as $nom => $value)
//                                echo $nom." : ".$value."<br/>";
                                    }
                                    if ($db->num_rows($result) > 1)
                                        echo "Plusieur résultat pour l'email : " . $val['PriGMocMail'] . " - ID - " . $val['PRIID'] . "<br/>";
                                    elseif ($db->num_rows($result) == 0)
                                        echo "Pas de résultat pour l'email : " . $val['PriGMocMail'] . " - ID - " . $val['PRIID'] . "<br/>";
                                }
                            }
                        } else {
                            $content = file_get_contents($dir . "temp/" . $file . ".iconv");
                            $lines = preg_split("/\n/", $content);
                            //3 analyse les colonnes
                            $ligneNum = 0;
                            $arrDesc = array();
                            $arrConvNumcol2Nomcol = array();
                            $mailSumUpContent['nbFile'] ++;
                            foreach ($lines as $key => $val) {
                                if (!strlen($val) > 10)
                                    continue;
                                $cols = preg_split("/[\t]/", $val);
                                if ($ligneNum == 0) {
                                    $arrDesc = $cols;
                                    $ligneNum++;
                                } else if ($ligneNum == 1) {//Comprend pas
                                    foreach ($cols as $key1 => $val1) {
                                        $arrConvNumcol2Nomcol[$key1] = $val1;
                                        $arrayImport[0][$val1] = $arrDesc[$key1];
                                        $arrayImport[1][$val1] = $val1;
                                    }
                                    $ligneNum++;
                                } else {
                                    foreach ($cols as $key2 => $val2) {
                                        switch ($arrConvNumcol2Nomcol[$key2]) {
                                            case 'PcvDate': {
                                                    //convert to epoch
                                                    if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/", $val2, $arrTmp)) {
                                                        $val2 = strtotime($arrTmp[3] . "-" . $arrTmp[2] . "-" . $arrTmp[1]);
                                                    }
                                                }
                                                break;
                                            case 'ArtPrixBase':
                                            case 'PlvPUNet':
                                            case 'PlvPA':
                                            case 'PcvMtHT': {
                                                    $val2 = preg_replace('/,/', '.', $val2);
                                                }
                                                break;
                                            case 'TaxTaux': {
                                                    $val2 = preg_replace('/,/', '.', $val2);
                                                    if ($val2 . "x" == "x")
                                                        $val2 = "0";
                                                }
                                                break;
                                            default: {
                                                    $val2 = preg_replace('/\'/', '\\\'', $val2);
                                                }
                                                break;
                                        }
//                                $arrayImport[$cntFile][$key][$arrConvNumcol2Nomcol[$key2]] = utf8_decode($val2);
                                        $File[$key][$arrConvNumcol2Nomcol[$key2]] = utf8_decode($val2);
                                    }
                                    $ligneNum++;
                                }
//    var_dump::display($cols);
                            }
                            unlink($dir . "temp/" . $file . '.iconv');
                            unlink($dir . "temp/" . $file . '.preparse');
                        }
                    } else {
                        $webContent .= "<tdclass='ui-widget-content'>Erreur de conversion : ".$dir . "temp/" . $file . ".iconv Introuvable</td>";
                        $OKFile = false;
                    }











                    /*
                     * Deb rajout
                     */






//              foreach ($arrayImport as $listFiles => $File) {
                    foreach ($File as $key => $val) {
                        $val['PlvLib'] = addslashes(stripslashes(stripslashes($val['PlvLib'])));
                        if (is_array($val))
                            foreach ($val as $cle => $val2)
                                $val[$cle] = utf8_encode($val2);
                        else
                            $val = utf8_encode($val);
//        $idebug++;
//        if($idebug == $debugLigne) { print "debug break"; break;}
                        if ($key < 2)
                            continue;
                        if ($val['PcvCode'] . "x" == "x")
                            continue; //8sens export cas 1
                        if ($val["PcvCode"] == 'Fin') {  //8sens export cas 2
                            break;
                        }

                        if (strpos($val["PcvCode"], "C") !== false)//C'est une commande
                            $typeLigne = "commande";
                        else
                            $typeLigne = "propal";
                        $paysGlobal = processPays($val['PysCode']);
                        $externalUserId = $val['PcvGPriID'];
                        $internalUserId = processUser($externalUserId, $val["PcvCode"], $file, $val);
                        if (!$internalUserId)
                            $OKFile = false;
                        else {
                            $mailSumUpContent['nbLine'] ++;
                            $webContent .= "<tr><th class='ui-state-default ui-widget-hover' colspan=2>Ligne: " . $key . "  " . ($typeLigne == "commande" ? "Commande" : "Propal") . ":" . $val["PcvCode"] . "</th>";
                            $mailContent .= "<tr><th style='color: #fff; background-color: #0073EA;' colspan=2>Ligne: " . $key . "  " . ($typeLigne == "commande" ? "Commande" : "Propal") . ":" . $val["PcvCode"] . "</th>" . "\n";

                            /*
                              +--------------------------------------------------------------------------------------------------------------+
                              |                                                                                                              |
                              |                                         Secteur societe                                                      |
                              |                                                                                                              |
                              +--------------------------------------------------------------------------------------------------------------+
                             */
                            $secteurActiv = false;
                            if ($val['CliActivEnu'] . "x" != "x") {
                                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "c_secteur WHERE LOWER(code) = '" . strtolower(SynSanitize($val['CliActivEnu'])) . "' ";
                                $sql = requeteWithCache($requete);
                                if ($db->num_rows($sql) > 0) {
                                    $res = fetchWithCache($sql);
                                    $secteurActiv = $res->id;
                                } else {
                                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "c_secteur (code,libelle,active) VALUES ('" . SynSanitize($val['CliActivEnu']) . "','" . addslashes($val['CliActivEnu']) . "',1)";
                                    $sql = requeteWithCache($requete);
                                    $secteurActiv = $db->last_insert_id('".MAIN_DB_PREFIX."c_secteur');
                                }
                            }


                            /*
                              +--------------------------------------------------------------------------------------------------------------+
                              |                                                                                                              |
                              |                                         La societe                                                           |
                              |                                                                                                              |
                              +--------------------------------------------------------------------------------------------------------------+
                             */

                            if (isset($tabImportOK['soc'][$val["CliCode"]])) {
                                $socid = $tabImportOK['soc'][$val['CliCode']]['socid'];
                                $livAdd = $tabImportOK['soc'][$val['CliCode']]['livAdd'];
                                $socContact = $tabImportOK['soc'][$val['CliCode']]['socContact'];
                            } else {
                                $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Soci&eacute;t&eacute;";
                                $mailContent .= "<tr><th style='color: #fff; background-color: #0073EA;'>Soci&eacute;t&eacute;</th>" . "\n";
                                $nomSoc = $val["CliLib"];
                                $codSoc = $val["CliCode"];
                                $socid = "";
                                $assujTVA = 0;
                                $typeEnt = 0;

                                switch ($val['CliCategEnu']) {
                                    case "PME": {
                                            $typeEnt = "8";
                                        }
                                        break;
                                    case "Educ": {
                                            $typeEnt = "5";
                                        }
                                        break;
                                    case "PARTICULIER": {
                                            $typeEnt = "8";
                                        }
                                        break;
                                    case "PARTICULIER": {
                                            $typeEnt = "8";
                                        }
                                        break;
                                }

                                if ($val['TyvCode'] == "FR" || $val['TyvCode'] == "CP") {
                                    $assujTVA = 1;
                                }
                                $tmpSoc = "";

                                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "societe WHERE code_client = '" . $codSoc . "'";
                                $sql = requeteWithCache($requete);

                                $socAdresse = $val['CliFAdrRue1'] . " " . $val['CliFAdrRue2'];

                                if ($db->num_rows($sql) > 0) {
                                    $res = fetchWithCache($sql);
                                    $socid = $res->rowid;



                                    $sqlUpt = array();
                                    if ($res->nom != $nomSoc)
                                        $sqlUpt[] = " nom = '" . $nomSoc . "'";
                                    if ($res->siret == 'NULL')
                                        $res->siret = '';
                                    if ($res->siret . "x" != $val['CliSIRET'] . "x")
                                        $sqlUpt[] = " siret = '" . (strlen($val['CliSIRET']) > 0 ? $val['CliSIRET'] : "NULL") . "'";
                                    if ($res->address != $socAdresse)
                                        $sqlUpt[] = " address = '" . (strlen($socAdresse) > 1 ? $socAdresse : "NULL") . "'";
                                    if ($res->zip != $val['CliFAdrZip'])
                                        $sqlUpt[] = " zip = '" . (strlen($val['CliFAdrZip']) > 0 ? $val['CliFAdrZip'] : "NULL") . "'";
                                    if ($res->town != $val['CliFAdrCity'])
                                        $sqlUpt[] = " town = '" . (strlen($val['CliFAdrCity']) > 0 ? $val['CliFAdrCity'] : "NULL") . "'";
                                    if ($res->phone != $val['MocTel'])
                                        $sqlUpt[] = " phone = '" . (strlen($val['MocTel']) > 0 ? $val['MocTel'] : "NULL") . "'";
                                    if ($res->fk_pays != $paysGlobal)
                                        $sqlUpt[] = " fk_pays = " . (strlen($paysGlobal) > 0 ? "'" . $paysGlobal . "'" : "NULL");
                                    if ($res->tva_assuj != $assujTVA)
                                        $sqlUpt[] = " tva_assuj = " . $assujTVA;
                                    if ($secteurActiv && $secteurActiv != $res->ref_int)
                                        $sqlUpt[] = " ref_int = " . $secteurActiv;
                                    if ($typeEnt != $res->fk_typent)
                                        $sqlUpt[] = " fk_typent = " . $typeEnt;
//            if ($res->titre != $val['CliTitleEnu'] )
//                $sqlUpt[] = " titre = '".$val['CliTitleEnu'] ."'";

                                    if (count($sqlUpt) > 0) {
                                        //Creation de la societe ou mise à jour si code client exist
                                        $updtStr = join(',', $sqlUpt);
                                        $requete = "UPDATE " . MAIN_DB_PREFIX . "societe SET " . $updtStr . " WHERE code_client = '" . $codSoc . "'";
                                        $sql = requeteWithCache($requete);
                                        if ($sql) {
                                            $webContent .= "<td class='ui-widget-content'>Mise &agrave; jour soci&eacute;t&eacute; OK</td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour soci&eacute;t&eacute; OK</td>" . "\n";
                                            /*
                                              +--------------------------------------------------------------------------------------------------------------+
                                              |                                                                                                              |
                                              |                                         Les commerciaux de la societe                                        |
                                              |                                                                                                              |
                                              +--------------------------------------------------------------------------------------------------------------+
                                             */
                                            $tmpSoc = new Societe($db);
                                            $tmpSoc->fetch($socid);
                                            if ($internalUserId > 0)
                                                $tmpSoc->add_commercial($user, $internalUserId);

                                            // Appel des triggers
                                            $interface = new Interfaces($db);
                                            $result = $interface->run_triggers('COMPANY_MODIFY', $tmpSoc, $user, $langs, $conf);
                                            if ($result < 0) {
                                                $error++;
                                                $errors = $interface->errors;
                                            }
                                        } else {
                                            $webContent .= "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour soci&eacute;t&eacute; KO " . $requete . "</td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour soci&eacute;t&eacute; KO</td>" . "\n";
                                        }
                                    } else {
                                        $webContent .= "<td  class='ui-widget-content'>Pas de modification soci&eacute;t&eacute;</td>";
                                        $mailContent .= "<td style='background-color: #FFF;'>Pas de modification soci&eacute;t&eacute;</td>" . "\n";
                                    }
                                } else {

                                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "societe
                                    (nom,
                                     code_client,
                                     datec,
                                    " . /* datea, */"
                                     siret,
                                     address,
                                     zip,
                                     town,
                                     phone,
                                     fk_pays,
                                     client,
" . //                                     titre,
                                            "                                    import_key,
                                    tva_assuj,
                                    " . ($secteurActiv ? "ref_int," : "") . "
                                    fk_typent
                                    )
                             VALUES ('" . $nomSoc . "',
                                     '" . $codSoc . "',
                                     now(),
                                    " . /* now(), */"
                                     '" . (strlen($val['CliSIRET']) > 0 ? $val['CliSIRET'] : "NULL") . "',
                                     '" . (strlen($socAdresse) > 1 ? $socAdresse : "NULL") . "',
                                     '" . (strlen($val['CliFAdrZip']) > 0 ? $val['CliFAdrZip'] : "NULL") . "','" . (strlen($val['CliFAdrCity']) > 0 ? $val['CliFAdrCity'] : "NULL") . "',
                                     '" . (strlen($val['MocTel']) > 0 ? $val['MocTel'] : "NULL") . "',
                                     " . (strlen($paysGlobal) > 0 ? "'" . $paysGlobal . "'" : "NULL") . ",
                                     1,
                                    "//'".$val['CliTitleEnu']."',
                                            . $val['AdpGAdrID'] . ",
                                    " . $assujTVA . ",
                                    " . ($secteurActiv ? $secteurActiv . "," : "") . "
                                    " . $typeEnt
                                            . ")";
                                    $sql = requeteWithCache($requete);
                                    $requete = "nnnnnnnnnnnnnimp";
//echo $requete;
                                    if ($sql) {
                                        /*
                                          +--------------------------------------------------------------------------------------------------------------+
                                          |                                                                                                              |
                                          |                                         Les commerciaux de la societe                                        |
                                          |                                                                                                              |
                                          +--------------------------------------------------------------------------------------------------------------+
                                         */
                                        /*
                                          PcvFree5 => string(19) 5 Dossier suivi par
                                          PcvGPriID => string(15) ID représentant
                                          PriCode => string(18) Code collaborateur
                                          PriLib => string(21) Libellé collaborateur
                                         */

                                        $socid = $db->last_insert_id("" . MAIN_DB_PREFIX . "societe");
                                        $tmpSoc = new Societe($db);
                                        $tmpSoc->fetch($socid);
                                        if ($internalUserId > 0)
                                            $tmpSoc->add_commercial($user, $internalUserId);
                                        // Appel des triggers
                                        $interface = new Interfaces($db);
                                        $result = $interface->run_triggers('COMPANY_CREATE', $tmpSoc, $user, $langs, $conf);
                                        if ($result < 0) {
                                            $error++;
                                            $errors = $interface->errors;
                                        }
                                        // Fin appel triggers
                                        $webContent .= "<td class='ui-widget-content'>Cr&eacute;ation de la soci&eacute;t&eacute; OK";
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation de la soci&eacute;t&eacute; OK</td>" . "\n";
                                    } else {
                                        $webContent .= "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation de la soci&eacute;t&eacute; KO " . $requete;
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation de la soci&eacute;t&eacute; KO</td>" . "\n";
                                    }
                                }


//                    if ($internalUserId > 0)
//                        if (is_object($tmpSoc))
//                            $tmpSoc->add_commercial($user, $internalUserId);
//                        else
//                            echo "Erreur pas de societe";

                                /*
                                  +--------------------------------------------------------------------------------------------------------------+
                                  |                                                                                                              |
                                  |                                         Les contacts de la societe                                           |
                                  |                                                                                                              |
                                  +--------------------------------------------------------------------------------------------------------------+
                                 */

                                $genre = $val["PrsTitleEnu"];
                                $prenom = $val['PrsPrenom'];
                                $nom = $val['PrsName'];
                                $socpeopleExternalId = $val['PcvPCopID'];

                                $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Contact de la soci&eacute;t&eacute;</th>";
                                $mailContent .= "<tr><th style='color:#fff; background-color: #0073EA;'>Contact de la soci&eacute;t&eacute;</th>" . "\n";


                                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "socpeople WHERE import_key = " . $socpeopleExternalId;
                                $sql = requeteWithCache($requete);
                                $res = fetchWithCache($sql);
                                $socContact = false;
                                if ($db->num_rows($sql) > 0) {
                                    $sqlUpt = array();
                                    $socContact = $res->rowid;
                                    if ($socContact < 1) {
                                        print_r($res);
                                        die("Probléme societe" . $socContact . $requete);
                                    }
                                    if ($res->phone != $val['PcvMocTel'])
                                        $sqlUpt[] = " phone = '" . $val["PcvMocTel"] . "'";
                                    if ($res->phone_mobile != $val['PcvMocPort'])
                                        $sqlUpt[] = " phone_mobile = '" . $val['PcvMocPort'] . "'";
                                    if ($res->civility != $genre)
                                        $sqlUpt[] = " civility = '" . $genre . "'";
                                    if (count($sqlUpt) > 0) {
                                        $updtStr = join(',', $sqlUpt);
                                        $requete = "UPDATE " . MAIN_DB_PREFIX . "socpeople SET " . $updtStr . " WHERE rowid =" . $socContact;
                                        $sql = requeteWithCache($requete);
                                        if ($sql) {
                                            $webContent .= "<td class='ui-widget-content'>Mise &agrave; jour contact OK";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour contact OK</td>" . "\n";
                                        } else {
                                            $webContent .= "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour contact KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour contact KO</td>" . "\n";
                                        }
                                    } else {
                                        $webContent .= "<td class='ui-widget-content'>Pas de mise &agrave; jour contact n&eacute;cessaire";
                                        $mailContent .= "<td style='background-color: #FFF;'>Pas de mise &agrave; jour contact n&eacute;cessaire</td>" . "\n";
                                    }
                                } else {
                                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "socpeople
                                    (datec,fk_soc,civility,lastname,firstname,phone,phone_mobile,import_key, fk_user_creat)
                             VALUES (now()," . $socid . ",'" . $genre . "','" . $nom . "','" . $prenom . "','" . $val['PcvMocTel'] . "','" . $val['PcvMocPort'] . "'," . $socpeopleExternalId . ", NULL)";
                                    $sql = requeteWithCache($requete);
                                    if ($sql) {
                                        $webContent .= "<td class='ui-widget-content'>Cr&eacute;ation contact OK";
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation contact OK</td>" . "\n";
                                    } else {
                                        $webContent .= "<td class='KOtd error  ui-widget-content'>Cr&eacute;ation contact KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span>";
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation contact KO</td>" . "\n";
                                    }
                                    $socContact = $db->last_insert_id(MAIN_DB_PREFIX . "socpeople");
                                }

                                /*
                                  +--------------------------------------------------------------------------------------------------------------+
                                  |                                                                                                              |
                                  |                                         Adresse de livraison                                                 |
                                  |                                                                                                              |
                                  +--------------------------------------------------------------------------------------------------------------+
                                 */

                                $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Adresse de livraison</th>";
                                $mailContent .= "<tr><th style='color:#fff; background-color: #0073EA;'>Adresse de livraison</th>" . "\n";
                                $livAdd = NULL;
                                $livAdresse = $val['CliLAdrRue1'] . " " . $val['CliLAdrRue2'];
                                $soc = new Societe($db);
                                $soc->fetch($socid);
                                if ($val['PcvLAdpID'] > 0 && !($socAdresse == $livAdresse && $val['CliFAdrZip'] == $val['CliLAdrZip'])) {


                                    $nomLivAdd = $val['CliLAdrLib'] . " - " . $val['PcvLAdpID'];
                                    $requete = "SELECT *
                          FROM " . MAIN_DB_PREFIX . "socpeople
                         WHERE (import_key = '" . $val['PcvLAdpID'] . "') || (fk_soc = " . $socid . " AND lastname = '" . $nomLivAdd . "') ||"
                                            . "(fk_soc = " . $socid . "
                                AND zip = '" . $val['CliLAdrZip'] . "'
                                AND town = '" . $val['CliLAdrCity'] . "'
                                AND address = '" . $livAdresse . "')";
//                        $requete = "SELECT *
//                          FROM " . MAIN_DB_PREFIX . "societe_adresse_livraison
//                         WHERE import_key = '" . $val['PcvLAdpID'] . "'";
                                    $sql = requeteWithCache($requete);
                                    $res = fetchWithCache($sql);
                                    if ($db->num_rows($sql) > 0) {
                                        $livAdd = $res->rowid;
                                        //            $webContent .=  "<td  class='ui-widget-content'> Pas de mise &agrave; jour de l'ad. de livraison</td>";
                                        //            $mailContent .= "<td  style='background-color: #fff;'> Pas de mise &agrave; jour de l'ad. de livraison"."\n";
                                        $requete = "UPDATE " . MAIN_DB_PREFIX . "socpeople
                            SET fk_soc = " . $socid . "
                                , zip = '" . $val['CliLAdrZip'] . "'
                                , town = '" . $val['CliLAdrCity'] . "'
                                , address = '" . $livAdresse . "'
                                , fk_pays = " . ($paysGlobal . "x" != "x" ? $paysGlobal : NULL) . "
                                , lastname = '" . $nomLivAdd . "'
                            WHERE import_key= " . $val['PcvLAdpID'];
                                        $sql = requeteWithCache($requete);
                                        if ($sql) {
                                            $webContent .= "<td  class='ui-widget-content'>Mise &agrave; jour ad. livraison OK";
                                            $mailContent .= "<td  style='background-color: #fff;'>Mise &agrave; jour ad. livraison OK" . "\n";
                                        } else {
                                            $webContent .= "<td  class='ui-widget-content'>Mise &agrave; jour ad. livraison KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span>";
                                            $mailContent .= "<td  style='background-color: #fff;'>Mise &agrave; jour ad. livraison KO" . "\n";
                                        }

                                        //Si modif
                                    } else {
                                        $nomLivAdd = $val['CliLAdrLib'] . " - " . $val['PcvLAdpID'];
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "socpeople (fk_soc, zip, town, address, fk_pays, lastname,import_key, fk_user_creat)
                                 VALUES (" . $socid . ",'" . $val['CliLAdrZip'] . "','" . $val['CliLAdrCity'] . "','" . $livAdresse . "',1,'" . $nomLivAdd . "'," . $val['PcvLAdpID'] . ", null)";
                                        $sql = requeteWithCache($requete);
                                        if ($sql) {
                                            $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation ad. livraison  OK";
                                            $mailContent .= "<td  style='background-color: #fff;'>Cr&eacute;ation ad. livraison  OK" . "\n";
                                        } else {
                                            $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation ad. livraison  KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span>";
                                            $mailContent .= "<td  style='background-color: #fff;'>Cr&eacute;ation ad. livraison  KO" . "\n";
                                        }
                                        $livAdd = $db->last_insert_id(MAIN_DB_PREFIX . "socpeople");
//die($livAdd);
                                    }
                                } else {
                                    $webContent .= "<td  class='ui-widget-content'>Pas ad. livraison";
                                    $mailContent .= "<td  style='background-color: #fff;'>Pas ad. livraison" . "\n";
                                }

                                $tabImportOK['soc'][$val["CliCode"]] = array('socid' => $socid, 'livAdd' => $livAdd, 'socContact' => $socContact);
                            }
                            /*
                              +--------------------------------------------------------------------------------------------------------------+
                              |                                                                                                              |
                              |                                         Les produits                                                         |
                              |                                                                                                              |
                              +--------------------------------------------------------------------------------------------------------------+
                             */
                            $prodId = false;
//PlvNuf

                            if ($val['ArtID'] > 0 && $val['ArtID'] != 41490 && ($val['PlvNuf'] == 'Normal' || $val['PlvNuf'] == 'Port')) {

                                /*

                                  ArtIsGaranti => string(7) Garanti            => ??
                                  ArtFree3 => string(17)  Désign2-2 Météor        => DurValidité contrat
                                  ArtDelai => string(20) Délai d'intervention     => SLA
                                  ArtDelai => string(20) Délai d'intervention     => SLA

                                 */

                                $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Produits</th>";
                                $mailContent .= "<tr><th  style='color:#fff; background-color: #0073EA;'>Produits</th>" . "\n";
                                $requete = "SELECT p.*, 2dureeSav as durSav FROM " . MAIN_DB_PREFIX . "product p LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields ON fk_object = p.rowid WHERE p.import_key = '" . $val['ArtID'] . "' OR ref='" . $val['PlvCode'] . "'";
                                $sql = requeteWithCache($requete);
                                $res = fetchWithCache($sql);
                                $sqlUpt = array();
                                $sqlUpt2 = array();
                                if ($db->num_rows($sql) > 0) {
                                    $prodId = $res->rowid;
                                    if ($res->description != $val['PlvLib'])
                                        $sqlUpt[] = " description = '" . $val['PlvLib'] . "'";
                                    if ($res->label != $val['ArtLib'])
                                        $sqlUpt[] = " label = '" . $val['ArtLib'] . "'";
                                    if ($res->price != $val['ArtPrixBase'])
                                        $sqlUpt[] = " price = '" . $val['ArtPrixBase'] . "'";
//                                    if ($res->prixAchat != $val['PlvPA'])
//                                        $sqlUpt2[] = " 2prixAchatHt = '" . $val['PlvPA'] . "'";
                                    if ($res->durSav != $val['ArtDureeGar'])
                                        $sqlUpt2[] = " 2dureeSav = " . ($val['ArtDureeGar'] > 0 ? $val['ArtDureeGar'] : 0) . "";
                                    if ($res->tva_tx != $val['TaxTaux'])
                                        $sqlUpt[] = " tva_tx = '" . $val['TaxTaux'] . "'";
//                                    if ($res->ref != $val['PlvCode']){
//                                        $sqlUpt[] = " ref = '" . $val['PlvCode'] . "'";
//                                        echo "Changement de ref de ".$res->ref." en ".$val['PlvCode'];
//                                    }

                                    
                                    if (count($sqlUpt) > 0 || count($sqlUpt2) > 0) {
                                        $ok = true;
                                        if (count($sqlUpt) > 0) {
                                            $updtStr = join(',', $sqlUpt);
                                            $requete = "UPDATE " . MAIN_DB_PREFIX . "product SET " . $updtStr . " WHERE import_key =" . $val['ArtID'];
                                            $sql = requeteWithCache($requete);
                                            if ($sql) {
                                                $tmpProd = new Product($db);
                                                $tmpProd->fetch($prodId);
                                                $tmpProd->updatePrice(($val['ArtPrixBase'] > 0 ? $val['ArtPrixBase'] : 0), "HT", $user, ($val['TaxTaux'] > 0 ? $val['TaxTaux'] : 0));
                                            } else {
                                                $ok = false;
                                            }
                                        }

                                        if (count($sqlUpt2) > 0) {
                                            $updtStr = join(',', $sqlUpt2);
                                            $requete = "UPDATE " . MAIN_DB_PREFIX . "product_extrafields SET " . $updtStr . " WHERE fk_object =" . $prodId;
                                            $sql = requeteWithCache($requete);
                                            if (!$sql)
                                                $ok = false;
                                        }
                                        if ($ok) {
                                            $webContent .= "<td class='ui-widget-content'>Mise &agrave; jour produit OK</td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour produit OK</td>" . "\n";
                                        } else {
                                            $webContent .= "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour produit KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour produit KO</td>" . "\n";
                                        }
                                    } else {
                                        $webContent .= "<td class='ui-widget-content'>Pas de mise &agrave; jour produit n&eacute;cessaire</td>";
                                        $mailContent .= "<td style='background-color: #FFF;'>Pas de mise &agrave; jour produit n&eacute;cessaire</td>" . "\n";
                                    }
                                    /*
                                      +--------------------------------------------------------------------------------------------------------------+
                                      |                                                                                                              |
                                      |                                         Catégorie et type                                                    |
                                      |                                                                                                              |
                                      +--------------------------------------------------------------------------------------------------------------+
                                     */
                                    updateCategorie($val['PlvCode'], $prodId, $val);
                                    updateType($val['PlvCode'], $prodId);
                                } else {
                                    $requete = "INSERT " . MAIN_DB_PREFIX . "product
                                   (datec,
                                    ref,
                                    label,
                                    description,
                                    price,
                                    "/* tobuy,
                                              durSav,
                                             */ . "tva_tx,
                                    import_key)
                            VALUES (now(),
                                    '" . $val['PlvCode'] . "',
                                    '" . $val['ArtLib'] . "',
                                    '" . $val['PlvLib'] . "',
                                    " . ($val['ArtPrixBase'] > 0 ? $val['ArtPrixBase'] : 0) . ",
                                    "/* 1,
                                              ".($val['ArtDureeGar']>0?$val['ArtDureeGar']:0).",
                                              " */ . ($val['TaxTaux'] > 0 ? $val['TaxTaux'] : 0) . ",
                                    " . ($val['ArtID'] > 0 ? $val['ArtID'] : 0) . ") ";
                                    $sql = requeteWithCache($requete);
                                    $prodId = $db->last_insert_id($sql);

                                    $requete = "INSERT " . MAIN_DB_PREFIX . "product_extrafields
                                    (
                                    `tms` ,
                                    `fk_object`,
                                    `2dureeSav` 
                                    )
                                     VALUES (now(),
                                    '" . $prodId . "',
                                    '" . $val['ArtDureeGar'] . "')
                                    " /*. ($val['PlvPA'] > 0 ? $val['PlvPA'] : 0) . ") "*/;

                                    if ($sql) {
                                        $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation produit OK</td>";
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation produit OK</td>" . "\n";

                                        $tmpProd = new Product($db);
                                        $tmpProd->fetch($prodId);
                                        $tmpProd->updatePrice(($val['ArtPrixBase'] > 0 ? $val['ArtPrixBase'] : 0), "HT", $user, ($val['TaxTaux'] > 0 ? $val['TaxTaux'] : 0));

                                        /*
                                          +--------------------------------------------------------------------------------------------------------------+
                                          |                                                                                                              |
                                          |                                         Catégorie et type                                                    |
                                          |                                                                                                              |
                                          +--------------------------------------------------------------------------------------------------------------+
                                         */
                                        updateCategorie($val['PlvCode'], $prodId, $val);
                                        updateType($val['PlvCode'], $prodId);
                                    } else {
                                        $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation produit KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                        $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation produit KO</td>" . "\n";
                                    }
                                    //$webContent .=  $requete . "<br/>";
                                }

                                if ($val['PlvPA'] > 0 && $prodId > 0) {
                                    require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.product.class.php");
                                    $sql2 = requeteWithCache($requete);
                                    $prodF = new ProductFournisseur($db);
                                    $fourn = new Fournisseur($db);
                                    $fourn->fetch(5862);
                                    $prodF->fetch($prodId);
                                    $prodF->update_buyprice(1, floatval($val['PlvPA']), $user, 'HT', $fourn, $availability, $val['ArtID'], $val['TaxTaux']);
                                }
                            }

                            if ($socid > 0) {
                                $condReg = false;
                                $modReg = false;

//                            switch ($val['PcvGRgmID']) {
//                                default: {
//                                        $modReg = 0;
//                                        $condReg = 1;
//                                    }
//                                    break;
//                                case "6": {
//                                        $modReg = 0;
//                                        $condReg = 24;
//                                    }
//                                    break;
//                                case "16": {
//                                        $modReg = 11;
//                                        $condReg = 15;
//                                    }
//                                    break;
//                                case "42": {
//                                        $modReg = 11;
//                                        $condReg = 4;
//                                    }
//                                    break;
//                                case "77": {
//                                        $modReg = 11;
//                                        $condReg = 13;
//                                    }
//                                    break;
//                                case "79": {
//                                        $modReg = 11;
//                                        $condReg = 12;
//                                    }
//                                    break;
//                                case "126": {
//                                        $modReg = 55;
//                                        $condReg = 24;
//                                    }
//                                    break;
//                                case "291": {
//                                        $modReg = 11;
//                                        $condReg = 3;
//                                    }
//                                    break;
//                                case "337": {
//                                        $modReg = 7;
//                                        $condReg = 7;
//                                    }
//                                    break;
//                                case "341": {
//                                        $modReg = 7;
//                                        $condReg = 20;
//                                    }
//                                    break;
//                                case "353": {
//                                        $modReg = 51;
//                                        $condReg = 7;
//                                    }
//                                    break;
//                                case "357": {
//                                        $modReg = 11;
//                                        $condReg = 2;
//                                    }
//                                    break;
//                                case "382": {
//                                        $modReg = 0;
//                                        $condReg = 1;
//                                    }
//                                    break;
//                                case "383": {
//                                        $modReg = 0;
//                                        $condReg = 20;
//                                    }
//                                    break;
//                                case "384": {
//                                        $modReg = 6;
//                                        $condReg = 26;
//                                    }
//                                    break;
//                                case "385": {
//                                        $modReg = 7;
//                                        $condReg = 1;
//                                    }
//                                    break;
//                                case "386": {
//                                        $modReg = 7;
//                                        $condReg = 25;
//                                    }
//                                    break;
//                                case "387": {
//                                        $modReg = 7;
//                                        $condReg = 2;
//                                    }
//                                    break;
//                                case "388": {
//                                        $modReg = 7;
//                                        $condReg = 4;
//                                    }
//                                    break;
//                                case "389": {
//                                        $modReg = 7;
//                                        $condReg = 2;
//                                    }
//                                    break;
//                                case "390": {
//                                        $modReg = 7;
//                                        $condReg = 3;
//                                    }
//                                    break;
//                                case "391": {
//                                        $modReg = 0;
//                                        $condReg = 8;
//                                    }
//                                    break;
//                                case "392": {
//                                        $condReg = 4;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "393": {
//                                        $condReg = 5;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "394": {
//                                        $condReg = 12;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "395": {
//                                        $condReg = 19;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "396": {
//                                        $condReg = 18;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "397": {
//                                        $condReg = 15;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "398": {
//                                        $condReg = 2;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "399": {
//                                        $condReg = 3;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "400": {
//                                        $condReg = 10;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "401": {
//                                        $condReg = 4;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "402": {
//                                        $condReg = 5;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "403": {
//                                        $condReg = 12;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "404": {
//                                        $condReg = 19;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "405": {
//                                        $condReg = 18;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "406": {
//                                        $condReg = 15;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "407": {
//                                        $condReg = 1;
//                                        $modReg = 2;
//                                    }
//                                    break;
//                                case "408": {
//                                        $condReg = 21;
//                                        $modReg = 3;
//                                    }
//                                    break;
//                                case "409": {
//                                        $condReg = 1;
//                                        $modReg = 4;
//                                    }
//                                    break;
//                                case "411": {
//                                        $condReg = 14;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "412": {
//                                        $condReg = 4;
//                                        $modReg = 2;
//                                    }
//                                    break;
//                                case "413": {
//                                        $condReg = 5;
//                                        $modReg = 2;
//                                    }
//                                    break;
//                                case "414": {
//                                        $condReg = 9;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "415": {
//                                        $condReg = 26;
//                                        $modReg = 0;
//                                    }
//                                    break;
//                                case "416": {
//                                        $condReg = 3;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "417": {
//                                        $condReg = 1;
//                                        $modReg = 0;
//                                    }
//                                    break;
//                                case "418": {
//                                        $condReg = 27;
//                                        $modReg = 0;
//                                    }
//                                    break;
//                                case "420": {
//                                        $condReg = 13;
//                                        $modReg = 51;
//                                    }
//                                    break;
//                                case "421": {
//                                        $condReg = 25;
//                                        $modReg = 0;
//                                    }
//                                    break;
//                                case "422": {
//                                        $condReg = 12;
//                                        $modReg = 2;
//                                    }
//                                    break;
//                                case "423": {
//                                        $condReg = 22;
//                                        $modReg = 13;
//                                    }
//                                    break;
//                                case "426": {
//                                        $condReg = 10;
//                                        $modReg = 7;
//                                    }
//                                    break;
//                                case "427": {
//                                        $condReg = 9;
//                                        $modReg = 0;
//                                    }
//                                    break;
//                            }


                                switch ($val['PcvGRgmID']) {
                                    default: {
                                            $modReg = 0;
                                        }
                                        break;
                                    case "28": {
                                            $modReg = 4;
                                        }
                                        break;
                                    case "12": {
                                            $modReg = 6;
                                        }
                                        break;
                                    case "13":
                                    case "14":
                                    case "15":
                                    case "16":
                                    case "17":
                                    case "18":
                                    case "19":
                                    case "20":
                                    case "33": {
                                            $modReg = 7;
                                        }
                                        break;
                                    case "21":
                                    case "22":
                                    case "23":
                                    case "24":
                                    case "25": {
                                            $modReg = 51;
                                        }
                                        break;
                                    case "26":
                                    case "29":
                                    case "30":
                                    case "32":
                                    case "35": {
                                            $modReg = 2;
                                        }
                                        break;
                                    case "27": {
                                            $modReg = 3;
                                        }
                                        break;
                                    case "2": {
                                            $modReg = 55;
                                        }
                                        break;
                                }


                                //manque mod 10 11 27 34



                                switch ($val['PcvGRgmID']) {
                                    default: {
                                            $condReg = 0;
                                        }
                                        break;
                                    case "10": {
                                            $condReg = 38;
                                        }
                                        break;
                                    case "34": {
                                            $condReg = 11;
                                        }
                                        break;
                                    case "18": {
                                            $condReg = 5;
                                        }
                                        break;
                                    case "35":
                                    case "21":
                                    case "16": {
                                            $condReg = 2;
                                        }
                                        break;
                                    case "32":
                                    case "20": {
                                            $condReg = 12;
                                        }
                                        break;
                                    case "30":
                                    case "25": {
                                            $condReg = 5;
                                        }
                                        break;
                                    case "29":
                                    case "24":
                                    case "19": {
                                            $condReg = 4;
                                        }
                                        break;
                                    case "23":
                                    case "33": {
                                            $condReg = 10;
                                        }
                                        break;
                                    case "22":
                                    case "17": {
                                            $condReg = 3;
                                        }
                                        break;
                                    case "13":
                                    case "14": {
                                            $condReg = 25;
                                        }
                                        break;
                                    case "11": {
                                            $condReg = 20;
                                        }
                                        break;
                                    case "12":
                                    case "9": {
                                            $condReg = 26;
                                        }
                                        break;
                                }

                                //manque cond paiement 2   27   26   15   28

                                /*
                                  +--------------------------------------------------------------------------------------------------------------+
                                  |                                                                                                              |
                                  |                                         La commande                                                          |
                                  |                                                                                                              |
                                  +--------------------------------------------------------------------------------------------------------------+
                                 */

                                if ($typeLigne == "propal") {
                                    if (isset($tabImportOK['propal'][$val['PcvCode']]))
                                        $comId = $tabImportOK['propal'][$val['PcvCode']];
                                    else {
                                        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/revision.class.php");
                                        $webContent .= "<tr><th class='ui-state-default ui-widget-header'>" . ($typeLigne == "commande" ? "Commande" : "Propal") . "</td>";
                                        $mailContent .= "<tr><th style='background-color: #0073EA; color: #FFF;'>" . ($typeLigne == "commande" ? "Commande" : "Propal") . "</th>" . "\n";
                                        $ref = ($val['PcvFree8'] != "") ? $val['PcvFree8'] : $val['PcvCode'];
                                        $oldRef = false;

                                        $tabRef = explode("-", $ref);
                                        if (isset($tabRef[1])) {
                                            $newRef = $ref;
                                            $oldRef = $tabRef[0];
                                            $result = SynopsisRevisionPropal::getRefMax($oldRef, "propal");
                                            if ($result) {
                                                $oldId = $result[0];
                                            }
                                        }

                                        $result = SynopsisRevisionPropal::getRefMax($ref, "propal");
                                        if ($result) {
                                            $oldRef = $ref;
                                            $ref = "TEMP(" . (($val['PcvFree8'] != "") ? $val['PcvFree8'] : $val['PcvCode']) . ")";
                                            $newRef = null;
                                            $oldId = $result[0];
                                        }

                                        //Insert commande
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "propal
                                        (datec,ref, fk_user_author, fk_soc,fk_cond_reglement, datep, fk_mode_reglement,fk_delivery_address,import_key)
                                 VALUES (now(),'" . $ref . "'," . ($internalUserId > 0 ? $internalUserId : 'NULL') . "," . $socid . "," . $condReg . ",'" . date('Y-m-d', $val['PcvDate']) . "'," . $modReg . ",'" . $livAdd . "'," . $val['PcvID'] . ")";
                                        $sql = requeteWithCache($requete);
                                        $comId = $db->last_insert_id("" . MAIN_DB_PREFIX . "propal");
                                        if (isset($oldRef) && $oldRef && $oldId > 0)
                                            SynopsisRevisionPropal::setLienRevision($oldRef, $oldId, $comId, $newRef);
                                        if ($sql) {
                                            $mode = "PROPAL_CREATE";
                                            $tabImportOK['propal'][$val['PcvCode']] = $comId;
                                            $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation commande OK</td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation commande OK</td>" . "\n";
                                        } else {
                                            $webContent .= "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation commande KO</td>" . "\n";
                                        }
                                    }
                                } else {
                                    if (isset($tabImportOK['commande'][$val['PcvCode']]))
                                        $comId = $tabImportOK['commande'][$val['PcvCode']]['id'];
                                    else {
                                        $webContent .= "<tr><th class='ui-state-default ui-widget-header'>" . ($typeLigne == "commande" ? "Commande" : "Propal") . "</td>";
                                        $mailContent .= "<tr><th style='background-color: #0073EA; color: #FFF;'>" . ($typeLigne == "commande" ? "Commande" : "Propal") . "</th>" . "\n";
                                        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commande WHERE ref = '" . $val['PcvCode'] . "'";
                                        $sql = requeteWithCache($requete);
                                        $res = fetchWithCache($sql);
                                        //Creer la commande
                                        $comId = false;


                                        $mode = ""; //pour les trigger

                                        if (!$db->num_rows($sql) > 0) {
                                            //Insert commande
                                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "commande
                                        (extraparams, date_creation,ref, fk_user_author, fk_soc,fk_cond_reglement, date_commande, fk_mode_reglement,fk_delivery_address,import_key)
                                 VALUES (1, now(),'" . $val['PcvCode'] . "'," . ($internalUserId > 0 ? $internalUserId : 'NULL') . "," . $socid . "," . $condReg . ",'" . date('Y-m-d', $val['PcvDate']) . "'," . $modReg . ",'" . $livAdd . "'," . $val['PcvID'] . ")";
                                            $sql = requeteWithCache($requete);
                                            $comId = $db->last_insert_id("" . MAIN_DB_PREFIX . "commande");
                                            if ($sql) {
                                                $tabImportOK['commande'][$val['PcvCode']] = array('id' => $comId, 'codeAff' => $val['AffCode']);
                                                $mode = "ORDER_CREATE";
                                                $webContent .= "<td  class='ui-widget-content'><a href='".DOL_URL_ROOT."/commande/card.php?id=".$comId."'>Cr&eacute;ation commande OK</a></td>";
                                                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation commande OK</td>" . "\n";
                                            } else {
                                                $webContent .= "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation commande KO</td>" . "\n";
                                            }
                                        } else {
                                            //Updatecommande
                                            $comId = $res->rowid;
                                            $sqlUpt = array();
                                            if ($res->fk_user_author != $internalUserId && $internalUserId > 0)
                                                $sqlUpt[] = " fk_user_author = '" . $internalUserId . "'";
                                            if ($res->fk_soc != $socid)
                                                $sqlUpt[] = " fk_soc = '" . $socid . "'";
                                            if ($res->fk_cond_reglement != $condReg)
                                                $sqlUpt[] = " fk_cond_reglement = '" . $condReg . "'";
                                            if ($res->fk_mode_reglement != $modReg)
                                                $sqlUpt[] = " fk_mode_reglement = '" . $modReg . "'";
                                            if ($res->fk_delivery_address != $livAdd)
                                                $sqlUpt[] = " fk_delivery_address = '" . $livAdd . "'";
                                            if (count($sqlUpt) > 0) {
                                                $updtStr = join(',', $sqlUpt);
                                                $requete = "UPDATE " . MAIN_DB_PREFIX . "commande SET " . $updtStr . " WHERE rowid =" . $comId;
                                                $sql = requeteWithCache($requete);
                                                if ($sql) {
                                                    $tabImportOK['commande'][$val['PcvCode']] = array('id' => $comId, 'codeAff' => $val['AffCode']);
                                                    $mode = "ORDER_MODIFY";
                                                    $webContent .= "<td  class='ui-widget-content'>Mise &agrave; jour commande OK</td>";
                                                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour commande OK</td>" . "\n";
                                                } else {
                                                    $webContent .= "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour commande KO</td>" . "\n";
                                                }
                                            } else {
                                                $tabImportOK['commande'][$val['PcvCode']] = array('id' => $comId, 'codeAff' => $val['AffCode']);
                                                $webContent .= "<td  class='ui-widget-content'>Pas de mise &agrave; jour commande n&eacute;c&eacute;ssaire";
                                                $mailContent .= "<td style='background-color: #FFF;'>Pas de mise &agrave; jour commande n&eacute;cessaire</td>" . "\n";
                                            }
                                        }
                                    }
                                }

                                if ($comId > 0 && $livAdd > 0) {
                                    $finReq = " FROM " . MAIN_DB_PREFIX . "element_contact WHERE fk_socpeople =" . $livAdd . " AND fk_c_type_contact IN (102) AND element_id = " . $comId;
                                    $requete = "SELECT *" . $finReq;
                                    $sql = requeteWithCache($requete);
//                            die($requete);
                                    if ($db->num_rows($sql) < 1) {
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact(fk_socpeople, fk_c_type_contact, element_id,statut, datecreate)
                                   VALUES (" . $livAdd . ",102," . $comId . ",4,now() )";
                                        $sql = requeteWithCache($requete);
                                    }
//print $requete;
                                }


                                /*
                                 * A voir drsi drsi drsi
                                 */
                                if ($comId > 0 && $socContact > 0) {
                                    $finReq = " FROM " . MAIN_DB_PREFIX . "element_contact WHERE fk_socpeople =" . $socContact . " AND fk_c_type_contact IN (100,101) AND element_id = " . $comId;
                                    $requete = "SELECT *" . $finReq;
                                    $sql = requeteWithCache($requete);
                                    if ($db->num_rows($sql) < 2) {
                                        $requete = "DELETE" . $finReq;
                                        $sql = requeteWithCache($requete);
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact(fk_socpeople, fk_c_type_contact, element_id,statut, datecreate)
                                   VALUES (" . $socContact . ",100," . $comId . ",4,now() )";
                                        $sql = requeteWithCache($requete);
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact(fk_socpeople, fk_c_type_contact, element_id,statut, datecreate)
                                   VALUES (" . $socContact . ",101," . $comId . ",4,now() )";
                                        $sql = requeteWithCache($requete);
                                    }
//print $requete;
                                }

                                /*
                                  +--------------------------------------------------------------------------------------------------------------+
                                  |                                                                                                              |
                                  |                                         Les lignes de commandes                                              |
                                  |                                                                                                              |
                                  +--------------------------------------------------------------------------------------------------------------+
                                 */

                                /*
                                  PlvFree0 => string(19)  Libre/Série Météor => coef ? sinon duree estimé (ajouter champs)
                                 */


                                $val['PlvQteUV'] = str_replace(",", ".", $val['PlvQteUV']);
                                if ($val['PlvQteUV'] < 0) {
                                    $val['PlvQteUV'] = 0 - $val['PlvQteUV'];
                                    $val['PlvPUNet'] = 0 - $val['PlvPUNet'];
                                }
                                $totalCom_ttc = preg_replace('/,/', '.', $val['PlvQteUV'] * $val['PlvPUNet'] * (1 + ( $val['TaxTaux'] / 100)));
                                $totalCom_tva = preg_replace('/,/', '.', $val['PlvQteUV'] * $val['PlvPUNet'] * (( $val['TaxTaux'] / 100)));
//echo "lalalalalala".$totalCom_tva."|".$val['PlvQteUV'];
                                if ($comId) {
                                    $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Ligne de commande</td>";
                                    $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Ligne de commande</td>" . "\n";
                                    //Les lignes de commandes
//Prix Achat
                                    if ($typeLigne == "propal") {
                                        if ($val['PlvCode']) {
                                            $prodType = getProdType($val['PlvCode']);
                                            $premiereDesc = true;
                                        } elseif ($premiereDesc && $val['PlvLib'] != "") {
                                            $prodType = 100;
                                            $premiereDesc = false;
                                        } else
                                            $prodType = 106;
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "propaldet
                                       (fk_propal,
                                        fk_product,
                                        description,
                                        qty,
                                        subprice,
                                        rang,
                                        total_ht,
                                        special_code,
                                        tva_tx,
                                        total_tva,
                                        total_ttc,
                                        buy_price_ht,
                                        product_type)
                                VALUES (" . $comId . ",
                                        " . ($prodId > 0 ? $prodId : "NULL") . ",
                                        '" . $val['PlvLib'] . "',
                                        " . preg_replace('/,/', '.', $val['PlvQteUV'] > 0 ? $val['PlvQteUV'] : ($val['PlvPUNet'] < 0 ? abs($val['PlvPUNet']) : 0)) . ",
                                        " . preg_replace('/,/', '.', ($val['PlvQteUV'] > 0 ? ($val['PlvPUNet'] > 0 ? $val['PlvPUNet'] : ($val['PlvPUNet'] < 0 ? $val['PlvPUNet'] : 0)) : ($val['PlvQteUV'] < 0 ? -1 * ($val['PlvPUNet'] > 0 ? $val['PlvPUNet'] : ($val['PlvPUNet'] < 0 ? $val['PlvPUNet'] : 0)) : 0))) . ",
                                        " . $val['PlvNumLig'] . ",
                                        " . preg_replace('/,/', '.', ($val['PlvQteUV'] != 0 ? floatval($val['PlvQteUV']) : 0) * ($val['PlvPUNet'] != 0 ? floatval($val['PlvPUNet']) : 0)) . ",
                                        " . $val['PlvID'] . ",
                                        " . preg_replace('/,/', '.', $val['TaxTaux']) . ",
                                        " . ($totalCom_tva != 0 ? $totalCom_tva : 0) . ",
                                        " . ($totalCom_ttc != 0 ? $totalCom_ttc : 0) . ",
                                        " . ($val['PlvPA'] > 0 ? $val['PlvPA'] : "NULL") . ",
                                        " . $prodType . ")";


                                        $sql = requeteWithCache($requete);
                                        if ($sql) {
                                            $remArrayComLigne[$comId][$db->last_insert_id("'" . MAIN_DB_PREFIX . "commandedet'")] = $db->last_insert_id("'" . MAIN_DB_PREFIX . "commandedet'");
                                            if ($mode != 'ORDER_CREATE')
                                                $mode = "ORDER_MODIFY";
                                            $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation ligne commande OK</td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande OK</td>" . "\n";
                                        } else {
                                            $webContent .= "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation ligne commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande KO</td>" . "\n";
                                        }
                                    } else {
                                        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "commandedet WHERE import_key = " . $val['PlvID'];
                                        $sql1 = requeteWithCache($requete);
                                        $res1 = fetchWithCache($sql1);
                                        if ($db->num_rows($sql1) > 0) {
                                            //Update
                                            $sqlUpt = array();

                                            if ($prodId && $res1->fk_product != $prodId)
                                                $sqlUpt[] = " fk_product = '" . $prodId . "'";
                                            if ($res1->buy_price_ht != $val['PlvPA'])
                                                $sqlUpt[] = " buy_price_ht = '" . ($val['PlvPA'] > 0 ? $val['PlvPA'] : 0) . "'";
                                            if ($res1->description != $val['PlvLib'])
                                                $sqlUpt[] = " description = '" . $val['PlvLib'] . "'";
                                            if ($val['PlvQteUV'] == '')
                                                $val['PlvQteUV'] = 0;
                                            if ($res1->qty != $val['PlvQteUV'])
                                                $sqlUpt[] = " qty = '" . preg_replace('/,/', '.', ($val['PlvQteUV'] > 0 ? $val['PlvQteUV'] : ($val['PlvQteUV'] < 0 ? abs($val['PlvQteUV']) : 0))) . "'";
                                            if ($val['PlvQteUV'] < 0) {
                                                $val['PlvPUNet'] = - $val['PlvPUNet'];
                                                $val['PlvQteUV'] = - $val['PlvQteUV'];
                                            }
                                            if ($val['PlvPUNet'] == '')
                                                $val['PlvPUNet'] = 0;
                                            if ($res1->subprice != $val['PlvPUNet'])
                                                $sqlUpt[] = " subprice = '" . preg_replace('/,/', '.', ($val['PlvPUNet']) > 0 ? $val['PlvPUNet'] : 0) . "'";
                                            if ($res1->total_ht != floatval($val['PlvQteUV']) * floatval($val['PlvPUNet']))
                                                $sqlUpt[] = " total_ht = qty * subprice ";
                                            if ($res1->rang != $val['PlvNumLig'])
                                                $sqlUpt[] = " rang = " . $val['PlvNumLig'];
                                            if ($res1->tva_tx != $val['TaxTaux'])
                                                $sqlUpt[] = " tva_tx = '" . preg_replace('/,/', '.', $val['TaxTaux']) . "'";
                                            if ($res1->total_ttc != $totalCom_ttc)
                                                $sqlUpt[] = " total_ttc = '" . $totalCom_ttc . "'";
                                            if ($res1->total_tva != $totalCom_tva)
                                                $sqlUpt[] = " total_tva = '" . $totalCom_tva . "'";
                                            if ($val['PlvCode'])
                                                $prodType = getProdType($val['PlvCode']);
                                            else
                                                $prodType = 5;
                                            if ($res1->product_type != $prodType)
                                                $sqlUpt[] = " product_type = '" . $prodType . "'";


                                            $remArrayComLigne[$comId][$res1->rowid] = $res1->rowid;
                                            if (count($sqlUpt) > 0) {
                                                $updtStr = join(',', $sqlUpt);
                                                $requete = "UPDATE " . MAIN_DB_PREFIX . "commandedet SET " . $updtStr . " WHERE rowid = " . $res1->rowid;
//                        print $requete;
                                                $sql = requeteWithCache($requete);
                                                if ($sql) {
                                                    $webContent .= "<td  class='ui-widget-content'>Mise &agrave; jour ligne commande OK</td>";
                                                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour ligne commande OK</td>" . "\n";
                                                    if ($mode != 'ORDER_CREATE')
                                                        $mode = "ORDER_MODIFY";
                                                } else {
                                                    $webContent .= "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour ligne commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour ligne commande OK</td>" . "\n";
                                                }
                                            } else {
                                                $webContent .= "<td  class='ui-widget-content'>Pas de modification ligne commande</td>";
                                                $mailContent .= "<td style='background-color: #FFF;'>Pas de modification ligne commande</td>" . "\n";
                                            }
                                        } else {
                                            //Insert
                                            if ($val['PlvCode'])
                                                $prodType = getProdType($val['PlvCode']);
                                            else
                                                $prodType = 5;
                                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "commandedet
                                       (fk_commande,
                                        fk_product,
                                        description,
                                        qty,
                                        subprice,
                                        rang,
                                        total_ht,
                                        import_key,
                                        tva_tx,
                                        total_tva,
                                        total_ttc,
                                        buy_price_ht,
                                        product_type)
                                VALUES (" . $comId . ",
                                        " . ($prodId > 0 ? $prodId : "NULL") . ",
                                        '" . $val['PlvLib'] . "',
                                        " . preg_replace('/,/', '.', $val['PlvQteUV'] > 0 ? $val['PlvQteUV'] : ($val['PlvPUNet'] < 0 ? abs($val['PlvPUNet']) : 0)) . ",
                                        " . preg_replace('/,/', '.', ($val['PlvQteUV'] > 0 ? ($val['PlvPUNet'] > 0 ? $val['PlvPUNet'] : ($val['PlvPUNet'] < 0 ? $val['PlvPUNet'] : 0)) : ($val['PlvQteUV'] < 0 ? -1 * ($val['PlvPUNet'] > 0 ? $val['PlvPUNet'] : ($val['PlvPUNet'] < 0 ? $val['PlvPUNet'] : 0)) : 0))) . ",
                                        " . $val['PlvNumLig'] . ",
                                        " . preg_replace('/,/', '.', ($val['PlvQteUV'] != 0 ? floatval($val['PlvQteUV']) : 0) * ($val['PlvPUNet'] != 0 ? floatval($val['PlvPUNet']) : 0)) . ",
                                        " . $val['PlvID'] . ",
                                        " . preg_replace('/,/', '.', $val['TaxTaux']) . ",
                                        " . ($totalCom_tva != 0 ? $totalCom_tva : 0) . ",
                                        " . ($totalCom_ttc != 0 ? $totalCom_ttc : 0) . ",
                                        " . ($val['PlvPA'] != 0 ? $val['PlvPA'] : "NULL") . ",
                                        " . $prodType . ")";


                                            $sql = requeteWithCache($requete);
                                            if ($sql) {
                                                $remArrayComLigne[$comId][$db->last_insert_id("'" . MAIN_DB_PREFIX . "commandedet'")] = $db->last_insert_id("'" . MAIN_DB_PREFIX . "commandedet'");
                                                if ($mode != 'ORDER_CREATE')
                                                    $mode = "ORDER_MODIFY";
                                                $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation ligne commande OK</td>";
                                                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande OK</td>" . "\n";
                                            } else {
                                                $webContent .= "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation ligne commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                                                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande KO</td>" . "\n";
                                            }
                                        }
                                    }
                                }
                            }
                            /*
                              +--------------------------------------------------------------------------------------------------------------+
                              |                                                                                                              |
                              |                                         Effacement Ligne de commande                                         |
                              |                                                                                                              |
                              +--------------------------------------------------------------------------------------------------------------+
                             */

                            $webContent .= "<table width=600 cellpadding=10>";
                            $webContent .= "<tr><th colspan=2 class='ui-state-default ui-widget-header'>Syncho nombre de ligne de commande</td>";
                            $mailContent .= "<tr><th colspan=2 style='background-color:#0073EA; color: #FFF;'>Syncho nombre de ligne de commande</td>" . "\n";

                            $webContent .= "</table>";
                            $mailContent .= "</table>" . "\n";
                        }
                    }



                    /*
                     * Fin modif
                     */









                    //Move file
                    if ($OKFile) {
                        if (!is_dir($dir . "/imported/"))
                            if (!mkdir($dir . $imported))
                                die("Impossible de créer le dossier.");
                        $webContent .= "<tr><th class='ui-state-default ui-widget-header'>deplacement fichier</th>";
                        $resultMv = rename($dir . "/" . $file, $dir . $imported . $file);
                        if ($resultMv)
                            $webContent.= "<td class='ui-widget-content'>OK</td>";
                        else
                            $webContent.= "<td class='ui-widget-content'>KO</td>";

                        $webContent .= "</table>";
//                echo $webContent;
//                $webContent = '';
                        $mailContent = '';
                    }
                    foreach ($remArrayComLigne as $comId => $arrLigne) {
                        $webContent .= "<tr><th colspan=1 class='ui-state-default ui-widget-header'>Commande #" . $comId . "</td>";
                        $mailContent .= "<tr><th colspan=1 style='background-color:#0073EA; color: #FFF;'>Commande #" . $comId . "</td>" . "\n";
                        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "commandedet WHERE fk_commande = " . $comId . " AND rowid not in (" . join(",", $arrLigne) . ")";
                        $sql = requeteWithCache($requete);
                        if ($sql) {
                            $webContent .= "<td  class='ui-widget-content'>Synchro des lignes de commande OK</td>";
                            $mailContent .= "<td style='background-color: #FFF;'>Synchro des lignes de commande OK</td>" . "\n";
                        } else {
                            $webContent .= "<td class='KOtd error ui-widget-content'>Synchro des lignes de commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                            $mailContent .= "<td style='background-color: #FFF;'>Synchro des lignes de commande KO</td>" . "\n";
                        }
                    }
                }
            }
        }
        closedir($dh);



        /*
          +--------------------------------------------------------------------------------------------------------------+
          |                                                                                                              |
          |                                         Update total commande                                                |
          |                                                                                                              |
          +--------------------------------------------------------------------------------------------------------------+
         */
//    }
    } else {
        $webContent .= "<div class='ui-error error'> Pas de r&eacute;pertoire d'importation d&eacute;fini</div>";
    }

    echo $webContent;
    $webContent = '';


//  var_dump($arrayImport);



    $webContent .= "<div id='debug'>Message:<div id='replace'></div></div>";
    $webContent .= <<<EOF
<style>
#debug { position:fixed; background-color: #fff; top: 10%; right: 10%; width: 400px; min-height: 400px; border: 1px Solid #0073EA; }
.KOtd #debugS { display: none; }
.KOtd { cursor: pointer;}
</style>
<script>
jQuery(document).ready(function(){
    jQuery('.KOtd').mouseover(function(){
        jQuery('#debug').find('#replace').replaceWith("<div id='replace'>"+jQuery(this).find('#debugS').html()+"</div>");
    });
});
</script>
EOF;


    $remCatGlob = false;





    foreach ($tabImportOK['commande'] as $ref => $tabT) {
        $id = $tabT['id'];
        $codeAff = $tabT['codeAff'];
        $com = new Synopsis_Commande($db);
        $com->fetch($id);


//                            $mailSumUpContent['commande'][] = $com;
        $societe = new Societe($db);
        $societe->fetch($com->socid);
        $mailHeader .= "<tr><td>\n" . $com->getNomUrl(1, 6) . "\n</td>" . "\n";
        $mailHeader .= "    <td>\n" . ($societe->id ? $societe->getNomUrl(1, 6) : '-') . "\n</td>" . "\n";
        $mailHeader .= "    <td aligne='right' nowrap>\n" . price($com->total_ht) . "&euro;\n</td>" . "\n";

        $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Groupe de commande</td>";
        $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Groupe de commande</td>" . "\n";
        /*
          +--------------------------------------------------------------------------------------------------------------+
          |                                                                                                              |
          |                                         Les groupes des commandes                                            |
          |                                                                                                              |
          +--------------------------------------------------------------------------------------------------------------+
         */

        $finReq = " FROM " . MAIN_DB_PREFIX . "Synopsis_commande_grpdet WHERE refCommande = '" . $com->ref . "'";
        $requete = "SELECT *" . $finReq;
        $sqlGr = requeteWithCache($requete);
        if ($codeAff . "x" == "x") {
            if ($db->num_rows($sqlGr) > 0) {
                //Verifier par rapport à la référence. => supression
                $requete = "DELETE" . $finReq;
                $sql = requeteWithCache($requete);
                if ($sql) {
                    $webContent .= "<td  class='ui-widget-content'>Effacement de la liaison commande - groupe OK</td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Effacement de la liaison commande - groupe OK</td>" . "\n";
                } else {
                    $webContent .= "<td class='KOtd error ui-widget-content'>Effacement de la liaison commande - groupe KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Effacement de la liaison commande - groupe KO</td>" . "\n";
                }
            } else
                $webContent .= ("pas de groupe commande");
        } else {
            //Recupere le groupeId
            $requete = "SELECT id FROM " . MAIN_DB_PREFIX . "Synopsis_commande_grp WHERE nom ='" . $codeAff . "'";
            $sql = requeteWithCache($requete);
            $res = fetchWithCache($sql);
            if (!$res) {
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_commande_grp (nom, datec) VALUES ('" . $codeAff . "',now())";
                $sql = requeteWithCache($requete);
                $grpId = $db->last_insert_id(MAIN_DB_PREFIX . 'Synopsis_commande_grp');
                if ($sql) {
                    $webContent .= "<td  class='ui-widget-content'>Cr&eacute;ation du groupe de commande OK</td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation du groupe de commande OK</td>" . "\n";
                } else {
                    $webContent .= "<td class='KOtd error ui-widget-content'>Cr&eacute;ation du groupe de commande KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation du groupe de commande KO</td>" . "\n";
                }
            } else {
                $grpId = $res->id;
                $webContent .= "<td  class='ui-widget-content'>Pas de modification du groupe de commande</td>";
                $mailContent .= "<td style='background-color: #FFF;'>Pas de modification du groupe de commande</td>" . "\n";
            }

            $webContent .= "<tr><th class='ui-state-default ui-widget-header'>Liaison Commande / groupe</td>";
            $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Liaison Commande / groupe</td>" . "\n";
            //efface la ref
            $lnDet = fetchWithCache($sqlGr);
            if (!$lnDet || $lnDet->commande_group_refid != $grpId || $lnDet->command_refid != $com->id) {
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_commande_grpdet WHERE refCommande = '" . $com->ref . "'";
                $sql = requeteWithCache($requete);
                //ajoute la ref dans le groupe
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_commande_grpdet
                                            (commande_group_refid,refCommande,command_refid )
                                     VALUES (" . $grpId . ",'" . $com->ref . "'," . $com->id . ")";
                $sql = requeteWithCache($requete);
                if ($sql) {
                    $webContent .= "<td  class='ui-widget-content'>Liaison commande - groupe OK</td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Liaison commande - groupe OK</td>" . "\n";
                } else {
                    $webContent .= "<td class='KOtd error ui-widget-content'>Liaison commande - groupe KO<span id='debugS'>Err: " . $db->lasterrno . "<br/>" . $db->lastqueryerror . "<br/>" . $db->lasterror . "</span></td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Liaison commande - groupe KO</td>" . "\n";
                }
            }
        }

        $com->update_price();
        $com->setStatut(0);
        $com->valid($user);
        if ($mode . "x" != "x") {
            $interface = new Interfaces($db);
            $result = $interface->run_triggers($mode, $com, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
//                                    $this->errors = $interface->errors;
            }
        }
    }

    foreach ($tabImportOK['propal'] as $ref => $id) {
        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
        $propal = new Propal($db);
        $propal->fetch($id);
        $propal->update_price();
        $propal->setStatut(0);
        $propal->valid($user);
    }



    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Send Mail                                                            |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */

//$arrCom = array();
//foreach ($mailSumUpContent['commande'] as $key => $val) {
//    $arrCom[$val->id] = $val;
//}
//foreach ($arrCom as $key => $val) {
//    
//}
    $mailHeader .= "</table>\n<table width=700 border=1 cellpadding=20 style='border-collapse: collapse;'>" . "\n";
    $mailHeader .= "<tr><th style='background-color: #0073EA; color: #fff;' colspan=2>Le d&eacute;tail de l'importation" . "</td>";

    $mailContent = $mailHeader . "<tr><td style='font-size: small;>" . $mailContent . "</table>\n";
    $mailFooter = "<div style='font-size: small;'>G&eacute;n&eacute;r&eacute; le " . date('d/m/Y') . " &agrave; " . date('H:i') . "</div>" . "\n";
    $mailFooter .= "<hr/>\n" . "\n";
    $mailFooter .= "<div><table border=0 width=700 cellpadding=20 style='border-collapse: collapse;'><tr><td><img height=100 src='" . DOL_URL_ROOT . "/theme/" . $conf->theme . "/Logo-72ppp.png'/></div>" . "\n";
    $mailFooter .= "<td valign=bottom><div style='font-size: small;'><b>Document strictement confidentiel</b><br>" . $mysoc->nom . '<br><em>' . $mysoc->address . '<br>' . $mysoc->zip . " " . $mysoc->town . '</em><br>Tel: ' . $mysoc->phone . "<br>Mail: <a href='mailto:" . $mysoc->email . "'>" . $mysoc->email . "</a><br>Url: <a href='" . $mysoc->url . "'>" . $mysoc->url . "</a></div><br/>" . "\n";
    $mailFooter .= "</table>" . "\n";
    $mailContent .= $mailFooter;

    if (!isset($conf->global->BIMP_MAIL_TO) || !isset($conf->global->BIMP_MAIL_FROM)) {
//        $webContent .= "<div style='color: #FF000;'>La fonction mail n'est pas configur&eacute;e</div>";
    } else {
        $mailFileArr = array();
        $mailFileMimeArr = array();
        $mailFileMimeNameArr = array();
        foreach ($fileArray as $key => $val) {
            $mailFileArr[] = $dir . "/" . $val;
            $mailFileMimeArr[] = "text/plain";
            $mailFileMimeNameArr[] = $val;
        }

        mailSyn2('Rapport d\'import', $conf->global->BIMP_MAIL_TO, "BIMP-ERP <" . $conf->global->BIMP_MAIL_FROM . ">", $mailContent, $mailFileArr, $mailFileMimeArr, $mailFileMimeNameArr, ($conf->global->BIMP_MAIL_CC . "x" == "x" ? "" : $conf->global->BIMP_MAIL_CC), ($conf->global->BIMP_MAIL_BCC . "x" == "x" ? "" : $conf->global->BIMP_MAIL_BCC), 0, 1, ($conf->global->BIMP_MAIL_CC . "x" == "x" ? "" : $conf->global->BIMP_MAIL_FROM));
    }


    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Save Historic                                                        |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */

//foreach ($fileArray as $key => $val) {
//    $requete = "INSERT INTO BIMP_import_history (webContent, mailContent,datec,filename)
//                     VALUES ('" . addslashes($webContent) . "','" . addslashes($mailContent) . "',now(),'" . $val . "')";
//    $sql = requeteWithCache($requete);
//}
    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Display                                                              |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */


    if ($displayHTML) {
        if ($cntFile == $maxFileImport)
            print "Import partielle trop de fichier. Merci de relancer l'import.";
        print $webContent;
    }
    print "<br/><br/>Temps import : " . (microtime(true) - $tempDeb) . "s.";
    print "<br/><br/>Req direct : " . $tabStat['d'] . ". Parcours " . $tabStat['pd'];
    print "<br/><br/>Req cache : " . $tabStat['c'] . ". Parcours " . $tabStat['pc'] . ". Parcours direct pour cache " . $tabStat['pcd'];
    print "<br/><br/>Cache suppr : " . $tabStat['ef'];

    /*
      +--------------------------------------------------------------------------------------------------------------+
      |                                                                                                              |
      |                                         Remove file "isrunning"                                              |
      |                                                                                                              |
      +--------------------------------------------------------------------------------------------------------------+
     */
    unlink($filename);
}
else {
    $webContent .= "<div class='ui-error error'> Pas de r&eacute;pertoire d'importation d&eacute;fini</div>";
    print $webContent;
}

global $logLongTime;
$logLongTime = false;
llxFooter();

function updateCategorie($ref, $prodId, $val) {
    if ($ref . 'x' != "x" && $prodId > 0) {
        global $remCatGlob, $db;

// 1 catégorie par Gamme Famille ...
//        ArtGammeEnu => string(5) Gamme               => ??
//        ArtFamilleEnu => string(7) Famille           => ??
//        ArtCollectEnu => string(10) Collection       => ??
//        ArtNatureEnu => string(6) Nature             => ??
//        ArtCategEnu => string(14) Sélection Bimp     => ??
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie"; // WHERE type=0";
        $sql = requeteWithCache($requete);
        $arrLocalCat = array();
        $gammeWasFound = false;
        $natureWasFound = false;
        $selectBIMPWasFound = false;
        $collecWasFound = false;
        $familleWasFound = false;
        while ($res = fetchWithCache($sql)) {
            //
            //  Sélection Bimp
            //
            $label = SynSanitize($res->label);
            $label1 = SynSanitize($val["ArtCategEnu"]);
            if ($label1 == $label) {
                $selectBIMPWasFound = $res->rowid;
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = " . $prodId . " AND fk_categorie = " . $res->rowid;
                $sql1 = requeteWithCache($requete);
                if ($db->num_rows($sql1) > 0) {
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $res->rowid . "," . $prodId . ")";
                    $sql1 = requeteWithCache($requete);
                }
            }

            //
            //  Nature
            //
            $label = SynSanitize($res->label);
            $label1 = SynSanitize($val["ArtNatureEnu"]);
            if ($label1 == $label) {
                $natureWasFound = $res->rowid;
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = " . $prodId . " AND fk_categorie = " . $res->rowid;
                $sql1 = requeteWithCache($requete);
                if ($db->num_rows($sql1) > 0) {
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $res->rowid . "," . $prodId . ")";
                    $sql1 = requeteWithCache($requete);
                }
            }

            //
            //  Collection
            //
            $label = SynSanitize($res->label);
            $label1 = SynSanitize($val["ArtCollectEnu"]);
            if ($label1 == $label) {
                $collecWasFound = $res->rowid;
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = " . $prodId . " AND fk_categorie = " . $res->rowid;
                $sql1 = requeteWithCache($requete);
                if ($db->num_rows($sql1) > 0) {
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $res->rowid . "," . $prodId . ")";
                    $sql1 = requeteWithCache($requete);
                }
            }

            //
            //  Famille
            //
            $label = SynSanitize($res->label);
            $label1 = SynSanitize($val["ArtFamilleEnu"]);
            if ($label1 == $label) {
                $familleWasFound = $res->rowid;
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = " . $prodId . " AND fk_categorie = " . $res->rowid;
                $sql1 = requeteWithCache($requete);
                if ($db->num_rows($sql1) > 0) {
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $res->rowid . "," . $prodId . ")";
                    $sql1 = requeteWithCache($requete);
                }
            }
            //
            //  Gamme
            //
            $label = SynSanitize($res->label);
            $label1 = SynSanitize($val["ArtGammeEnu"]);
            if ($label1 == $label) {
                $gammeWasFound = $res->rowid;
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = " . $prodId . " AND fk_categorie = " . $res->rowid;
                $sql1 = requeteWithCache($requete);
                if ($db->num_rows($sql1) > 0) {
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $res->rowid . "," . $prodId . ")";
                    $sql1 = requeteWithCache($requete);
                }
            }
        }

        //
        //  Gamme
        //
        if (!$gammeWasFound && $val["ArtGammeEnu"] . "x" != "x") {
            global $gammeCatId;
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type, fk_parent) VALUES (1,'" . $val["ArtGammeEnu"] . "',0, " . $gammeCatId . ")";
            $sql = requeteWithCache($requete);
            $catId = $db->last_insert_id($sql);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $catId . "," . $prodId . ")";
            $sql1 = requeteWithCache($requete);
        }
        //
        //  Famille
        //
        if (!$familleWasFound && $val["ArtFamilleEnu"] . "x" != "x") {
            global $familleCatId;
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type,fk_parent) VALUES (1,'" . $val["ArtFamilleEnu"] . "',0," . $familleCatId . ")";
            $sql = requeteWithCache($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$familleCatId.",".$newId.")";
//            $sql1 = requeteWithCache($requete);
            $catId = $db->last_insert_id($sql);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $catId . "," . $prodId . ")";
            $sql1 = requeteWithCache($requete);
        }
        //
        //  Nature
        //
        if (!$natureWasFound && $val["ArtNatureEnu"] . "x" != "x") {
            global $natureCatId;
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type,fk_parent) VALUES (1,'" . $val["ArtNatureEnu"] . "',0," . $natureCatId . ")";
            $sql = requeteWithCache($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$natureCatId.",".$newId.")";
//            $sql1 = requeteWithCache($requete);
            $catId = $db->last_insert_id($sql);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $catId . "," . $prodId . ")";
            $sql1 = requeteWithCache($requete);
        }
        //
        //  Selection BIMP
        //
        if (!$selectBIMPWasFound && $val["ArtCategEnu"] . "x" != "x") {
            global $selectBIMPCatId;
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type,fk_parent) VALUES (1,'" . $val["ArtCategEnu"] . "',0," . $selectBIMPCatId . ")";
            $sql = requeteWithCache($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$selectBIMPCatId.",".$newId.")";
//            $sql1 = requeteWithCache($requete);
            $catId = $db->last_insert_id($sql);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $catId . "," . $prodId . ")";
            $sql1 = requeteWithCache($requete);
        }

        //
        //  Collection
        //

        if (!$collecWasFound && $val["ArtCollectEnu"] . "x" != "x") {
            global $collectionCatId;
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type,fk_parent) VALUES (1,'" . $val["ArtCollectEnu"] . "',0," . $collectionCatId . ")";
            $sql = requeteWithCache($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$collectionCatId.",".$newId.")";
//            $sql1 = requeteWithCache($requete);
            $catId = $db->last_insert_id($sql);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $catId . "," . $prodId . ")";
            $sql1 = requeteWithCache($requete);
        }

        if (!is_array($remCatGlob)) {
            $remCatGlob = array();
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_import_product_cat ORDER BY rang";
            $sql = requeteWithCache($requete);
            while ($res = fetchWithCache($sql)) {
                $remCatGlob[$res->id] = array('pattern' => $res->pattern, 'categorie_refid' => $res->categorie_refid, "rang" => $res->rang);
            }
        }
        foreach ($remCatGlob as $id => $arrReco) {
            if (preg_match('/' . $arrReco['pattern'] . '/', $ref)) {
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_product, fk_categorie) VALUES (" . $prodId . "," . $arrReco['categorie_refid'] . ")";
                $sql = requeteWithCache($requete);
            }
        }
    }
}

function sendMail($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = '', $addr_bcc = '', $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '') {
    global $mysoc;
    global $langs;
    $mail = new CMailFile($subject, $to, $from, $msg, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, $errors_to);
    $res = $mail->sendfile();
    if ($res) {
        return (1);
    } else {
        return -1;
    }
}

function processPays($codePays) {
    global $db;
    if ($codePays . "x" != "x") {
        switch ($codePays) {
            case "CS": {
                    $codePays = "CZ";
                }
                break;
            case "BU": {
                    $codePays = "BF";
                }
                break;
            case "MAD": {
                    $codePays = "MG";
                }
                break;
            case "SL": {
                    $codePays = "SI";
                }
                break;
            case "TC": {
                    $codePays = "TD";
                }
                break;
            case "YU": {
                    $codePays = "RS";
                }
                break;
            case "QQ": {
                    return "";
                }
                break;
        }
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "c_country WHERE code = '" . $codePays . "'";
        $sql = requeteWithCache($requete);
        $res = fetchWithCache($sql);
        return ($res->rowid);
    } else {
        return 1; //Code pays France
    }
}

function processUser($import_key, $nomCom, $file, $val) {
//    return 59;
    global $remUserArray, $db;
    if ($import_key > 0) {
        $result = $db->query("SELECT `fk_object` FROM `" . MAIN_DB_PREFIX . "user_extrafields` WHERE `id8sens` = '" . $import_key . "'");
        while ($ligne = $db->fetch_object($result))
            return $ligne->fk_object;
        $result = getElementElement("idUser8Sens", "idUserGle", $import_key);
        if (isset($result[0]['d']))
            return $result[0]['d'];
        else
            return 1;
            affErreur("Pas de correspondance pour l'utilisateur dans BIMP-ERP : id 8Sens " . $import_key." non : ".print_r($val,1)." | commande ".$nomCom. " fichier ".$file);
//        if (!isset($remUserArray[$import_key])) {
//            $requete = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE ref_ext = " . $import_key;
//            $sql = requeteWithCache($requete);
//            if ($db->num_rows($sql) > 0) {
//                $res = fetchWithCache($sql);
//                $remUserArray[$import_key] = $res->rowid;
//            } else
//                $remUserArray[$import_key] = false;
//            return $remUserArray[$import_key];
//        } else {
//            return $remUserArray[$import_key];
//        }
    } else
        affErreur("Pas d'id pour l'user");
}

function affErreur($text) {
    echo "<br/><h3 style='color:red;'>" . $text . "</h3><br/>";
}

function fetchWithCache($result) {
    global $db, $tabRequeteP, $tabStat;
    $tabRequete = $tabRequeteP;
    $resultStr = get_resource_id($result);
    if (isset($tabRequete[$resultStr]['result'])) {
        $tabStat['pc'] ++;
        $tabRequete[$resultStr]['index'] = $tabRequete[$resultStr]['index'] + 1;
        $index = $tabRequete[$resultStr]['index'];
        if (isset($tabRequete[$resultStr]['result'][$index]))
            return $tabRequete[$resultStr]['result'][$index];
        else
            return false;
    }
    else {
        $tabStat['pd'] ++;
        return $db->fetch_object($result);
    }
}

/*
 * Tab requete = array('requete'=>list des req avec tab et afface
 *                      ''
 */

function requeteWithCache($requete) {
    global $db, $tabRequeteP, $tabStat;
    $tabRequete = $tabRequeteP;
    $noCache = true;

    if ($noCache) {
        $tabStat['d'] ++;
        $result = $db->query($requete);
        if (!$result)
            die("Erreur SQL NO CACHE : " . $requete);
        return $result;
    }

    if (!isset($tabRequete[$requete])) {
        $tabRequeteM = explode(" ", $requete);
        $tabT = 'nc';
        $tabSupprT = false;
        foreach ($tabRequeteM as $morcReqeute) {
            if (stripos($morcReqeute, "llx") !== false) {
                $tabT = $morcReqeute;
                break;
            }
        }
        if ($tabT != 'nc') {
            if (stripos($requete, "INSERT") !== false ||
                    stripos($requete, "UPDATE") !== false ||
                    stripos($requete, "DELETE") !== false) {
                $tabSupprT = $tabT;
                $tabT = 'nc';
            }
            $tabRequete[$requete]['tab'] = $tabT;
            $tabRequete[$requete]['tabSuppr'] = $tabSupprT;
        } else {
            die("Impossible de trouver le nom de la table." . $tabT);
        }
    }

    $tab = $tabRequete[$requete]['tab'];
    $tabSuppr = $tabRequete[$requete]['tabSuppr'];


    if ($tabSuppr && isset($tabRequete[$tabSuppr])) {
        $tabRequete[$tabSuppr] = array();
        $tabStat['ef'] ++;
    }



    if (isset($tabRequete[$tab][$requete])) {
        $tabStat['c'] ++;
        $result = $tabRequete[$tab][$requete];
        $strResult = get_resource_id($result);
        $tabRequete[$strResult]['index'] = -1;
        $result->data_seek(0);
    } else {
        $tabStat['d'] ++;
        $result = $db->query($requete);
        if (!$result)
            die("Erreur SQL : " . $requete);
        if ($tab != 'nc') {
            $strResult = get_resource_id($result);
            $tabRequete[$strResult]['result'] = array();
            $i = 0;
            while ($obj = $db->fetch_object($result)) {
                $tabStat['pcd'] ++;
//            $tabRequete[$tab][$requete]['result'][$i] = $obj;
//            $tabRequete[$tab][$requete]['count'] = $i;
//            $tabRequete[$tab][$requete]['sql'] = $result;
                $tabRequete[$strResult]['result'][$i] = $obj;
                $i++;
            }
            $result->data_seek(0);
//            print_r($tabRequete[$strResult]['result']);
            $tabRequete[$tab][$requete] = $result;
            $tabRequete[$strResult]['index'] = -1;
        }
    }
    $tabRequeteP = $tabRequete;
    unset($tabRequete);
    return $result;
}

function get_resource_id($resource) {
    return is_object($resource) ? spl_object_hash($resource /* strlen("Resource id #") */) : 'pas de ress';
}

function updateType($ref, $prodId) {
    global $db;
    if ($ref . 'x' != "x" && $prodId > 0) {
        $type = getProdType($ref);
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "product_extrafields WHERE fk_object = " . $prodId;
        $sql = $db->query($requete);
        if($db->num_rows($sql) == 0){
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "product_extrafields (fk_object, type2) VALUES ('".$prodId."','".$type."')";
        }
        else{
            $requete = "UPDATE " . MAIN_DB_PREFIX . "product_extrafields SET type2 = '" . $type . "' WHERE fk_object = " . $prodId;
        }
        requeteWithCache($requete);
    }
}

function getProdType($ref) {
    if ($ref . 'x' != "x" && $ref != "0") {
        global $remTypeGlob;
        if (!is_array($remTypeGlob)) {
            $remTypeGlob = array();
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_PrepaCom_import_product_type ORDER BY rang";
            $sql = requeteWithCache($requete);
            while ($res = fetchWithCache($sql)) {
                $remTypeGlob[$res->rang] = array('pattern' => $res->pattern, 'product_type' => $res->product_type, "rang" => $res->rang);
            }
        }
        foreach ($remTypeGlob as $arrReco) {
            if (preg_match('/' . str_replace('/', '\/', $arrReco['pattern']) . '/', $ref)) {
                return $arrReco['product_type'];
            }
        }
        return 0;
    }
}

function getCat($label) {
    global $db;
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE label = '" . $label . "'";
    $sql = requeteWithCache($requete);
    $res = fetchWithCache($sql);
    if ($res) {
        $catId = $res->rowid;
    } else {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "categorie (visible,label,type,fk_parent) VALUES (1,'" . $label . "',0, 0)";
        $sql = requeteWithCache($requete);
        $catId = $db->last_insert_id($sql);
    }
    return $catId;
}

function sizeofvar($var) {

    $start_memory = memory_get_usage();
    $temp = unserialize(serialize($var));
    $taille = memory_get_usage() - $start_memory;
    return $taille;
}

?>