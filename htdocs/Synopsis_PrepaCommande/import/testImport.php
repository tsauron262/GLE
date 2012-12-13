<?php
/*
  ** GLE by Synopsis et DRSI
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
  * GLE-1.2
  */
  ini_set ('max_execution_time', 0);
  $displayHTML=true;
  if($_REQUEST['modeCli'] == 1) {
      $displayHTML = false;
  }
  require_once('pre.inc.php');
//  require_once('Var_Dump.php');

  require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
  require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Tools/class/CMailFile.class.php');
  include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

  $dir = $conf->global->BIMP_PATH_IMPORT;
  $remArrayComLigne = array(); // array contenant les commandes importer dans le fichier, pour supprimer les lignes en trops


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
//Problème icone dans commande/fiche.php?id= (contrat et déplacement)
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
// Contrat contrat/fiche.php => prob ordre select_user
// Remettre liste au lieu de carte dans product/liste.php recherche par ref
// Contrat -> commandedet => prob accent
// total ht ds contrat bug
// contrat en double si non rattaché à un produit http://192.168.1.10/GLE/contrat/fiche.php?id=3
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
//rapport.php => DemandeInterv => prenom / nom dans le menu déroulant
//Vendu par gamme => valign=top
//Modif modele Einstein
//PrepaCommande champs intervenant => pas trier dans le bon ordre (prenom nom)
//Date dans FI=> prob accent dans liste et fiche.php
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


if (file_exists("/tmp/.importRunning")){
    print "Un import est d&eacute;j&agrave; en cours\n";
    exit;
}
touch("/tmp/.importRunning");
$mailContent = "";
$mailSumUpContent = array();
$mailSumUpContent=array('nbFile' => 0, 'nbLine' => 0, 'nbLigneModif' => 0, 'commande' => array());
$fileArray=array();
$imported = "/imported/";

global $langs, $conf;

  $webContent = "";
if($displayHTML) {
  llxHeader();
}
  $webContent .= " <a href='index.php'><span style='float: left;' class='ui-icon ui-icon-extlink'></span><span>Retour</span></a>";
  $arrayImport = array();
  //1 ouvre le rep et trouve les fichiers
  $mailContent = "Liste des fichiers: ";
  $cntFile = -1;
  if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false)
            {
                $cntFile++;
                if ($file == ".." || $file == '.') continue;
                if (preg_match('/^\./',$file)) continue;
                //2 ouvre le fichier
                if (is_readable($dir ."/". $file) && is_file(($dir ."/". $file) )){
                    $webContent .=  "<div><div class='titre'>fichier : ".$file."</div>";
                    $mailContent .= '<div style="color: 00FFC6;">'.$file.'</div>'."\n";
                    $fileArray[]=$file;
                    //iconv file
                    $webContent .=  "<table width=600 cellpadding=10><tr><th class='ui-state-default ui-widget-header'>Conversion du fichier</th>";
                    exec("/usr/bin/perl -p -e 's/\r/\n/g' < ".$dir."/".$file."  > /tmp/".$file.".preparse");
                    exec("/usr/bin/iconv -f MAC -t UTF8 /tmp/".$file.".preparse > /tmp/".$file.".iconv");
                    if (is_file("/tmp/".$file.".iconv"))
                    {
                        $webContent .=  "<td class='ui-widget-content'>OK</td>";
                        $content = file_get_contents("/tmp/".$file.".iconv");
                        $lines = preg_split("/\n/",$content);
                        //3 analyse les colonnes
                        $ligneNum = 0;
                        $arrDesc = array();
                        $arrConvNumcol2Nomcol = array();
                        $mailSumUpContent['nbFile']++;
                        foreach($lines as $key=>$val)
                        {
                            if (! strlen($val) > 10)
                               continue;
                            $cols = preg_split("/[\t]/",$val);
                            if ($ligneNum == 0)
                            {
                                $arrDesc=$cols;
                                $ligneNum++;
                            } else if ($ligneNum == 1){
                                foreach($cols as $key1=>$val1)
                                {
                                    $arrConvNumcol2Nomcol[$key1]=$val1;
                                    $arrayImport[0][$val1]=$arrDesc[$key1];
                                    $arrayImport[1][$val1]=$val1;
                                }
                                $ligneNum++;
                            } else {
                                foreach($cols as $key2 => $val2 )
                                {
                                    switch ($arrConvNumcol2Nomcol[$key2]){
                                        case 'PcvDate':
                                        {
                                            //convert to epoch
                                           if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$val2,$arrTmp))
                                           {
                                               $val2 = strtotime($arrTmp[3]."-".$arrTmp[2]."-".$arrTmp[1]);
                                           }
                                        }
                                        break;
                                        case 'ArtPrixBase':
                                        case 'PlvPUNet':
                                        case 'PlvPA':
                                        case 'PcvMtHT':
                                        {
                                            $val2=preg_replace('/,/','.',$val2);
                                        }
                                        break;
                                        case 'TaxTaux':
                                        {
                                            $val2=preg_replace('/,/','.',$val2);
                                            if ($val2."x"=="x") $val2="19.6";
                                        }
                                        break;
                                        default:
                                        {
                                            $val2=preg_replace('/\'/','\\\'',$val2);
                                        }
                                        break;
                                    }
                                    $arrayImport[$cntFile][$key][$arrConvNumcol2Nomcol[$key2]]= utf8_decode($val2);
                                }
                                $ligneNum++;
                            }
//    var_dump::display($cols);
                        }
                        unlink("/tmp/".$file.'.iconv');
                        unlink("/tmp/".$file.'.preparse');
                        //Move file
                        if(!is_dir($dir."/imported/"))
                        {
                            $resultMd= mkdir($dir.$imported);
                            if ($resultMd)
                            {
                                $webContent.= "<tr><th class='ui-state-default ui-widget-header'>deplacement fichier</th>";
                                $resultMv = rename($dir."/".$file,$dir.$imported.$file);
                                if ($resultMv){
                                    $webContent.= "<td class='ui-widget-content'>OK</td>";
                                } else {
                                    $webContent.= "<td class='ui-widget-content'>KO</td>";
                                }
                            }

                        } else {
                            $webContent .= "<tr><th class='ui-state-default ui-widget-header'>deplacement fichier</th>";
//                            var_dump($dir."/".$file);
//                            var_dump($dir.$imported.$file);
                            $resultMv = rename ($dir."/".$file,$dir.$imported.$file);
                            if ($resultMv)
                            {
                                $webContent.= "<td class='ui-widget-content'>OK</td>";
                            } else {
                                $webContent.= "<td class='ui-widget-content'>KO</td>";
                            }
                        }
                    } else {
                        $webContent .=  "<tdclass='ui-widget-content'>Erreur de conversion</td>";
                    }
                    $webContent .=  "</table>";
                }
            }
            closedir($dh);
        }
    } else {
         $webContent .=  "<div class='ui-error error'> Pas de r&eacute;pertoire d\'importation d&eacute;fini</div>";
    }

//  var_dump($arrayImport);

        /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Chargement catégorie                                                 |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
        */
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE label = 'Famille' AND ( level=2 OR level is null)";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $gammeCatId = $res->rowid;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE label = 'Gamme' AND ( level=2 OR level is null)";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $familleCatId = $res->rowid;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE label = 'Collection' AND ( level=2 OR level is null)";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $collectionCatId = $res->rowid;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE label = 'Nature' AND ( level=2 OR level is null)";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $natureCatId = $res->rowid;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE label LIKE '%lection Bimp' AND ( level=2 OR level is null)";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $selectBIMPCatId = $res->rowid;

//  exit();
        /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Import                                                               |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
        */
  $webContent .=  "<table width=600 cellpadding=10>";
  $mailContent .= "<table width=600 cellpadding=10>"."\n";
//  $debugLigne=120;
//  $idebug=0;
  foreach($arrayImport as $listFiles=>$File){
      foreach($File as $key=>$val){
//        $idebug++;
//        if($idebug == $debugLigne) { print "debug break"; break;}
        if ($key < 2) continue;
        if($val['PcvCode']."x"=="x") continue; //8sens export cas 1
        if($val["PcvCode"]=='Fin')  //8sens export cas 2
        {
            break;
        }
        $paysGlobal = processPays($val['PysCode']);
        $externalUserId = $val['PcvGPriID'];
        $internalUserId = processUser($externalUserId);
        $mailSumUpContent['nbLine']++;
        $webContent .=  "<tr><th class='ui-state-default ui-widget-hover' colspan=2>Ligne: ".$key. "  Commande:".$val["PcvCode"]."</th>";
        $mailContent .= "<tr><th style='color: #fff; background-color: #0073EA;' colspan=2>Ligne: ".$key. "  Commande:".$val["PcvCode"]."</th>"."\n";

        /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Secteur societe                                                      |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
        */
        $secteurActiv=false;
        if ($val['CliActivEnu']."x" != "x")
        {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_secteur WHERE LOWER(libelle) = '".strtolower($val['CliActivEnu'])."' ";
            $sql = $db->query($requete);
            if ($db->num_rows($sql)> 0)
            {
                $res = $db->fetch_object($sql);
                $secteurActiv = $res->id;
            } else {
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."c_secteur (id,code,libelle,active) VALUES (max(id)+1, '".SynSanitize($val['CliActivEnu'])."','".addslashes($val['CliActivEnu'])."',1)";
                $sql = $db->query($requete);
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

        $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Soci&eacute;t&eacute;";
        $mailContent .= "<tr><th style='color: #fff; background-color: #0073EA;'>Soci&eacute;t&eacute;</th>"."\n";
        $nomSoc = $val["CliLib"];
        $codSoc = $val["CliCode"];
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE code_client = '".$codSoc."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $socid = "";
        $assujTVA = 0;
        $typeEnt = 0;

        switch ($val['CliCategEnu']){
        case "PME":{
            $typeEnt="8";
        }
        break;
        case "Educ":{
            $typeEnt="5";
        }
          break;
        case "PARTICULIER":{
            $typeEnt="8";
        }
            break;
        case "PARTICULIER":{
                $typeEnt="8";
        }
        break;

        }

        if ($val['TyvCode'] =="FR" || $val['TyvCode'] == "CP")
        {
            $assujTVA = 1;
        }
        $tmpSoc = "";

        if ($db->num_rows($sql) > 0){
            $socid = $res->rowid;



            $sqlUpt=array();
            if ($res->nom != $nomSoc)
                $sqlUpt[] = " nom = '".$nomSoc."'";
            if ($res->siret != $val['CliSIRET'])
                $sqlUpt[] = " siret = '".(strlen($val['CliSIRET'])>0?$val['CliSIRET']:"NULL")."'";
            if ($res->address != $val['CliFAdrRue1']." ".$val['CliFAdrRue2'])
                $sqlUpt[] = " address = '".(strlen($val['CliFAdrRue1']." ".$val['CliFAdrRue2'])>2?$val['CliFAdrRue1']." ".$val['CliFAdrRue2']:"NULL")."'";
            if ($res->cp != $val['CliFAdrZip'])
                $sqlUpt[] = " cp = '".(strlen($val['CliFAdrZip'])>0?$val['CliFAdrZip']:"NULL")."'";
            if ($res->ville != $val['CliFAdrCity'])
                $sqlUpt[] = " ville = '".(strlen($val['CliFAdrCity'])>0?$val['CliFAdrCity']:"NULL")."'";
            if ($res->tel != $val['MocTel'])
                $sqlUpt[] = " tel = '".(strlen($val['MocTel'])>0?$val['MocTel']:"NULL")."'";
            if ($res->fk_pays != $paysGlobal)
                $sqlUpt[] = " fk_pays = ".(strlen($paysGlobal)>0?"'".$paysGlobal."'":"NULL");
            if ($res->tva_assuj != $assujTVA)
                $sqlUpt[] = " tva_assuj = ".$assujTVA;
            if ($secteurActiv && $secteurActiv != $res->fk_secteur)
                $sqlUpt[] = " fk_secteur = ".$secteurActiv;
            if ($typeEnt != $res->fk_typent)
                $sqlUpt[] = " fk_typent = ".$typeEnt;
            if ($res->titre != $val['CliTitleEnu'] )
                $sqlUpt[] = " titre = '".$val['CliTitleEnu'] ."'";

            if (count($sqlUpt) > 0)
            {
                //Creation de la societe ou mise à jour si code client exist
                $updtStr = join(',',$sqlUpt);
                $requete = "UPDATE ".MAIN_DB_PREFIX."societe SET ".$updtStr. " WHERE code_client = ".$codSoc;
                $sql = $db->query($requete);
                if ($sql){
                    $webContent .=  "<td class='ui-widget-content'>Mise &agrave; jour soci&eacute;t&eacute; OK</td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour soci&eacute;t&eacute; OK</td>"."\n";
    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Les commerciaux de la societe                                        |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */
                        $tmpSoc = new Societe($db);
                        $tmpSoc->fetch($socId);
                        if ($internalUserId > 0)
                            $tmpSoc->add_commercial($user,$internalUserId);

                        // Appel des triggers
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('COMPANY_MODIFY',$tmpSoc,$user,$langs,$conf);
                        if ($result < 0) { $error++; $errors=$interface->errors; }
                } else {
                    $webContent .=  "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour soci&eacute;t&eacute; KO</td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour soci&eacute;t&eacute; KO</td>"."\n";
                }
            } else {
                $webContent .=  "<td  class='ui-widget-content'>Pas de modification soci&eacute;t&eacute;</td>";
                $mailContent .= "<td style='background-color: #FFF;'>Pas de modification soci&eacute;t&eacute;</td>"."\n";
            }
        } else {

            $requete = "INSERT INTO ".MAIN_DB_PREFIX."societe
                                    (nom,
                                     code_client,
                                     datec,
                                     datea,
                                     siret,
                                     address,
                                     cp,
                                     ville,
                                     tel,
                                     fk_pays,
                                     client,
                                     titre,
                                    external_id,
                                    tva_assuj,
                                    ".($secteurActiv?"fk_secteur,":"")."
                                    fk_typent
                                    )
                             VALUES ('".$nomSoc."',
                                     '".$codSoc."',
                                     now(),
                                     now(),
                                     '".(strlen($val['CliSIRET'])>0?$val['CliSIRET']:"NULL")."',
                                     '".(strlen($val['CliFAdrRue1']." ".$val['CliFAdrRue2'])>2?$val['CliFAdrRue1']." ".$val['CliFAdrRue2']:"NULL")."',
                                     '".(strlen($val['CliFAdrZip'])>0?$val['CliFAdrZip']:"NULL")."','".(strlen($val['CliFAdrCity'])>0?$val['CliFAdrCity']:"NULL")."',
                                     '".(strlen($val['MocTel'])>0?$val['MocTel']:"NULL")."',
                                     ".(strlen($paysGlobal)>0?"'".$paysGlobal."'":"NULL").",
                                     1,
                                    '".$val['CliTitleEnu']."',
                                    ".$val['AdpGAdrID'].",
                                    ".$assujTVA.",
                                    ".($secteurActiv?$secteurActiv.",":"")."
                                    ".$typeEnt
                                .")";
            $sql = $db->query($requete);
            $socid=$db->last_insert_id("".MAIN_DB_PREFIX."societe");

            if ($sql){
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

                $tmpSoc = new Societe($db);
                $tmpSoc->fetch($socId);
                if ($internalUserId > 0)
                    $tmpSoc->add_commercial($user,$internalUserId);
                // Appel des triggers
                $interface=new Interfaces($db);
                $result=$interface->run_triggers('COMPANY_CREATE',$tmpSoc,$user,$langs,$conf);
                if ($result < 0) { $error++; $errors=$interface->errors; }
                // Fin appel triggers
                $webContent .=  "<td class='ui-widget-content'>Cr&eacute;ation de la soci&eacute;t&eacute; OK";
                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation de la soci&eacute;t&eacute; OK</td>"."\n";
            } else {
                $webContent .=  "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation de la soci&eacute;t&eacute; KO";
                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation de la soci&eacute;t&eacute; KO</td>"."\n";
            }
        }


        if ($internalUserId > 0)
            $tmpSoc->add_commercial($user,$internalUserId);

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

        $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Contact de la soci&eacute;t&eacute;</th>";
        $mailContent .= "<tr><th style='color:#fff; background-color: #0073EA;'>Contact de la soci&eacute;t&eacute;</th>"."\n";


        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."socpeople WHERE external_id = ".$socpeopleExternalId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $socContact = false;
        if ($db->num_rows($sql) > 0)
        {
            $sqlUpt=array();
            $socContact = $res->rowid;
            if ($res->phone != $val['PcvMocTel'])
                $sqlUpt[] = " phone = '".$val["PcvMocTel"]."'";
            if ($res->phone_mobile != $val['PcvMocPort'])
                $sqlUpt[] = " phone_mobile = '".$val['PcvMocPort']."'";
            if ($res->civilite != $genre)
                $sqlUpt[] = " civilite = '".$genre."'";
            if (count($sqlUpt) > 0)
            {
                $updtStr = join(',',$sqlUpt);
                $requete = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ".$updtStr. " WHERE rowid =".$socContact;
                $sql = $db->query($requete);
                if ($sql)
                {
                    $webContent .=  "<td class='ui-widget-content'>Mise &agrave; jour contact OK";
                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour contact OK</td>"."\n";
                } else {
                    $webContent .=  "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour contact KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                    $mailContent .= "<td style='background-color: #FFF;'>Mise &agrave; jour contact KO</td>"."\n";
                }
            } else {
                $webContent .=  "<td class='ui-widget-content'>Pas de mise &agrave; jour contact n&eacute;cessaire";
                $mailContent .= "<td style='background-color: #FFF;'>Pas de mise &agrave; jour contact n&eacute;cessaire</td>"."\n";
            }
        } else {
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."socpeople
                                    (datec,fk_soc,civilite,name,firstname,phone,phone_mobile,external_id)
                             VALUES (now(),".$socid.",'".$genre."','".$nom."','".$prenom."','".$val['PcvMocTel']."','".$val['PcvMocPort']."',".$socpeopleExternalId.")";
            $sql = $db->query($requete);
            if ($sql){
                $webContent .=  "<td class='ui-widget-content'>Cr&eacute;ation contact OK";
                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation contact OK</td>"."\n";
            }else {
                $webContent .=  "<td class='KOtd error  ui-widget-content'>Cr&eacute;ation contact KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span>";
                $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation contact KO</td>"."\n";
            }
            $socContact = $db->last_insert_id('".MAIN_DB_PREFIX."socpeople');
        }
    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Adresse de livraison                                                 |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */

        if($val['PcvLAdpID']>0)
        {
            $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Adresse de livraison</th>";
            $mailContent .=  "<tr><th style='color:#fff; background-color: #0073EA;'>Adresse de livraison</th>"."\n";
            $livAdd = "";
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."societe_adresse_livraison
                         WHERE external_id = '".$val['PcvLAdpID']."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            if ($db->num_rows($sql) > 0)
            {
                $livAdd=$res->rowid;
    //            $webContent .=  "<td  class='ui-widget-content'> Pas de mise &agrave; jour de l'ad. de livraison</td>";
    //            $mailContent .= "<td  style='background-color: #fff;'> Pas de mise &agrave; jour de l'ad. de livraison"."\n";
                $nomLivAdd = $val['CliLAdrLib']." - ".$val['PcvLAdpID'];
                $requete = "UPDATE ".MAIN_DB_PREFIX."societe_adresse_livraison
                            SET fk_societe = ".$socid."
                                , cp = '".$val['CliLAdrZip']."'
                                , ville = '".$val['CliLAdrCity']."'
                                , address = '".$val['CliLAdrRue1']." ".$val['CliLAdrRue2']."'
                                , fk_pays = ".($paysGlobal."x"!="x"?$paysGlobal:NULL)."
                                , label = '".$nomLivAdd."'
                            WHERE external_id= ".$val['PcvLAdpID'];
                $sql = $db->query($requete);
                if ($sql){
                    $webContent .=  "<td  class='ui-widget-content'>Mise &agrave; jour ad. livraison OK";
                    $mailContent .= "<td  style='background-color: #fff;'>Mise &agrave; jour ad. livraison OK"."\n";
                } else {
                    $webContent .=  "<td  class='ui-widget-content'>Mise &agrave; jour ad. livraison KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span>";
                    $mailContent .= "<td  style='background-color: #fff;'>Mise &agrave; jour ad. livraison KO"."\n";
                }

                //Si modif
            } else {
                $nomLivAdd = $val['CliLAdrLib']." - ".$val['PcvLAdpID'];
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."societe_adresse_livraison (fk_societe, cp, ville, address, fk_pays, label,external_id)
                                 VALUES (".$socid.",'".$val['CliLAdrZip']."','".$val['CliLAdrCity']."','".$val['CliLAdrRue1']." ".$val['CliLAdrRue2']."',1,'".$nomLivAdd."',".$val['PcvLAdpID'].")";
                $sql = $db->query($requete);
                if ($sql){
                    $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation ad. livraison  OK";
                    $mailContent .= "<td  style='background-color: #fff;'>Cr&eacute;ation ad. livraison  OK"."\n";
                } else {
                    $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation ad. livraison  KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span>";
                    $mailContent .= "<td  style='background-color: #fff;'>Cr&eacute;ation ad. livraison  KO"."\n";
                }
                $livAdd=$db->last_insert_id('".MAIN_DB_PREFIX."societe_adresse_livraison');
            }
      }
    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Les produits                                                         |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */
        $prodId=false;
//PlvNuf

        if ($val['ArtID']>0 && ($val['PlvNuf'] == 'Normal' || $val['PlvNuf'] == 'Port'))
        {

    /*

     ArtIsGaranti => string(7) Garanti            => ??
     ArtFree3 => string(17)  Désign2-2 Météor        => DurValidité contrat
     ArtDelai => string(20) Délai d'intervention     => SLA
     ArtDelai => string(20) Délai d'intervention     => SLA

    */

            $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Produits</th>";
            $mailContent .=  "<tr><th  style='color:#fff; background-color: #0073EA;'>Produits</th>"."\n";
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE external_id = '".$val['ArtID']."' OR ref='".$val['PlvCode']."'";
            $sql =$db->query($requete);
            $res=$db->fetch_object($sql);
            $sqlUpt = array();
            if ($db->num_rows($sql)>0)
            {
                $prodId = $res->rowid;
                if ($res->description != $val['PlvLib'])
                    $sqlUpt[] = " description = '".$val['PlvLib']."'";
                if ($res->label != $val['ArtLib'])
                    $sqlUpt[] = " label = '".$val['ArtLib']."'";
                if ($res->price != $val['ArtPrixBase'])
                    $sqlUpt[] = " price = '".$val['ArtPrixBase']."'";
                if ($res->PrixAchatHT != $val['PlvPA'])
                    $sqlUpt[] = " PrixAchatHT = '".$val['PlvPA']."'";
                if ($res->durSav != $val['ArtDureeGar'])
                    $sqlUpt[] = " durSav = ".($val['ArtDureeGar']>0?$val['ArtDureeGar']:0)."";
                if ($res->tva_tx != $val['TaxTaux'])
                    $sqlUpt[] = " tva_tx = '".$val['TaxTaux']."'";

                if (count($sqlUpt) > 0)
                {
                    $updtStr = join(',',$sqlUpt);
                    $requete = "UPDATE ".MAIN_DB_PREFIX."product SET ".$updtStr. " WHERE external_id =".$val['ArtID'];
                    $sql = $db->query($requete);
                    if ($sql){
                        $webContent .=  "<td class='ui-widget-content'>Mise &agrave; jour produit OK</td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour produit OK</td>"."\n";
                        $tmpProd = new Product($db);
                        $tmpProd->update_price($prodId,($val['ArtPrixBase']>0?$val['ArtPrixBase']:0),"HT",$user,($val['TaxTaux']>0?$val['TaxTaux']:0));

                    } else {
                        $webContent .=  "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour produit KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour produit KO</td>"."\n";
                    }
                } else {
                    $webContent .=  "<td class='ui-widget-content'>Pas de mise &agrave; jour produit n&eacute;cessaire</td>";
                    $mailContent .=  "<td style='background-color: #FFF;'>Pas de mise &agrave; jour produit n&eacute;cessaire</td>"."\n";
                }
    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Catégorie et type                                                    |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */
                updateCategorie($val['PlvCode'],$prodId,$val);
                updateType($val['PlvCode'],$prodId);
            } else {
                $requete = "INSERT ".MAIN_DB_PREFIX."product
                                   (datec,
                                    ref,
                                    label,
                                    description,
                                    price,
                                    PrixAchatHT,
                                    price_base_type,
                                    envente,
                                    durSav,
                                    tva_tx,
                                    external_id)
                            VALUES (now(),
                                    '".$val['PlvCode']."',
                                    '".$val['ArtLib']."',
                                    '".$val['PlvLib']."',
                                    ".($val['ArtPrixBase']>0?$val['ArtPrixBase']:0).",
                                    ".($val['PlvPA']>0?$val['PlvPA']:0).",
                                    'HT',
                                    1,
                                    ".($val['ArtDureeGar']>0?$val['ArtDureeGar']:0).",
                                    ".($val['TaxTaux']>0?$val['TaxTaux']:0).",
                                    ".($val['ArtID']>0?$val['ArtID']:0).") ";
                $sql = $db->query($requete);
                if ($sql){
                    $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation produit OK</td>";
                    $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation produit OK</td>"."\n";
                    $prodId = $db->last_insert_id(MAIN_DB_PREFIX.'product');

                    $tmpProd = new Product($db);
                    $tmpProd->update_price($prodId,($val['ArtPrixBase']>0?$val['ArtPrixBase']:0),"HT",$user,($val['TaxTaux']>0?$val['TaxTaux']:0));

    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Catégorie et type                                                    |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */
                    updateCategorie($val['PlvCode'],$prodId,$val);
                    updateType($val['PlvCode'],$prodId);
                } else {
                    $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation produit KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                    $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation produit KO</td>"."\n";
                }
                //$webContent .=  $requete . "<br/>";
            }
        }

        if ($socid > 0)
        {

    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         La commande                                                          |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */
            $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Commande</td>";
            $mailContent .= "<tr><th style='background-color: #0073EA; color: #FFF;'>Commande</th>"."\n";
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE ref = '".$val['PcvCode']."'";
            $sql = $db->query($requete);
            $res=$db->fetch_object($sql);
            //Creer la commande
            $comId = false;

            $condReg = false;
            $modReg = false;
//            if (preg_match('/^Ch/',$val['PcvModRegl']))
//            {
//                $modReg = 7;
//            } else if (preg_match('/^Vi/',$val['PcvModRegl']))
//            {
//                $modReg = 2;
//            } else if (preg_match('/^Pre/',$val['PcvModRegl']))
//            {
//                $modReg = 3;
//            } else if (preg_match('/^Tr/',$val['PcvModRegl']))
//            {
//                $modReg = 8;
//            } else if (preg_match('/^TI/',$val['PcvModRegl']))
//            {
//                $modReg = 1;
//            } else if (preg_match('/^Ca/',$val['PcvModRegl']))
//            {
//                $modReg = 6;
//            } else if (preg_match('/^CB/',$val['PcvModRegl']))
//            {
//                $modReg = 6;
//            } else if (preg_match('/^Fa/',$val['PcvModRegl']))
//            {
//                $modReg = 10;
//            } else if (preg_match('/^Es/',$val['PcvModRegl']))
//            {
//                $modReg = 4;
//            } else {
//                $modReg = 0;
//            }
//            if (preg_match('/30/',$val['PcvModRegl'])){
//                if (preg_match('/net/',$val['PcvModRegl']))
//                {
//                    $condReg = 2;
//                } else {
//                    $condReg = 3;
//                }
//            }
//            if (preg_match('/60/',$val['PcvModRegl'])){
//                if (preg_match('/net/',$val['PcvModRegl']))
//                {
//                    $condReg = 4;
//                } else {
//                    $condReg = 5;
//                }
//            }
//
//
//            if(!$condReg) $condReg = 1;

            switch($val['PcvGRgmID']){
                default:
                {
                    $modReg=0;
                    $condReg=1;
                }
                break;
                case "6":{
                    $modReg=0;
                    $condReg=24;
                }
                break;
                case "16":{
                    $modReg=11;
                    $condReg=15;
                }
                break;
                case "42":{
                    $modReg=11;
                    $condReg=4;
                }
                break;
                case "77":{
                    $modReg=11;
                    $condReg=13;
                }
                break;
                case "79":{
                    $modReg=11;
                    $condReg=12;
                }
                break;
                case "126":{
                    $modReg=12;
                    $condReg=24;
                }
                break;
                case "291":{
                    $modReg=11;
                    $condReg=3;
                }
                break;
                case "337":{
                    $modReg=7;
                    $condReg=7;
                }
                break;
                case "341":{
                    $modReg=7;
                    $condReg=20;
                }
                break;
                case "353":{
                    $modReg=8;
                    $condReg=7;
                }
                break;
                case "357":{
                    $modReg=11;
                    $condReg=2;
                }
                break;
                case "382":{
                    $modReg=0;
                    $condReg=1;
                }
                break;
                case "383":{
                    $modReg=0;
                    $condReg=20;
                }
                break;
                case "384":{
                    $modReg=6;
                    $condReg=26;
                }
                break;
                case "385":{
                    $modReg=7;
                    $condReg=1;
                }
                break;
                case "386":{
                    $modReg=7;
                    $condReg=25;
                }
                break;
                case "387":{
                    $modReg=7;
                    $condReg=2;
                }
                break;
                case "388":{
                    $modReg=7;
                    $condReg=4;
                }
                break;
                case "389":{
                    $modReg=7;
                    $condReg=2;
                }
                break;
                case "390":{
                    $modReg=7;
                    $condReg=3;
                }
                break;
                case "391":{
                    $modReg=0;
                    $condReg=8;
                }
                break;
                case "392":{
                    $condReg=4;
                    $modReg=7;
                }
                break;
                case "393":{
                    $condReg=5;
                    $modReg=7;
                }
                break;
                case "394":{
                    $condReg=12;
                    $modReg=7;
                }
                break;
                case "395":{
                    $condReg=19;
                    $modReg=7;
                }
                break;
                case "396":{
                    $condReg=18;
                    $modReg=7;
                }
                break;
                case "397":{
                    $condReg=15;
                    $modReg=7;
                }
                break;
                case "398":{
                    $condReg=2;
                    $modReg=8;
                }
                break;
                case "399":{
                    $condReg=3;
                    $modReg=8;
                }
                break;
                case "400":{
                    $condReg=10;
                    $modReg=8;
                }
                break;
                case "401":{
                    $condReg=4;
                    $modReg=8;
                }
                break;
                case "402":{
                    $condReg=5;
                    $modReg=8;
                }
                break;
                case "403":{
                    $condReg=12;
                    $modReg=8;
                }
                break;
                case "404":{
                    $condReg=19;
                    $modReg=8;
                }
                break;
                case "405":{
                    $condReg=18;
                    $modReg=8;
                }
                break;
                case "406":{
                    $condReg=15;
                    $modReg=8;
                }
                break;
                case "407":{
                    $condReg=1;
                    $modReg=2;
                }
                break;
                case "408":{
                    $condReg=21;
                    $modReg=3;
                }
                break;
                case "409":{
                    $condReg=1;
                    $modReg=4;
                }
                break;
                case "411":{
                    $condReg=14;
                    $modReg=7;
                }
                break;
                case "412":{
                    $condReg=4;
                    $modReg=2;
                }
                break;
                case "413":{
                    $condReg=5;
                    $modReg=2;
                }
                break;
                case "414":{
                    $condReg=9;
                    $modReg=8;
                }
                break;
                case "415":{
                    $condReg=26;
                    $modReg=0;
                }
                break;
                case "416":{
                    $condReg=3;
                    $modReg=7;
                }
                break;
                case "417":{
                    $condReg=1;
                    $modReg=0;
                }
                break;
                case "418":{
                    $condReg=27;
                    $modReg=0;
                }
                break;
                case "420":{
                    $condReg=13;
                    $modReg=8;
                }
                break;
                case "421":{
                    $condReg=25;
                    $modReg=0;
                }
                break;
                case "422":{
                    $condReg=12;
                    $modReg=2;
                }
                break;
                case "423":{
                    $condReg=22;
                    $modReg=13;
                }
                break;
                case "426":{
                    $condReg=10;
                    $modReg=7;
                }
                break;
                case "427":{
                    $condReg=9;
                    $modReg=0;
                }
                break;
            }

            $mode = ""; //pour les trigger

            if (!$db->num_rows($sql)>0)
            {
                //Insert commande
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."commande
                                        (date_creation,ref, fk_user_author, fk_soc,fk_cond_reglement, date_commande, fk_mode_reglement,fk_adresse_livraison,external_id)
                                 VALUES (now(),'".$val['PcvCode']."',".($internalUserId>0?$internalUserId:'NULL').",".$socid.",".$condReg.",'".date('Y-m-d',$val['PcvDate'])."',".$modReg.",".$livAdd.",".$val['PcvID'].")";
                $sql = $db->query($requete);
                if ($sql)
                {
                    $mode = "ORDER_CREATE";
                    $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation commande OK</td>";
                    $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation commande OK</td>"."\n";
                } else {
                    $webContent .=  "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                    $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation commande KO</td>"."\n";
                }
                $comId = $db->last_insert_id("".MAIN_DB_PREFIX."commande");
            } else {
                //Updatecommande
                $comId = $res->rowid;
                $sqlUpt=array();
                if ($res->fk_user_author != $internalUserId)
                    $sqlUpt[] = " fk_user_author = '".$internalUserId."'";
                if ($res->fk_soc != $socid)
                    $sqlUpt[] = " fk_soc = '".$socid."'";
                if ($res->fk_cond_reglement != $condReg)
                    $sqlUpt[] = " fk_cond_reglement = '".$condReg."'";
                if ($res->fk_mode_reglement != $modReg)
                    $sqlUpt[] = " fk_mode_reglement = '".$modReg."'";
                if ($res->fk_adresse_livraison != $livAdd)
                    $sqlUpt[] = " fk_adresse_livraison = '".$livAdd."'";
                if (count($sqlUpt) > 0)
                {
                    $updtStr = join(',',$sqlUpt);
                    $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET ".$updtStr. " WHERE rowid =".$comId;
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $mode = "ORDER_MODIFY";
                        $webContent .=  "<td  class='ui-widget-content'>Mise &agrave; jour commande OK</td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour commande OK</td>"."\n";
                    } else {
                        $webContent .=  "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour commande KO</td>"."\n";
                    }
                } else {
                    $webContent .=  "<td  class='ui-widget-content'>Pas de mise &agrave; jour commande n&eacute;c&eacute;ssaire";
                    $mailContent .=  "<td style='background-color: #FFF;'>Pas de mise &agrave; jour commande n&eacute;cessaire</td>"."\n";
                }
            }
            if ($comId> 0 && $socContact > 0)
            {
                $requete = "DELETE FROM ".MAIN_DB_PREFIX."element_contact WHERE fk_socpeople =".$socContact." AND fk_c_type_contact IN (100,101) AND element_id = ".$comId;
                $sql = $db->query($requete);
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."element_contact(fk_socpeople, fk_c_type_contact, element_id,statut, datecreate)
                                   VALUES (".$socContact.",100,".$comid.",4,now() )";
                $sql = $db->query($requete);
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."element_contact(fk_socpeople, fk_c_type_contact, element_id,statut, datecreate)
                                   VALUES (".$socContact.",101,".$comid.",4,now() )";
//print $requete;
                $sql = $db->query($requete);
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

            $totalCom_ttc = preg_replace('/,/','.',$val['PlvQteUV'] * $val['PlvPUNet'] * (1 + ( $val['TaxTaux']  / 100)));
            $totalCom_tva = preg_replace('/,/','.',$val['PlvQteUV'] * $val['PlvPUNet'] * (( $val['TaxTaux']  / 100)));

            if ($comId)
            {
                $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Ligne de commande</td>";
                $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Ligne de commande</td>"."\n";
                //Les lignes de commandes
//Prix Achat
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commandedet WHERE external_id = ".$val['PlvID'];
                $sql1 = $db->query($requete);
                $res1=$db->fetch_object($sql1);
                if ($db->num_rows($sql1) > 0)
                {
                    //Update
                    $sqlUpt=array();

                    if ($prodId && $res1->fk_product != $prodId )
                        $sqlUpt[] = " fk_product = '".$prodId."'";
                    if ($res1->pu_achat_ht != $val['PlvPA'])
                        $sqlUpt[] = " pu_achat_ht = '".($val['PlvPA']>0?$val['PlvPA']:0)."'";
                    if ($res1->description != $val['PlvLib'])
                        $sqlUpt[] = " description = '".$val['PlvLib']."'";
                    if ($res1->qty != $val['PlvQteUV'])
                        $sqlUpt[] = " qty = '".preg_replace('/,/','.',($val['PlvQteUV']>0?$val['PlvQteUV']:($val['PlvQteUV']<0?abs($val['PlvQteUV']):0)))."'";
                    if ($val['PlvQteUV'] < 0){ $val['PlvPUNet'] = - $val['PlvPUNet'];$val['PlvQteUV'] = - $val['PlvQteUV']; }
                    if ($res1->subprice != $val['PlvPUNet'])
                        $sqlUpt[] = " subprice = '".preg_replace('/,/','.',($val['PlvPUNet'])>0?$val['PlvPUNet']:0)."'";
                    if ($res1->subprice * $res->qty != floatval($val['PlvQteUV']) * floatval($val['PlvPUNet']))
                        $sqlUpt[] = " total_ht = qty * subprice ";
                    if ($res1->rang != $val['PlvNumLig'])
                        $sqlUpt[] = " rang = ".$val['PlvNumLig'];
                    if ($res1->tva_tx != $val['TaxTaux'])
                        $sqlUpt[] = " tva_tx = '".preg_replace('/,/','.',$val['TaxTaux'])."'";
                    if ($res1->total_ttc != $totalCom_ttc)
                        $sqlUpt[] = " total_ttc = '".$totalCom_ttc."'";
                    if ($res1->total_tva != $totalCom_tva)
                        $sqlUpt[] = " total_tva = '".$totalCom_tva."'";

                    $remArrayComLigne[$comId][$res1->rowid]=$res1->rowid;
                    if (count($sqlUpt) > 0)
                    {
                        $updtStr = join(',',$sqlUpt);
                        $requete = "UPDATE ".MAIN_DB_PREFIX."commandedet SET ".$updtStr. " WHERE rowid = ".$res1->rowid;
                        print $requete;
                        $sql = $db->query($requete);
                        if ($sql)
                        {
                            $webContent .=  "<td  class='ui-widget-content'>Mise &agrave; jour ligne commande OK</td>";
                            $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour ligne commande OK</td>"."\n";
                            if ($mode != 'ORDER_CREATE') $mode="ORDER_MODIFY";
                        } else {
                            $webContent .=  "<td class='KOtd error  ui-widget-content'>Mise &agrave; jour ligne commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                            $mailContent .=  "<td style='background-color: #FFF;'>Mise &agrave; jour ligne commande OK</td>"."\n";
                        }
                    }  else {
                       $webContent .=  "<td  class='ui-widget-content'>Pas de modification ligne commande</td>";
                       $mailContent .=  "<td style='background-color: #FFF;'>Pas de modification ligne commande</td>"."\n";
                    }
                } else  {
                    //Insert
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."commandedet
                                       (fk_commande,
                                        fk_product,
                                        description,
                                        qty,
                                        subprice,
                                        rang,
                                        total_ht,
                                        external_id,
                                        tva_tx,
                                        total_tva,
                                        total_ttc,
                                        pu_achat_ht,
                                        coef
                                        )
                                VALUES (".$comId.",
                                        ".($prodId>0?$prodId:"NULL").",
                                        '".$val['PlvLib']."',
                                        ".preg_replace('/,/','.',$val['PlvQteUV']>0?$val['PlvQteUV']:($val['PlvPUNet']<0?abs($val['PlvPUNet']):0)).",
                                        ". preg_replace('/,/','.',($val['PlvQteUV']>0?($val['PlvPUNet']>0?$val['PlvPUNet']:($val['PlvPUNet']<0?$val['PlvPUNet']:0)):($val['PlvQteUV']<0? -1 * ($val['PlvPUNet']>0?$val['PlvPUNet']:($val['PlvPUNet']<0?$val['PlvPUNet']:0)):0))).",
                                        ".$val['PlvNumLig'].",
                                        ".preg_replace('/,/','.',($val['PlvQteUV']!=0?floatval($val['PlvQteUV']):0) * ($val['PlvPUNet']!=0?floatval($val['PlvPUNet']):0)).",
                                        ".$val['PlvID'].",
                                        ".preg_replace('/,/','.',$val['TaxTaux']).",
                                        ".($totalCom_tva>0?$totalCom_tva:0).",
                                        ".($totalCom_ttc>0?$totalCom_ttc:0).",
                                        ".($val['PlvPA']>0?$val['PlvPA']:"NULL").",1
                                        )";


                    $sql=$db->query($requete);
                    if ($sql)
                    {
                        $remArrayComLigne[$comId][$db->last_insert_id('".MAIN_DB_PREFIX."commandedet')]=$db->last_insert_id('".MAIN_DB_PREFIX."commandedet');
                        if ($mode != 'ORDER_CREATE') $mode="ORDER_MODIFY";
                        $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation ligne commande OK</td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande OK</td>"."\n";
                    } else {
                        $webContent .=  "<td  class='KOtd error  ui-widget-content'>Cr&eacute;ation ligne commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Cr&eacute;ation ligne commande KO</td>"."\n";
                    }
                }
                /*
                +--------------------------------------------------------------------------------------------------------------+
                |                                                                                                              |
                |                                         Update total commande                                                |
                |                                                                                                              |
                +--------------------------------------------------------------------------------------------------------------+
                */

                $com = new Synopsis_Commande($db);
                $com->fetch($comId);
                $com->update_price();
                $com->valid($user);
                if ($mode ."x" != "x")
                {
                    $interface=new Interfaces($db);
                    $result=$interface->run_triggers($mode,$com,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                }


                $mailSumUpContent['commande'][]=$com;
                $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Groupe de commande</td>";
                $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Groupe de commande</td>"."\n";
    /*
        +--------------------------------------------------------------------------------------------------------------+
        |                                                                                                              |
        |                                         Les groupes des commandes                                            |
        |                                                                                                              |
        +--------------------------------------------------------------------------------------------------------------+
    */


                if ($val['AffCode'] . "x" == "x"){
                    //Verifier par rapport à la référence. => supression
                    $requete = "DELETE FROM Babel_commande_grpdet WHERE refCommande = '".$com->ref."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $webContent .=  "<td  class='ui-widget-content'>Effacement de la liaison commande - groupe OK</td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Effacement de la liaison commande - groupe OK</td>"."\n";
                    } else {
                        $webContent .=  "<td class='KOtd error ui-widget-content'>Effacement de la liaison commande - groupe KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                        $mailContent .=  "<td style='background-color: #FFF;'>Effacement de la liaison commande - groupe KO</td>"."\n";
                    }

                } else {
                    //Recupere le groupeId
                    $requete = "SELECT id FROM Babel_commande_grp WHERE nom ='".$val['AffCode']."'";
                    $sql = $db->query($requete);
                    $res = $db->fetch_object($sql);
                    $grpId = $res->id;
                    if (!$grpId > 0)
                    {
                        $requete = "INSERT INTO Babel_commande_grp (nom, datec) VALUES ('".$val['AffCode']."',now())";
                        $sql = $db->query($requete);
                        $grpId = $db->last_insert_id('Babel_commande_grp');
                        if ($sql)
                        {
                            $webContent .=  "<td  class='ui-widget-content'>Cr&eacute;ation du groupe de commande OK</td>";
                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation du groupe de commande OK</td>"."\n";
                        } else {
                            $webContent .=  "<td class='KOtd error ui-widget-content'>Cr&eacute;ation du groupe de commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                            $mailContent .= "<td style='background-color: #FFF;'>Cr&eacute;ation du groupe de commande KO</td>"."\n";
                        }

                    } else {
                        $webContent .=  "<td  class='ui-widget-content'>Pas de modification du groupe de commande</td>";
                        $mailContent .= "<td style='background-color: #FFF;'>Pas de modification du groupe de commande</td>"."\n";
                    }

                    $webContent .=  "<tr><th class='ui-state-default ui-widget-header'>Liaison Commande / groupe</td>";
                    $mailContent .= "<tr><th style='background-color:#0073EA; color: #FFF;'>Liaison Commande / groupe</td>"."\n";
                    //efface la ref
                    $requete = "DELETE FROM Babel_commande_grpdet WHERE refCommande = '".$com->ref."'";
                    $sql = $db->query($requete);
                    //ajoute la ref dans le groupe
                    $requete = "INSERT INTO Babel_commande_grpdet
                                            (commande_group_refid,refCommande,command_refid )
                                     VALUES (".$grpId.",'".$com->ref."',".$com->id.")";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $webContent .=  "<td  class='ui-widget-content'>Liaison commande - groupe OK</td>";
                        $mailContent .= "<td style='background-color: #FFF;'>Liaison commande - groupe OK</td>"."\n";
                    } else {
                        $webContent .=  "<td class='KOtd error ui-widget-content'>Liaison commande - groupe KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
                        $mailContent .= "<td style='background-color: #FFF;'>Liaison commande - groupe KO</td>"."\n";
                    }

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

$webContent .=  "<tr><th colspan=2 class='ui-state-default ui-widget-header'>Syncho nombre de ligne de commande</td>";
$mailContent .= "<tr><th colspan=2 style='background-color:#0073EA; color: #FFF;'>Syncho nombre de ligne de commande</td>"."\n";

foreach($remArrayComLigne as $comId => $arrLigne)
{
    $webContent .=  "<tr><th colspan=1 class='ui-state-default ui-widget-header'>Commande #".$comId."</td>";
    $mailContent .= "<tr><th colspan=1 style='background-color:#0073EA; color: #FFF;'>Commande #".$comId."</td>"."\n";
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande = ".$comId." AND rowid not in (".join(",",$arrLigne).")";
    $sql = $db->query($requete);
    if ($sql)
    {
        $webContent .=  "<td  class='ui-widget-content'>Synchro des lignes de commande OK</td>";
        $mailContent .= "<td style='background-color: #FFF;'>Synchro des lignes de commande OK</td>"."\n";
    } else {
        $webContent .=  "<td class='KOtd error ui-widget-content'>Synchro des lignes de commande KO<span id='debugS'>Err: ".$db->lasterrno."<br/>".$db->lastqueryerror."<br/>".$db->lasterror."</span></td>";
        $mailContent .= "<td style='background-color: #FFF;'>Synchro des lignes de commande KO</td>"."\n";
    }
}
    $webContent .=  "</table>";
    $mailContent .=  "</table>"."\n";


    $webContent .=  "<div id='debug'>Message:<div id='replace'></div></div>";
    $webContent .=  <<<EOF
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


$remCatGlob=false;
function updateCategorie($ref,$prodId,$val)
{
    if ($ref.'x'!="x" && $prodId > 0){
        global $remCatGlob,$db;

// 1 catégorie par Gamme Famille ...
//        ArtGammeEnu => string(5) Gamme               => ??
//        ArtFamilleEnu => string(7) Famille           => ??
//        ArtCollectEnu => string(10) Collection       => ??
//        ArtNatureEnu => string(6) Nature             => ??
//        ArtCategEnu => string(14) Sélection Bimp     => ??
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie WHERE type=0";
        $sql = $db->query($requete);
        $arrLocalCat = array();
        $gammeWasFound=false;
        $natureWasFound=false;
        $selectBIMPWasFound=false;
        $collecWasFound=false;
        $familleWasFound=false;
        while ($res = $db->fetch_object($sql))
        {
            //
            //  Sélection Bimp
            //
            $label = SynSanitize($res->label);
            $label1 =SynSanitize($val["ArtCategEnu"]);
            if ($label1==$label){
                $selectBIMPWasFound=$res->rowid;
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".$prodId . " AND fk_categorie = ".$res->rowid;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1)>0){
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
                    $sql1 = $db->query($requete);
                }
            }

            //
            //  Nature
            //
            $label = SynSanitize($res->label);
            $label1 =SynSanitize($val["ArtNatureEnu"]);
            if ($label1==$label){
                $natureWasFound=$res->rowid;
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".$prodId . " AND fk_categorie = ".$res->rowid;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1)>0){
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
                    $sql1 = $db->query($requete);
                }
            }

            //
            //  Collection
            //
            $label = SynSanitize($res->label);
            $label1 =SynSanitize($val["ArtCollectEnu"]);
            if ($label1==$label){
                $collecWasFound=$res->rowid;
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".$prodId . " AND fk_categorie = ".$res->rowid;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1)>0){
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
                    $sql1 = $db->query($requete);
                }
            }

            //
            //  Famille
            //
            $label = SynSanitize($res->label);
            $label1 =SynSanitize($val["ArtFamilleEnu"]);
            if ($label1==$label){
                $familleWasFound=$res->rowid;
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".$prodId . " AND fk_categorie = ".$res->rowid;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1)>0){
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
                    $sql1 = $db->query($requete);
                }
            }
            //
            //  Gamme
            //
            $label = SynSanitize($res->label);
            $label1 =SynSanitize($val["ArtGammeEnu"]);
            if ($label1==$label){
                $gammeWasFound=$res->rowid;
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".$prodId . " AND fk_categorie = ".$res->rowid;
                $sql1 = $db->query($requete);
                if ($db->num_rows($sql1)>0){
                    //?
                } else {
                    //Insert Product in catégorie
                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
                    $sql1 = $db->query($requete);
                }
            }

        }

        //
        //  Gamme
        //
        if (!$gammeWasFound && $val["ArtGammeEnu"]."x" != "x"){
            global $gammeCatId;
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie (visible,label,type,level, fk_parent) VALUES (1,'".$val["ArtGammeEnu"]."',0,3, ".$gammeCatId.")";
            $sql = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
            $sql1 = $db->query($requete);

        }
        //
        //  Famille
        //
        if (!$familleWasFound && $val["ArtFamilleEnu"]."x" != "x"){
            global $familleCatId;
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie (visible,label,type,level,fk_parent) VALUES (1,'".$val["ArtFamilleEnu"]."',0,3,".$familleCatId.")";
            $sql = $db->query($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$familleCatId.",".$newId.")";
//            $sql1 = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
            $sql1 = $db->query($requete);

        }
        //
        //  Nature
        //
        if (!$natureWasFound && $val["ArtNatureEnu"]."x" != "x"){
            global $natureCatId;
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie (visible,label,type,level,fk_parent) VALUES (1,'".$val["ArtNatureEnu"]."',0,3,".$natureCatId.")";
            $sql = $db->query($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$natureCatId.",".$newId.")";
//            $sql1 = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
            $sql1 = $db->query($requete);
        }
        //
        //  Selection BIMP
        //
        if (!$selectBIMPWasFound && $val["ArtCategEnu"]."x" != "x"){
            global $selectBIMPCatId;
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie (visible,label,type,level,fk_parent) VALUES (1,'".$val["ArtCategEnu"]."',0,3,".$selectBIMPCatId.")";
            $sql = $db->query($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$selectBIMPCatId.",".$newId.")";
//            $sql1 = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
            $sql1 = $db->query($requete);
        }

        //
        //  Collection
        //

        if (!$collecWasFound && $val["ArtCollectEnu"]."x" != "x"){
            global $collectionCatId;
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie (visible,label,type,level,fk_parent) VALUES (1,'".$val["ArtCollectEnu"]."',0,3,".$collectionCatId.")";
            $sql = $db->query($requete);
//            $newId = $db->last_insert_id(MAIN_DB_PREFIX.'categorie');
//            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association
//                                    (fk_categorie_mere_babel,fk_categorie_fille_babel)
//                             VALUES (".$collectionCatId.",".$newId.")";
//            $sql1 = $db->query($requete);
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product) VALUES (".$res->rowid.",".$prodId.")";
            $sql1 = $db->query($requete);
        }

        if (!is_array($remCatGlob)){
            $remCatGlob=array();
            $requete = "SELECT * FROM BIMP_import_product_cat ORDER BY rang";
            $sql = $db->query($requete);
            while ($res=$db->fetch_object($sql)){
                $remCatGlob[$res->id]=array('pattern'=>$res->pattern, 'categorie_refid' => $res->categorie_refid, "rang" => $res->rang);
            }
        }
        foreach($remCatGlob as $id => $arrReco)
        {
            if (preg_match('/'.$arrReco['pattern'].'/',$ref))
            {
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_product, fk_categorie) VALUES (".$prodId.",".$arrReco['categorie_refid'].")";
                $sql = $db->query($requete);
            }
        }
    }
}
$remTypeGlob=false;
function updateType($ref,$prodId)
{
    if ($ref.'x'!="x" && $prodId > 0){
        global $remTypeGlob,$db;
        if (!is_array($remTypeGlob)){
            $remTypeGlob=array();
            $requete = "SELECT * FROM BIMP_import_product_type ORDER BY rang";
            $sql = $db->query($requete);
            while ($res=$db->fetch_object($sql)){
                $remTypeGlob[$res->id]=array('pattern'=>$res->pattern, 'product_type' => $res->product_type, "rang" => $res->rang);
            }
        }
        foreach($remTypeGlob as $id => $arrReco)
        {
            if (preg_match('/'.$arrReco['pattern'].'/',$ref))
            {
                $requete = "UPDATE ".MAIN_DB_PREFIX."product SET fk_product_type = '".$arrReco['product_type']."' WHERE rowid = ".$prodId;
                $sql = $db->query($requete);
            }
        }
    }

}



/*
 +--------------------------------------------------------------------------------------------------------------+
 |                                                                                                              |
 |                                         Send Mail                                                            |
 |                                                                                                              |
 +--------------------------------------------------------------------------------------------------------------+
 */

$mailHeader = "<div><table border=0 width=700 cellpadding=20 style='border-collapse: collapse;'><tr><td><img height=100 src='".GLE_FULL_ROOT."/theme/".$conf->theme."/Logo-72ppp.png'/></div>"."\n";
$mailHeader .= "<td valign=bottom><div style='color: #0073EA; font-size: 25pt;'>Rapport d'importation</div><br/>"."\n";
$mailHeader .= "</table>"."\n";
$arrCom = array();
foreach($mailSumUpContent['commande'] as $key=>$val)
{
    $arrCom[$val->id]=$val;
}
$mailHeader .= "<table  border=0 width=700 cellpadding=10 style='border-collapse: collapse;'>"."\n";
$mailHeader .= "<tr><th style='background-color: #0073EA; color: #fff;' colspan=3>Les commandes ajout&eacute;es / modifi&eacute;es"."</td>"."\n";
foreach($arrCom as $key=>$val)
{
    $mailHeader .= "<tr><td>\n".$val->getNomUrl(1,6)."\n</td>"."\n";
    $mailHeader .= "    <td>\n".($val->societe?$val->societe->getNomUrl(1,6):'-')."\n</td>"."\n";
    $mailHeader .= "    <td aligne='right' nowrap>\n".price($val->total_ht)."&euro;\n</td>"."\n";
}
$mailHeader .= "</table>\n<table width=700 border=1 cellpadding=20 style='border-collapse: collapse;'>"."\n";
$mailHeader .= "<tr><th style='background-color: #0073EA; color: #fff;' colspan=2>Le d&eacute;tail de l'importation"."</td>";

$mailContent = $mailHeader . "<tr><td style='font-size: small;>".$mailContent."</table>\n";
$mailFooter = "<div style='font-size: small;'>G&eacute;n&eacute;r&eacute; le ".date('d/m/Y')." &agrave; ".date('H:i')."</div>"."\n";
$mailFooter .= "<hr/>\n"."\n";
$mailFooter .= "<div><table border=0 width=700 cellpadding=20 style='border-collapse: collapse;'><tr><td><img height=100 src='".GLE_FULL_ROOT."/theme/".$conf->theme."/Logo-72ppp.png'/></div>"."\n";
$mailFooter .= "<td valign=bottom><div style='font-size: small;'><b>Document strictement confidentiel</b><br>".$mysoc->nom.'<br><em>'.$mysoc->address.'<br>'.$mysoc->cp." ".$mysoc->ville.'</em><br>Tel: '.$mysoc->tel."<br>Mail: <a href='mailto:".$mysoc->email."'>".$mysoc->email."</a><br>Url: <a href='".$mysoc->url."'>".$mysoc->url."</a></div><br/>"."\n";
$mailFooter .= "</table>"."\n";
$mailContent .= $mailFooter;

if ($conf->global->BIMP_MAIL_TO ."x" == 'x' || $conf->global->BIMP_MAIL_FROM."x" == "x")
{
    $webContent .= "<div style='color: #FF000;'>La fonction mail n'est pas configur&eacute;e</div>";
} else {
    $mailFileArr = array();
    $mailFileMimeArr = array();
    $mailFileMimeNameArr = array();
    foreach($fileArray as $key=>$val)
    {
        $mailFileArr[]=$dir."/".$val;
        $mailFileMimeArr[]="text/plain";
        $mailFileMimeNameArr[]=$val;
    }

    sendMail('Rapport d\'import',$conf->global->BIMP_MAIL_TO,"GLE <".$conf->global->BIMP_MAIL_FROM.">",$mailContent,
             $mailFileArr,
             $mailFileMimeArr,
             $mailFileMimeNameArr,
             ($conf->global->BIMP_MAIL_CC."x" == "x"?"":$conf->global->BIMP_MAIL_CC),
             ($conf->global->BIMP_MAIL_BCC."x" == "x"?"":$conf->global->BIMP_MAIL_BCC),
             0,
             1,
             ($conf->global->BIMP_MAIL_CC."x" == "x"?"":$conf->global->BIMP_MAIL_FROM));

}


/*
 +--------------------------------------------------------------------------------------------------------------+
 |                                                                                                              |
 |                                         Save Historic                                                        |
 |                                                                                                              |
 +--------------------------------------------------------------------------------------------------------------+
 */

foreach($fileArray as $key=>$val)
{
    $requete = "INSERT INTO BIMP_import_history (webContent, mailContent,datec,filename)
                     VALUES ('".addslashes($webContent)."','".addslashes($mailContent)."',now(),'".$val."')";
    $sql = $db->query($requete);
}
/*
 +--------------------------------------------------------------------------------------------------------------+
 |                                                                                                              |
 |                                         Display                                                              |
 |                                                                                                              |
 +--------------------------------------------------------------------------------------------------------------+
 */


if($displayHTML) print $webContent;


/*
 +--------------------------------------------------------------------------------------------------------------+
 |                                                                                                              |
 |                                         Remove file "isrunning"                                              |
 |                                                                                                              |
 +--------------------------------------------------------------------------------------------------------------+
 */
unlink("/tmp/.importRunning");

function sendMail($subject,$to,$from,$msg,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc='',$addr_bcc='',$deliveryreceipt=0,$msgishtml=1,$errors_to='')
{
    global $mysoc;
    global $langs;
    $mail = new CMailFile($subject,$to,$from,$msg,
                          $filename_list,$mimetype_list,$mimefilename_list,
                          $addr_cc,$addr_bcc,$deliveryreceipt,$msgishtml,$errors_to);
    $res = $mail->sendfile();
    if ($res)
    {
        return (1);
    } else {
        return -1;
    }
}
function processPays($codePays)
{
    global $db;
    if($codePays."x" != "x"){
        switch($codePays){
            case "CS":{
                $codePays = "CZ";
            }
            break;
            case "BU":{
                $codePays = "BF";
            }
            break;
            case "MAD":{
                $codePays = "MG";
            }
            break;
            case "SL":{
                $codePays = "SI";
            }
            break;
            case "TC":{
                $codePays = "TD";
            }
            break;
            case "YU":{
                $codePays = "RS";
            }
            break;
            case "QQ":{
                return "";
            }
            break;
        }
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_pays WHERE code = '".$codePays."'";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        return ($res->rowid);

    } else {
        return 1; //Code pays France
    }
}
$remUserArray=array();
function processUser($external_id){
//    return 59;
    global $remUserArray,$db;
    if (!$remUserArray[$external_id]){
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE external_id = ".$external_id;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $remUserArray[$external_id]=$res->rowid;
        return($res->rowid);
    } else {
        return $remUserArray[$external_id];
    }
}
?>