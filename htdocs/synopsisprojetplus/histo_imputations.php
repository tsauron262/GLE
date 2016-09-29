<?php

/*
 */
/**
 *
 * Name : histo_imputations.php
 * GLE-2
 */
/*
 * Actuellemnt 
 * 
 * Euros Par heure Réalisé   =      Commande    /     nbHeure Réalisé Total         X     nbHeure Realisé Période Tache
 */





ini_set('max_execution_time', 40000);
ini_set("memory_limit", "1200M");


require_once('../main.inc.php');

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

$statImputations->getFormDoc();


global $logLongTime;
$logLongTime = false;
llxFooter("<em>Derni&egrave;re modification </em>");

class statImputations {

    var $tabProjInDate = array();
    var $tabTaskInDate = array();
    var $totForTauxHVendue = 0;

    function __construct($db) {
        global $user, $langs;
        $this->user = $user;
        define('_AFFICHE_LIGNE_VIDE_', true);
        define('_IMPUT_POURC_MULTI_USER_', false);
        require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
        require_once(DOL_DOCUMENT_ROOT . "/projet/class/task.class.php");

        $this->db = $db;
        $langs->load("synopsisprojetplus@synopsisprojetplus");
        $this->userId = $this->user->id;


        if (!isset($_REQUEST['action']))
            $_REQUEST['action'] = '';



        $this->messErreur = array();

        if ($this->user->rights->synopsisprojet->voirImputations && isset($_REQUEST['userid']) && ($_REQUEST['userid'] > 0 || $_REQUEST['userid'] == -2))
            $this->userId = $_REQUEST['userid'];


        $comref = sanitize_string("Imputations-" . date('Y') . '-' . $this->user->login);
        $filedir = $conf->imputations->dir_output;

        $this->curUser = new User($this->db);
        $this->curUser->fetch($this->userId);



        $this->format = 'weekly';
        if (isset($_SESSION['format']))
            $this->format = $_SESSION['format'];
        if (isset($_REQUEST['format']) && $_REQUEST['format'] . 'x' != "x") {
            $this->format = $_REQUEST['format'];
            $_SESSION['format'] = $_REQUEST['format'];
        }
        $this->date = strtotime(date('Y-m-d'));
        if (isset($_SESSION['date']))
            $this->date = $_SESSION['date'];
        if (isset($_REQUEST['date']) && $_REQUEST['date'] . 'x' != "x") {
            $this->date = $_REQUEST['date'];
            $_SESSION['date'] = $_REQUEST['date'];
        }

        $this->modVal = 1;
        if (isset($_SESSION['modVal']))
            $this->modVal = $_SESSION['modVal'];
        if (isset($_REQUEST['modVal'])) {
            $this->modVal = $_REQUEST['modVal'];
            $_SESSION['modVal'] = $_REQUEST['modVal'];
        }
//print_r($this->user->rights->synopsisprojet->caImput);die;
        if (!$this->user->rights->synopsisprojet->caImput && $this->modVal == 3)
            $this->modVal = 2;


        $this->grandType = 1;
        if (isset($_SESSION['grandType']))
            $this->grandType = $_SESSION['grandType'];
        if (isset($_REQUEST['grandType'])) {
            $this->grandType = $_REQUEST['grandType'];
            $_SESSION['grandType'] = $_REQUEST['grandType'];

//    $this->modVal = 1; //Valeur auto mais pas obligé
//    $this->format = 'weekly';
//    $this->date = strtotime(date('Y-m-d'));
        }




        $this->typeTableau = 1;
        if (isset($_SESSION['typeTableau']))
            $this->typeTableau = $_SESSION['typeTableau'];
        if (isset($_REQUEST['typeTableau'])) {
            $this->typeTableau = $_REQUEST['typeTableau'];
            $_SESSION['typeTableau'] = $_REQUEST['typeTableau'];
        }
        if ($this->typeTableau == 2) {
            $this->userId = -2;
        }



        $this->formatView = 'norm';
        if (isset($_SESSION['view']))
            $this->formatView = $_SESSION['view'];
        if (isset($_REQUEST['view'])) {
            $this->formatView = $_REQUEST['view'];
            $_SESSION['view'] = $_REQUEST['view'];
        }



        $this->typeUser = 'user';
        if (isset($_SESSION['typeUser']))
            $this->typeUser = $_SESSION['typeUser'];
        if (isset($_REQUEST['typeUser'])) {
            $this->typeUser = $_REQUEST['typeUser'];
            $_SESSION['typeUser'] = $_REQUEST['typeUser'];
        }


        if ($this->formatView == "month" && $this->format != 'annualy' && $this->format != "monthly") {
            if (isset($_REQUEST['view']))
                $this->format = 'annualy';
            else
                $this->formatView = "norm";
        }



        if ($this->grandType != 1 && $this->grandType != 4) {
            if ($this->modVal == 1)
                $this->modVal = 2;
            $this->format = "annualy";
            $this->formatView = "month";
        }


        $monthDur = 30;


        define('_AFFICHE_LIGNE_VIDE2_', (_AFFICHE_LIGNE_VIDE_ ||
                ($this->grandType == 1 && $this->userId > 0 && $this->modVal == 1) ||
                ($this->grandType == 2 && $this->modVal == 2)));

//Si format => weekly => debute un lundi, idem bi weekly
//Si format => monthly => debute le 1 du mois => doit determiner le nb de jour du mois
        if (($this->format == "weekly" || $this->format == "biweekly")) {
            if (date('w', $this->date) != 1) {
                while (date('w', $this->date) != 1) {
//        $this->date -= 3600 * 24;
                    $this->date = strtotime("-1 day", $this->date);
                }
            }
            if ($this->format == "weekly")
                $this->dateFin = strtotime("+7 day", $this->date);
            elseif ($this->format == "biweekly")
                $this->dateFin = strtotime("+14 day", $this->date);
        } else if ($this->format == 'monthly') {
            if (date('j', $this->date) != 1)
                $this->date = strtotime(date('Y', $this->date) . "-" . date('m', $this->date) . "-01");
            $this->dateFin = strtotime("+1 month", $this->date);
        } else if ($this->format == 'annualy') {
            $this->date = strtotime("+5 day", $this->date);
            $this->date = strtotime(date('Y', $this->date) . "-01-01");
            $this->dateFin = strtotime("+1 year", $this->date);
        }
        if ($this->format == 'monthly')
            $monthDur = date('t', $this->date);

        $this->fromProj = false;
        $this->projet = false;


        global $conf;
        $this->page = GETPOST("page", 'int');
        $this->limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit * 10;
        if ($this->page == -1) {
            $this->page = 0;
        }


        require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');
        $this->proj = new Project($this->db);
    }

    function toAffiche($val, $unite = true) {
        if ($val == "n/c")
            return '';
        if ($val == "-INF")
            return '';
        if (!is_numeric($val))
            return $val;


        if ($this->grandType == 1 || $this->grandType == 4) {
            if ($this->prevue <= 0 && $val != 0)
//                $val = 0;
                dol_syslog("Imputations sans temps prévue" . print_r($val, 1), 3);
            elseif ($this->modVal == 3) {
                if ($val > 0) {
                    $tot = ($this->realiser > $this->prevue) ? $this->realiser : $this->prevue;
                    $val = $val / $tot * $this->prixCaTacheUser;
                } else
                    $val = 0;
            }
            elseif ($this->modVal == 2)
                $val = $val / $this->prevue * 100;
        }
        else {
            if ($this->modVal == 3)
                $val = $this->prixCaTacheUser * $val / 100;
        }
        if ($unite)
            $val = $this->getUnite($val);
        return $val;
    }

    function getUnite($val) {
        $val = round($val * 100) / 100;
        if ($this->modVal == 3)
            return $val . " €";
        elseif ($this->modVal == 1)
            return $val . " h";
        elseif ($this->modVal == 2)
            return $val . " %";
    }

    function getHeurePrevue($fk_task = 0, $filtreUser = 0) {
        if (!is_array($fk_task))
            $fk_task = array($fk_task);
        if (count($fk_task) < 1)
            return 0;
        $requete1 = "SELECT sum(task_duration) as sumTps
                  FROM " . MAIN_DB_PREFIX . "synopsis_projet_task_timeP
                 WHERE 1 "
                . " AND fk_task IN (" . implode(",", $fk_task) . ")"
                . (($filtreUser > 0) ? " AND fk_user = " . $filtreUser : "");

        $sql1 = $this->db->query($requete1);
        $res1 = $this->db->fetch_object($sql1);

        return round(intval($res1->sumTps) / 3600); //Heure prevue pour la tache en question et pour l'utilisateur
    }

    function getHeureRealise($fk_task = -2, $filtreUser = -2, $tmpDate = null, $tmpDate2 = null) {
        if (!is_array($fk_task))
            $fk_task = array($fk_task);
//        if (count($fk_task) < 1)
//            return 0;
        $requete = "SELECT sum(task_duration / 3600) as task_duration
                     FROM " . MAIN_DB_PREFIX . "projet_task_time as e
                    WHERE 1 "
                . ((count($fk_task) > 0)? " AND fk_task IN (" . implode(",", $fk_task) . ")" : "")
                . (($filtreUser > 0) ? " AND fk_user = " . $filtreUser : "")
                . (($tmpDate) ? " AND task_date >= '" . date('Y-m-d', $tmpDate) . " 00:00:00' AND task_date < '" . date('Y-m-d H:i:s', $tmpDate2) . "'" : "");
        $sql1 = $this->db->query($requete);
        $res1 = $this->db->fetch_object($sql1);
        return ($res1->task_duration > 0 ? (round($res1->task_duration * 100) / 100) : 0);
    }

    function getAvancementDeclare($fk_task = -2, $filtreUser = -2, $tmpDate = null, $tmpDate2 = null) {
        if (!is_array($fk_task))
            $fk_task = array($fk_task);
        if (count($fk_task) < 1)
            return 0;
        $contraintedate = (($tmpDate) ? " AND date >= '" . date('Y-m-d', $tmpDate) . " 00:00:00' AND date < '" . date('Y-m-d H:i:s', $tmpDate2) . "'" : "");

        if ($filtreUser < 1 || !_IMPUT_POURC_MULTI_USER_) {
            $requete = "SELECT SUM(val) as sumAvancement
                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                 WHERE fk_task IN (" . implode(",", $fk_task) . ")"
                    . (($filtreUser > 0) ? " AND fk_user = " . $filtreUser : "")
                    . $contraintedate;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            $total = $res->sumAvancement;
        } else {//sinon il faut callculé le prorata de chaque avancement par user
            $requete = "SELECT val, fk_user
                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                 WHERE 1 "
                    . (($fk_task > 0) ? " AND fk_task = " . $fk_task : "")
                    . (($filtreUser > 0) ? " AND fk_user = " . $filtreUser : "")
                    . $contraintedate;

            $sql = $this->db->query($requete);
            $total = 0;
            while ($result = $this->db->fetch_object($sql)) {
                $this->prevueUser = $this->getHeurePrevue($fk_task, $result->fk_user);
                $this->prevueTask = $this->getHeurePrevue($fk_task);
                $total += $result->val * $this->prevueUser / $this->prevueTask;
            }
        }
        return $total;
    }

    function getHeurePrevueProjet($idProjet, $idUser = 0) {
        if (!is_array($idProjet))
            $idProjet = array($idProjet);
        if (count($idProjet) < 1)
            return 0;
        $requete = "SELECT SUM(task_duration) as duree_prevue
                      FROM " . MAIN_DB_PREFIX . "synopsis_projet_task_timeP tt,
                           " . MAIN_DB_PREFIX . "projet_task
                     WHERE  " . MAIN_DB_PREFIX . "projet_task.rowid = tt.fk_task
                       AND " . MAIN_DB_PREFIX . "projet_task.priority <> 3";
        $requete .= " AND " . MAIN_DB_PREFIX . "projet_task.fk_projet IN (" . implode(",", $idProjet) . ")";
        if ($idUser)
            $requete .= " AND fk_user = " . $idUser;

        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return $res->duree_prevue / 3600;
    }

    function getHeureRealiseProjet($idProjet, $idUser = 0) {
        if (!is_array($idProjet))
            $idProjet = array($idProjet);
        if (count($idProjet) < 1)
            return 0;
        $requete = "SELECT SUM(task_duration) as duree_conso
                      FROM " . MAIN_DB_PREFIX . "projet_task_time tt,
                           " . MAIN_DB_PREFIX . "projet_task
                     WHERE  " . MAIN_DB_PREFIX . "projet_task.rowid = tt.fk_task
                       AND " . MAIN_DB_PREFIX . "projet_task.priority <> 3";
        $requete .= " AND " . MAIN_DB_PREFIX . "projet_task.fk_projet IN (" . implode(",", $idProjet) . ")";
        if ($idUser)
            $requete .= " AND fk_user = " . $idUser;

        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return $res->duree_conso / 3600;
    }

    function ligneTableau($res) {

        $tousVide = true;
        $this->bool = !$this->bool;
        $arrTaskId[$res->tid] = $res->tid;

        $staticTask = new Task($this->db);

        $staticTask->label = $res->title;
        $staticTask->id = $res->tid;
        $staticTask->ref = $res->tid;

        $titreProjet = "";
        //Pour le tab utilisateur
        if ($this->typeTableau == 2) {
            $this->userId = $res->rowid;
            $userLigne = new User($this->db);
            $userLigne->fetch($this->userId);
            $titreProjet = "<label title='" . $userLigne->title . "'/>" . $userLigne->getNomUrl(1, '', 25) . "</label>";
            $dureeProjetPrevue = $this->getHeurePrevueProjet($this->tabProjInDate, $this->userId);
            $dureeProjetRealise = $this->getHeureRealiseProjet($this->tabProjInDate, $this->userId);
            $res->tid = $this->tabTaskInDate;

            //Recup des commandes et des prix
            $this->prixTot = 0;
            foreach ($this->tabProjInDate as $idProjet) {
                $this->proj->fetch($idProjet);
                $commandes = $this->proj->get_element_list('order', 'commande');

                foreach ($commandes as $commande) {
                    $comm = new Commande($this->db);
                    $comm->fetch($commande);
                    $this->prixTot += $comm->total_ht;
                }
//                $tempsPrevueProjet =
            }
        } else {
            if (!$this->remProjId || $this->remProjId != $res->pid) {
                $this->proj->fetch($res->pid);
                $titreProjet = "<label title='" . $this->proj->title . "'/>" . $this->proj->getNomUrl(1, '', 25) . "</label>";

                $dureeProjetPrevue = $this->getHeurePrevueProjet($res->pid);
                $dureeProjetRealise = $this->getHeureRealiseProjet($res->pid);

                $commandes = $this->proj->get_element_list('order', 'commande');

                //Recup des commandes et des prix
                $this->prixTot = 0;
                foreach ($commandes as $commande) {
                    $comm = new Commande($this->db);
                    $comm->fetch($commande);
                    $this->prixTot += $comm->total_ht;
                }
            }
        }



//Recup des temps prévue
        $this->prevue = $this->getHeurePrevue($res->tid, $this->userId); //Heure prevue pour la tache en question et pour l'utilisateur
        $this->realiser = $this->getHeureRealise($res->tid, $this->userId); //Heure réalisé pour la tache en question et pour l'utilisateur


        $pourcRealisationProjet = ($dureeProjetRealise > $dureeProjetPrevue) ? 1 : ($dureeProjetRealise / $dureeProjetPrevue);
        $pourcRealisationTacheUser = ($this->realiser > $this->prevue) ? 1 : ($this->realiser / $this->prevue);

        $pourcCaTacheUser = ($this->prevue > $dureeProjetPrevue) ? 1 : ($this->prevue / $dureeProjetPrevue);

        $this->prixCaTacheUser = $pourcCaTacheUser * $this->prixTot;

//        global $this->prevue, $this->realiser, $dureeProjetPrevue, $dureeProjetRealise, $this->prixTot, $pourcRealisationProjet, $pourcRealisationTacheUser, $this->prixCaTacheUser;

        $restant = 0;
        if ($this->grandType == 1) {
            $totalLigne = $this->realiser;
            $restant = $this->prevue - $this->realiser;
        } elseif ($this->grandType == 3) {
            $pourcHeure = $this->getAvancementDeclare($res->tid, $this->userId);
            $pourcAvenc = $this->realiser / $this->prevue * 100;
            $totalLigne = $pourcHeure - $pourcAvenc;
            $restant = "n/c";
        } elseif ($this->grandType == 4) {
                $totalLigne = $this->realiser;
                if ($totalLigne > 0) {
                    $avcdecl = $this->getAvancementDeclare($res->tid, $this->userId) / 100;
                    if ($avcdecl > 0) {
//                        $restant = $this->prevue - $this->realiser / $avcdecl;
                        $restant = $this->realiser - $avcdecl * $this->prevue;
                        if ($restant == 0)
                            $restant = "Equilibre";
                    }
                    else {
                        $restant = "Pas d'avancement déclaré";
                        $totalLigne = "";
                    }
                } else
                    $restant = "Pas d'imputations";

                if(stripos($this->proj->ref, "6") === 0)
                $this->totForTauxHVendue += $this->realiser - $restant;
        } else {
            $totalLigne = $this->getAvancementDeclare($res->tid, $this->userId);
            $restant = 100 - $totalLigne;
        }



//        $hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY;
//    $totalLignePerDay = round(intval($res2->sumTps) / (36 * $hourPerDay)) / 100;

        $restant = $this->toAffiche($restant);
        $totalLigne = $this->toAffiche($totalLigne);






        $html = '';
//Affichage titre 
        $html .= '<tr class="' . $arrPairImpair[$this->bool] . '">';
        $html .= '  <td class="nowrap" colspan="1">';
        $html .= $titreProjet;
        $html .= '  <td class="nowrap" colspan="1">';
        if ($staticTask->id > 0)
            $html .= $staticTask->getNomUrl(1, 'withproject', 'task', 1);
        $html .= '     </td>';

//Restant
        $html .= '     <td nowrap class="display_value' . (($restant < 0) ? ' error' : '') . '">' . $restant . '</td>';
//Total h
        $html .= '     <td nowrap class="display_value">' . $totalLigne . '</td>';


        if ($this->grandType != 4) {
            $tmpDate = $this->date;
            $html2 = $html3 = '';
            $totalPeriode = 0;
            for ($i = 0; $i < $this->arrNbJour[$this->format]; $i++) {
                if ($this->formatView == "month") {
                    if (date('m', $tmpDate) < 12)
                        $tmpDate2 = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . date('-d', $tmpDate) . ' 00:00:00');
                    else
                        $tmpDate2 = strtotime((date('Y', $tmpDate) + 1) . '-01' . date('-d', $tmpDate) . ' 00:00:00');
                } else
                    $tmpDate2 = strtotime(date('Y-m-d', $tmpDate) . ' 23:59:59');
                if ($this->grandType == 1)
                    $nbHeure = $this->getHeureRealise($res->tid, $this->userId, $tmpDate, $tmpDate2);
                elseif ($this->grandType == 3) {
                    $pourcDeclarer = $this->getAvancementDeclare($res->tid, $this->userId, $tmpDate, $tmpDate2);
                    $pourcAvenc = $this->getHeureRealise($res->tid, $this->userId, $tmpDate, $tmpDate2) / ($this->prevue > $this->realiser ? $this->prevue : $this->realiser) * 100;
                    if ($pourcAvenc > 0 || $pourcDeclarer > 0) {
                        if ($this->modVal != 3)
                            $tousVide = false;
                        $nbHeure = $pourcDeclarer - $pourcAvenc;
                    } else
                        $nbHeure = "n/c";
                } else
                    $nbHeure = $this->getAvancementDeclare($res->tid, $this->userId, $tmpDate, $tmpDate2);



                $totalPeriode += $nbHeure;
//        $nbHeure = $this->toAffiche($nbHeure);
                $this->totalDay2[$tmpDate] = $this->toAffiche($nbHeure);
                $html2 .= '     <td class="day_' . date('w', $tmpDate) . '" style="text-align:center;overflow:auto;">';
                $html3 .= '     <td class="day_' . date('w', $tmpDate) . '" style="text-align:center;overflow:auto;">';
                $html2 .= '             <input type="hidden" name="activity_hidden[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" ' . (($this->grandType == 1) ? 'maxlength="1"' : '') . '/>';
                $html2 .= '             <input type="text" name="activity[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" ' . (($this->grandType == 1) ? 'maxlength="1"' : '') . '/>';
                $html3 .= $this->toAffiche($nbHeure);
                $html2 .= '     </td>';
                $html3 .= '     </td>';
                if ($this->formatView == "month")
                    $tmpDate = $tmpDate2;
                else
//            $tmpDate += 3600 * 24;
                    $tmpDate = strtotime("+1 day", $tmpDate);
                if ($nbHeure != 0 && $this->toAffiche($nbHeure) != 0)
                    $tousVide = false;
            }

            $html2 .= '</tr>';
            $html3 .= '</tr>';

            foreach ($this->totalDay2 as $cle => $val)
                $this->totalDay[$cle] += $this->totalDay2[$cle];
            $stat = $res->fk_statut;

//Total periode
            $html .= '     <td nowrap class="display_value">' . $this->toAffiche($totalPeriode) . '</td>';
        }

        $affiche = true;
        $ecriture = false;

        if ($this->proj->statut == 1) {
            if ($this->grandType == 1 && $this->modVal == 1 && $this->formatView == 'norm' && $this->userId > 0)
                $ecriture = true;
            elseif ($this->grandType == 2 && $this->modVal == 2 && ($this->userId > 0 || !_IMPUT_POURC_MULTI_USER_))
                $ecriture = true;
        }

        if ($ecriture) {
            $html .= $html2;
            $sauvegarde = true;
        } elseif (!$tousVide || _AFFICHE_LIGNE_VIDE_)
            $html .= $html3;
        else
            $affiche = false;

        if ($affiche) {
            echo $html;
            $this->remProjId = $res->pid;

            $this->grandTotalRestant += $restant;
            $this->grandTotalLigne += $totalLigne;
            $this->grandTotalLigne2 += $this->toAffiche($totalPeriode);
//            echo $this->grandTotalLigne."|".$userLigne->id."|||<br/>";
        }
    }

    function proccAction() {
        global $langs;
        if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0) {
            require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
            $this->projet = new Project($this->db);
            $this->projet->fetch($_REQUEST['id']);
            $this->fromProj = true;
        }

        if ($_REQUEST['action'] == 'builddoc') {    // In get or post
            require_once(DOL_DOCUMENT_ROOT . "/core/modules/imputation/modules_imputations.php");
            $outputlangs = '';
            if ($_REQUEST['lang_id']) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($_REQUEST['lang_id']);
            }

            $result = imputations_pdf_create($this->db, $_REQUEST['id'], $_REQUEST['model'], $outputlangs);
            if ($result <= 0) {
                dol_print_error($this->db, $result);
                exit;
            } else {
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('ECM_GENIMPUTATIONS', false, $this->user, $langs, $conf);
                if ($result < 0) {
                    $error++;
//    		$this->errors = $interface->errors ;
                }
// Fin appel triggers
                Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST['id'] . '#builddoc');
                exit;
            }
        } else if ($_REQUEST['action'] == 'remove_file') {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

            $langs->load("other");
            $file = $filedir . '/' . GETPOST('file');
            dol_delete_file($file);
            $mesg = '<div class="ok">' . $langs->trans("FileWasRemoved", GETPOST('file')) . '</div>';
        }

        if ($_REQUEST['action'] == 'save') {
            $arrModTask = array();
            if (/* $this->userId > 0 && */isset($_REQUEST['activity_hidden'])) {
                foreach ($_REQUEST['activity_hidden'] as $key => $val) {
                    $arrModTask[$key] = $key;
                    foreach ($val as $key1 => $val1) {
                        $newVal = $_REQUEST['activity'][$key][$key1];
                        if ($newVal != $val1) {
                            if ($this->grandType == 1) {
                                $requete2 = "SELECT sum(task_duration) as sommeheure
                                   FROM " . MAIN_DB_PREFIX . "projet_task_time
                                 WHERE task_date = '" . date('Y-m-d H:i:s', $key1) . "'
                                   AND fk_user = " . $this->userId;
//AND fk_task = " . $key ;                     
                                $sql2 = $this->db->query($requete2);
                                $res2 = $this->db->fetch_object($sql2);

                                $requete3 = "SELECT SUM(task_duration) as task_duration
                                   FROM " . MAIN_DB_PREFIX . "projet_task_time
                                 WHERE task_date = '" . date('Y-m-d H:i:s', $key1) . "'
                                   AND fk_user = " . $this->userId . "
                                   AND fk_task = " . $key;
                                $sql3 = $this->db->query($requete3);
                                $res3 = $this->db->fetch_object($sql3);
                                $existant = false;
                                if ($res3)
                                    $existant = true;
                                $somh = $res2->sommeheure;
                                if ($existant)
                                    $somh = $somh - $res3->task_duration;
                                if ($newVal < 9 && (($somh / 3600) + $newVal) <= 8) {//verif que on respecte le max d'heure par jour et par tache
                                    if ($existant) {
//                                $requete = "UPDATE " . MAIN_DB_PREFIX . "projet_task_time
//                                       SET task_duration = " . intval($newVal * 3600) . "
//                                     WHERE rowid = " . $res3->rowid;
                                        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task_time
                                 WHERE task_date = '" . date('Y-m-d H:i:s', $key1) . "'
                                   AND fk_user = " . $this->userId . "
                                   AND fk_task = " . $key;
                                        $sql1 = $this->db->query($requete);
                                    } //else {
                                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "projet_task_time (task_duration, task_date, fk_task, fk_user)
                                         VALUES (" . intval($newVal * 3600) . ",'" . date('Y-m-d H:i:s', $key1) . "'," . $key . "," . $this->userId . ")";
                                    $sql1 = $this->db->query($requete);
//                            }
                                } else
                                    $this->messErreur[] = "Plus de 8 h pour la journée " . date('Y-m-d H:i:s', $key1);
                            } elseif ($this->grandType == 2) {
//                    echo "<pre>";print_r($_REQUEST);die;
                                $requete2 = "SELECT sum(val) as sommeheure, rowid
                                   FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                                 WHERE date = '" . date('Y-m-d H:i:s', $key1) . "'
                                     AND fk_task = " . $key . "
		" . (($this->userId != -2 && _IMPUT_POURC_MULTI_USER_) ? " AND fk_user = $this->userId " : "");
//AND fk_task = " . $key ;                     
                                $sql2 = $this->db->query($requete2);
                                $res2 = $this->db->fetch_object($sql2);
                                $existant = false;
                                $totPourc = $this->getAvancementDeclare($key, $this->userId);
                                if (isset($res2->sommeheure)) {
                                    $existant = true;
                                    $totPourc -= $res2->sommeheure;
                                }
                                if (($totPourc + $newVal) <= 100) {
                                    if ($existant) {
                                        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                                       SET val = " . intval($newVal) . "
                                     WHERE rowid = " . $res2->rowid;
                                        $sql1 = $this->db->query($requete);
                                    } else {
                                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ (val, date, fk_task, fk_user)
                                         VALUES (" . intval($newVal) . ",'" . date('Y-m-d H:i:s', $key1) . "'," . $key . "," . $this->userId . ")";
                                        $sql1 = $this->db->query($requete);
                                    }
                                } else
                                    $this->messErreur[] = "Plus de 100% pour la tache " . $res2->rowid;
                            }
                        }
                    }
                }
            }
            if ($this->grandType == 1) {
                foreach ($arrModTask as $taskId) {
                    $requete = "SELECT sum(task_duration) as durEff FROM " . MAIN_DB_PREFIX . "projet_task_time WHERE fk_task = " . $taskId;
                    $sql = $this->db->query($requete);
                    $res = $this->db->fetch_object($sql);
                    $tot = $res->durEff;
                    if ($tot <= 0)
                        $tot = "0";
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "projet_task SET duration_effective = " . $tot . " WHERE rowid = " . $taskId;
                    $sql = $this->db->query($requete);
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "projet_task SET progress = 100-((planned_workload - duration_effective) *100)/planned_workload WHERE rowid = " . $taskId;
                    $sql = $this->db->query($requete);
                }
            }
//    header('location: ?' . ($this->fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $this->userId);
        }
    }

    function getMenu() {
        $this->debutPara .= 'userid=' . $this->userId . '&';

        print_barre_liste("Imputations projet" . ($this->fromProj ? " : " . $this->projet->getNomUrl(1, '', 1) : "s"), $this->page, $_SERVER["PHP_SELF"], "&" . $this->debutPara);

        print '<table class="menu"><tr>';



        print '<td><b>Type d\'imputation :</b></td>';
        print '<td class="paddingRight">';
        print (($this->grandType != 1) ? '<a href="?' . $this->debutPara . 'grandType=1">' : '') . 'Par heures (Réalisé)' . (($this->grandType != 1) ? '</a>' : '') . '</br>';
        print (($this->grandType != 2) ? '<a href="?' . $this->debutPara . 'grandType=2">' : '') . 'Par avancements (Déclaré)' . (($this->grandType != 2) ? '</a>' : '') . '</br>';
//        print (($this->grandType != 3) ? '<a href="?' . $this->debutPara . 'grandType=3">' : '') . 'Ratio (Av Déclaré - Av Réalisé)' . (($this->grandType != 3) ? '</a>' : '') . '</br>';
        print (($this->grandType != 4) ? '<a href="?' . $this->debutPara . 'grandType=4">' : '') . 'Dérive estimée' . (($this->grandType != 4) ? '</a>' : '') . '</br>';
        print '</td>';


        print '<td><b>Type de tableau :</b></td>';
        print '<td class="paddingRight">';
        print (($this->typeTableau != 1) ? '<a href="?' . $this->debutPara . 'typeTableau=1">' : '') . 'Par tâches' . (($this->typeTableau != 1) ? '</a>' : '') . '</br>';
        print (($this->typeTableau != 2) ? '<a href="?' . $this->debutPara . 'typeTableau=2">' : '') . 'Par utilisateurs' . (($this->typeTableau != 2) ? '</a>' : '') . '</br>';
        print '</td>';



        print '<td><b>Type valeur d\'affichage :</b></td>';
        print '<td class="paddingRight">';
        if ($this->grandType == 1 || $this->grandType == 4)
            print (($this->modVal != 1) ? '<a href="?' . $this->debutPara . 'modVal=1">' : '') . 'Heures' . (($this->modVal != 1) ? '</a>' : '') . '</br>';
        print (($this->modVal != 2) ? '<a href="?' . $this->debutPara . 'modVal=2">' : '') . 'Pourcentages' . (($this->modVal != 2) ? '</a>' : '') . '</br>';
        if ($this->user->rights->synopsisprojet->caImput && $this->grandType != 4)
            print (($this->modVal != 3) ? '<a href="?' . $this->debutPara . 'modVal=3">' : '') . 'Euros' . (($this->modVal != 3) ? '</a>' : '') . '</br>';
        print '</td>';




        if ($this->userId > 0) {
            print '<td><b>Type intervenant :</b></td>';
            print '<td class="paddingRight">';
            print (($this->typeUser != 'user') ? '<a href="?' . $this->debutPara . 'typeUser=user">' : '') . 'Contributeur' . (($this->typeUser != 'user') ? '</a>' : '') . '</br>';
            print (($this->typeUser == 'user') ? '<a href="?' . $this->debutPara . 'typeUser=responsable">' : '') . 'Responsable' . (($this->typeUser == 'user') ? '</a>' : '') . '</br>';
            print '</td>';
        }

        if ($this->user->rights->synopsisprojet->voirImputations && $this->typeTableau == 1) {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
            $html = new Form($this->db);
            print '<td><b>Utilisateur(s) : </b></td>';
            print "<td colspan='3'><form action='?" . $this->debutPara . "' method=POST>";
            print "<table><tr><td>";
            $html->select_users($this->userId, 'userid', 1, '', 0);
            print "<td><button class='butAction'>OK</button>";
            print "<td><button class='butAction tousUser'>Tous</button>";
            print "</table>";
            print "</form>";
        }

        print '</tr><tr>';



        if ($this->grandType == 1 || $this->grandType == 4) {
            print '<td><b>Periode d\'affichage :</b></td>';
            print '<td class="paddingRight">';
            print (($this->format != 'annualy') ? '<a href="?' . $this->debutPara . 'format=annualy&amp;date=' . $this->date . '">' : '') . 'Annuel' . (($this->format != 'annualy') ? '</a>' : '') . '</br>';
            print (($this->format != 'monthly') ? '<a href="?' . $this->debutPara . 'format=monthly&amp;date=' . $this->date . '">' : '') . 'Mensuel' . (($this->format != 'monthly') ? '</a>' : '') . '</br>';
            print (($this->format != 'biweekly') ? '<a href="?' . $this->debutPara . 'format=biweekly&amp;date=' . $this->date . '">' : '') . 'Bihebdomadaire' . (($this->format != 'biweekly') ? '</a>' : '') . '</br>';
            print (($this->format != 'weekly') ? '<a href="?' . $this->debutPara . 'format=weekly&amp;date=' . $this->date . '">' : '') . 'Hebdomadaire' . (($this->format != 'weekly') ? '</a>' : '') . '</br>';
            print '</td>';




            print '<td><b>Type d\'affichage :</b></td>';
            print '<td class="paddingRight">';
            print (($this->formatView != 'month') ? '<a href="?' . $this->debutPara . 'view=month">' : '') . 'Par mois' . (($this->formatView != 'month') ? '</a>' : '') . '</br>';
            print (($this->formatView != 'norm') ? '<a href="?' . $this->debutPara . 'view=norm">' : '') . 'Par jour' . (($this->formatView != 'norm') ? '</a>' : '') . '</br>';
        }
        print '</td>';


        print '</td></tr></table>';


        print '<form method="post" action="?' . $this->debutPara . '&action=save&format=' . $this->format . '">';
        print '<input type="hidden" name="userid" value="' . $this->userId . '"></input>';
        print '    <div style="width:100%;" class="noScroll">';
        print '    <table class="calendar" width=100%>';
        if ($this->user->id == $this->userId)
            print '     <caption class="ui-state-default ui-widget-header">Mes imputations</caption>';
        elseif ($this->userId == -2)
            print '     <caption class="ui-state-default ui-widget-header">Toutes les imputations</caption>';
        else
            print '     <caption class="ui-state-default ui-widget-header">Les imputations de ' . $this->curUser->getNomUrl(1) . '</caption>';
        print '       <thead>';
        print '         <tr>';
        print '           <th class="ui-state-hover ui-widget-header navigation" colspan="2">';
        print '                 &nbsp;';
    }

    function getTabHead() {

        $arrTitleNav = array('nextweekly' => "Semaine suivante", 'nextbiweekly' => "Semaine suivante", 'nextmonthly' => "Mois suivant",
            'prevweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevbiweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevmonthly' => "Mois pr&eacute;c&eacute;dent",);

        $prevDate = strtotime("-1 week", $this->date);
        $nextDate = strtotime("+1 week", $this->date);
        $nowDate = strtotime(date('Y-m-d'));
//die(date('Y-m', strtotime("+4 day", $this->date))."-01 00:00:00");
        $miSemaine = strtotime("+4 day", $this->date);
        if ($this->format == "monthly") {
            $prevDate = strtotime("-1 month", $this->date);
            $nextDate = strtotime("+1 month", $this->date);
        }
        if ($this->format == "annualy") {
            $prevDate = strtotime(date('Y', $this->date) - 1 . "-01-01");
            $nextDate = strtotime(date('Y', $this->date) + 1 . "-01-01");
        }
        print '                 <a href="?' . $this->debutPara . 'date=' . $prevDate . '">';
        print '                     <span class="ui-icon ui-icon-arrowthickstop-1-w" title="' . $arrTitleNav['prev' . $this->format] . '" style="float:left"></span>';
        print '                 </a>';
        print '                 <a class="today" href="?' . $this->debutPara . 'date=' . $nowDate . '">';
        print '                     <span class="ui-icon ui-icon-arrowthickstop-1-s" title="Aujourd\'hui" style="float:left"></span>';
        print '                 </a>';
        print '                 <a href="?' . $this->debutPara . 'date=' . $nextDate . '">';
        print '                     <span class="ui-icon ui-icon-arrowthickstop-1-e" title="' . $arrTitleNav['next' . $this->format] . '" style="float:left"></span>';
        print '                 </a>';
        $arrMonthFR = array('1' => 'Jan', "2" => "Fev", "3" => "Mar", "4" => "Avr", "5" => "Mai", "6" => "Jun", "7" => "Jui", "8" => "Aou", "9" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
        if ($this->format == 'weekly') {
            print '                 Activit&eacute;s de la semaine ' . intval(date('W', $miSemaine)) . " (" . $arrMonthFR[date('n', $miSemaine)] . ") ";
        } else if ($this->format == 'biweekly') {
            print '                 Activit&eacute;s des semaines ' . intval(date('W', $miSemaine)) . ' / ' . intval(date('W', $miSemaine) + 1) . " (" . $arrMonthFR[date('n', $miSemaine)] . ") ";
        } else if ($this->format == 'monthly') {
            print '                 Activit&eacute;s du mois de ' . $arrMonthFR[date('n', $this->date)] . " - ";
        }
        print date('Y', $miSemaine) . '</th>             <th class="ui-state-hover ui-widget-header" colspan="1"></th>';
        print '             <th class="ui-state-hover ui-widget-header" colspan="2">Total</th>';


        $arrNbMonth = array('monthly' => 1, "annualy" => 12);
        $this->totalDay = array();

        $tmpDate = $this->date;

        if ($this->grandType != 4) {
            if ($this->formatView == "month") {
                $this->arrNbJour = array('monthly' => 1, "annualy" => 12);
                for ($i = 0; $i < $arrNbMonth[$this->format]; $i++) {
                    print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('m', $tmpDate) . '</th>';
                    $tmpDate = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . "-01");
                    $this->totalDay[$tmpDate] = 0;
                }
            } else {
                $this->arrNbJour = array('monthly' => $monthDur, 'weekly' => 7, "biweekly" => 14, "annualy" => 365);
                for ($i = 0; $i < $this->arrNbJour[$this->format]; $i++) {
                    if ($this->format != 'annualy')
                        print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('d', $tmpDate) . '</th>';
                    else
                        print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('d/m', $tmpDate) . '</th>';
//        $tmpDate += 3600 * 24;
                    $tmpDate = strtotime("+1 day", $tmpDate);
//        if(date('d/m', $tmpDate) == "04/10")
//                $tmpDate += 3600;
                    $this->totalDay[$tmpDate] = 0;
                }
            }
        }
        print "</tr>";
        print '<tr>';
        print '  <th class="ui-widget-header" style="width:270px;">';
        print '  </th>';
        print '  <th class="ui-widget-header">&nbsp;&nbsp;</th>';
        if ($this->grandType == 4)
            print '             <th class="ui-widget-header" title="Restant">Dérive</th>';
        else
            print '             <th class="ui-widget-header" title="Restant">Res&nbsp;</th>';
        print '             <th class="ui-widget-header">Global</th>';

        if ($this->grandType != 4) {
            print '             <th class="ui-widget-header">Période</th>';
            $tmpDate = $this->date;
            if ($this->formatView == "month") {
                $arrJourFR = array(1 => "Janv", 2 => "Fev", 3 => "Mars", 4 => "Avril", 5 => "Mai", 6 => "Juin", 7 => "Juillet", 8 => "Aout", 9 => "Sept", 10 => "Oct", 11 => "Nov", 12 => "Dec");
                for ($i = 0; $i < $this->arrNbJour[$this->format]; $i++) {
                    print '<th class="ui-widget-header day_' . date('w', $tmpDate) . '">' . $arrJourFR[round(date('m', $tmpDate))] . '</th>';
                    $tmpDate = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . "-01");
                }
            } else {
                $arrJourFR = array(0 => "Dim", 1 => "Lun", 2 => "Mar", 3 => "Mer", 4 => "Jeu", 5 => "Ven", 6 => "Sam");
                for ($i = 0; $i < $this->arrNbJour[$this->format]; $i++) {
                    print '<th class="ui-widget-header day_' . date('w', $tmpDate) . '">' . $arrJourFR[date('w', $tmpDate)] . '</th>';
//        $tmpDate += 3600 * 24;
                    $tmpDate = strtotime("+1 day", $tmpDate);
                }
            }
        }

        print "</tr>";
        print "</thead>";
    }

    function getTabBody() {
        print '<tbody class="div_scrollable_medium yesScroll">';
//trouve tous les projet de l'utilisateur ou il a un role

        if ($this->userId != -2 && $this->typeUser != "user" && !_IMPUT_POURC_MULTI_USER_)
            $contraiteUser = " AND p.fk_user_resp = $this->userId ";
        elseif ($this->userId != -2)
            $contraiteUser = " AND a.fk_c_type_contact IN (181,180) AND a.fk_socpeople = $this->userId ";
        else
            $contraiteUser = '';
        $requete = "SELECT DISTINCT t.rowid as tid,
                  p.rowid as pid,
                  p.ref as pref,
                  t.label as title,
                  t.fk_statut as statut,
                  p.fk_statut
             FROM " . MAIN_DB_PREFIX . "element_contact AS a,
                  " . MAIN_DB_PREFIX . "Synopsis_projet_view AS p,
                  " . MAIN_DB_PREFIX . "projet_task AS t
            WHERE p.rowid = t.fk_projet
              AND t.rowid = a.element_id
		" . $contraiteUser . "
                    " . ($this->fromProj ? (" AND p.rowid = " . $this->projet->id) : "") .
                " AND (p.date_close >= '" . date('Y-m-d', $this->date) . "' || p.fk_statut < 2)  " .
                " AND (p.dateo < '" . date('Y-m-d', $this->dateFin) . "')  " .
                (_AFFICHE_LIGNE_VIDE2_ ? "" : " AND t.rowid IN (SELECT fk_task  FROM `llx_projet_task_time` WHERE `task_date` >= '" . date('Y-m-d', $this->date) . "'" .
                        " AND task_date < '" . date('Y-m-d', $this->dateFin) . "'" .
                        ")") .
                "ORDER BY p.rowid";
        if ($this->typeTableau == 2) {
            $sql500 = $this->db->query($requete);

            while ($res500 = $this->db->fetch_object($sql500)) {
                $this->tabProjInDate[$res500->pid] = $res500->pid;
                $this->tabTaskInDate[$res500->tid] = $res500->tid;
            }
            $requete = "SELECT * FROM `" . MAIN_DB_PREFIX . "user` WHERE 1";
        }



        $offset = $this->limit * $this->page;
        $requete.= $this->db->plimit($this->limit + 1, $offset);
//echo $requete;

        $sql = $this->db->query($requete);
        $nbLigne = $this->db->num_rows($sql);
        $this->remProjId = false;
        $this->bool = true;
        $arrPairImpair[false] = "pair";
        $arrPairImpair[true] = "impaire";
        $arrTaskId = array();
        $this->grandTotalRestant = 0;
        $this->grandTotalLigne = 0;
        $this->grandTotalLigne2 = 0;
        $sauvegarde = false;

        while ($res = $this->db->fetch_object($sql)) {
            if ($this->typeTableau != 2) {
                $this->tabProjInDate[$res->pid] = $res->pid;
                $this->tabTaskInDate[$res->tid] = $res->tid;
            }
            $this->ligneTableau($res);
        }

        print '    </tbody>';
    }

    function getTabFoot() {

        print "<tfoot>";
        if ($this->modVal != 2) {
            print '         <tr>';
            print '             <th class="ui-state-default ui-widget-header" colspan=2 align=right>Total&nbsp;';

//  $hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY;
//  $this->grandTotalLignePerDay = round($this->grandTotalLigne * 100 / $hourPerDay) / 100;
//  $this->grandTotalLigne = round($this->grandTotalLigne * 100) / 100;
//Total restant
            print '             <th class="ui-state-default ui-widget-header">' . $this->getUnite($this->grandTotalRestant) . '</th>';
//Total h
            print '             <th class="ui-state-default ui-widget-header">' . $this->getUnite($this->grandTotalLigne) . '</th>';
//Total periode
            print '             <th class="ui-state-default ui-widget-header">' . $this->getUnite($this->grandTotalLigne2) . '</th>';

            ksort($this->totalDay);
            $i = 0;
            foreach ($this->totalDay as $tmpDate => $val) {
                if ($tmpDate >= $this->date && $i < $this->arrNbJour[$this->format]) {
                    $i++;
                    if (!$val > 0) {
                        $val = 0;
                    }
                    print '<th class="ui-state-default ui-widget-header day_' . date('w', $tmpDate) . '">' . $this->getUnite($val) . '</th>';
                }
            }


            print "</tr>";
        }
//dol_syslog(join(',', $arrTaskId), 3);
        if ($this->modVal == 1 && count($arrTaskId) > 0 && _AFFICHE_LIGNE_VIDE_ && 0) {//Faussé par le projer fermé en cours de période
            $colspan = $this->arrNbJour[$this->format] - 5; // -5 -5 + 5
//Total Mois
            $requete = "SELECT sum(task_duration) / 3600 as durEff
  FROM " . MAIN_DB_PREFIX . "projet_task_time
  WHERE month(task_date) = " . date('m', $this->date) . "
  AND year(task_date) = " . date('Y', $this->date) . "
  AND fk_task in (" . join(',', $arrTaskId) . ")
		" . (($this->userId != -2) ? " AND fk_user = $this->userId " : "");

            $sql = $this->db->query($requete);

            if ($sql) {
                $res = $this->db->fetch_object($sql);
                print "<tr><td style='padding:10px;' colspan=" . $colspan . "</td>";
                print "    <th style='padding:10px;' align='right' class='ui-widget-header ui-state-default' colspan='" . ($colspan > 1 ? '5' : '3') . "'>Total mensuel&nbsp;</td>";
                print "    <td align=center style='padding:10px;' class='ui-widget-content' colspan='" . ($colspan > 1 ? '5' : '2') . "'>" . $this->getUnite($res->durEff) . "</td>";
                print "</tr>";
            }

//Total Annee
            $requete = "SELECT sum(task_duration) / 3600 as durEff
  FROM " . MAIN_DB_PREFIX . "projet_task_time
  WHERE year(task_date) = " . date('Y', $this->date) . "
  AND fk_task in (" . join(',', $arrTaskId) . ")
		" . (($this->userId != -2) ? " AND fk_user = $this->userId " : "");

            $sql = $this->db->query($requete);

            if ($sql) {
                $res = $this->db->fetch_object($sql);
                print "<tr><td style='padding:10px;' colspan=" . $colspan . "</td>";
                print "    <th style='padding:10px;' align='right' class='ui-widget-header ui-state-default' colspan='" . ($colspan > 1 ? '5' : '3') . "'>Total annuel&nbsp;</td>";
                print "    <td align=center style='padding:10px;' class='ui-widget-content' colspan='" . ($colspan > 1 ? '5' : '2') . "'>" . $this->getUnite($res->durEff) . "</td>";
                print "</tr>";
            }
        }
        print "<tr><td colspan='2'><td>Taux heure vendue : <td>".$this->totForTauxHVendue / $this->getHeureRealise($this->tabTaskInDate, $this->userId)."</td></tr>";
        
        
        print "</tfoot>";


        print '  </table>';

        if ($sauvegarde) {
            print "<div class='tabsAction'>";
            print "<button class='butAction'>Sauvegarder</button>";
            print "</div>";
        }
        print "</form>";

        print '<table width="500"><tr><td width="50%" valign="top">';
        print '<a name="builddoc"></a>'; // ancre
    }

    function getFormDoc() {

        /*
         * Documents generes
         *
         */
        $urlsource = $_SERVER["PHP_SELF"] . (isset($_REQUEST['id']) ? '?id=' . $_REQUEST['id'] : '');
        $genallowed = $this->user->rights->projet->creer;
        $delallowed = $this->user->rights->projet->supprimer;

        $modelpdf = "";

        $conf->global->IMPUTATIONS_ADDON_PDF = "caracal";
        $formfile = new FormFile($this->db);
        include_once(DOL_DOCUMENT_ROOT . '/core/modules/imputation/modules_imputations.php');
        $somethingshown = @$formfile->show_documents('imputations', $comref, $filedir . "/" . $comref, $urlsource, $genallowed, $delallowed, $modelpdf);

//    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$display=true)


        print "</table>";
    }

}

?>
