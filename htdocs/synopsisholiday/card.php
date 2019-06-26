<?php

/* Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2013 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, orwrite
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/synopsisholiday/card.php
 * 		\ingroup    holiday
 * 		\brief      Form and file creation of paid holiday.
 */
if (!isset($user))
    require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisholiday/core/lib/synopsisholiday.lib.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisholiday/common.inc.php';

$myparam = GETPOST("myparam");
$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');

$isGroup = GETPOST('usersGroup');
if (isset($isGroup) && $isGroup) {
    $userid = GETPOST('groupUsers');
    if (!isset($userid) || empty($userid))
        $userid = array();
} else {
    $userid = GETPOST('userid') ? GETPOST('userid') : $user->id;
}
global $userHoliday;
$userHoliday = $userid;

// Protection if external user
if ($user->societe_id > 0)
    accessforbidden();

$now = dol_now();

/*
 * Actions
 */

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$droitAll = ((!empty($user->rights->holiday->write_all) && $user->rights->holiday->write_all) ||
        (!empty($user->rights->holiday->read_all) && $user->rights->holiday->read_all));


// Si création de la demande:
if ($action == 'create') {
    $cp = new SynopsisHoliday($db);
    $isDrh = ($user->id == $cp->getConfCP('drhUserId'));

    // Si pas le droit de créer une demande
    if (!$isDrh) {
        if (is_numeric($userid)) {
            if (($userid == $user->id && empty($user->rights->holiday->write)) ||
                    ($userid != $user->id && !$droitAll)) {
                $error++;
                setEventMessage($langs->trans('CantCreateCP'));
                $action = 'request';
            }
        } else if (!$droitAll) {
            $error++;
            setEventMessage($langs->trans('CantCreateCP'));
            $action = 'request';
        }
    }

    if (!$error) {
        $date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
        $date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
        $date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
        $date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
        $starthalfday = GETPOST('starthalfday');
        $endhalfday = GETPOST('endhalfday');
        $halfday = 0;
        if ($starthalfday == 'afternoon' && $endhalfday == 'morning')
            $halfday = 2;
        else if ($starthalfday == 'afternoon')
            $halfday = -1;
        else if ($endhalfday == 'morning')
            $halfday = 1;

        $valideur = GETPOST('valideur');
        $description = trim(GETPOST('description'));
//        $userID = GETPOST('userID'); // ?? (non utilisé)

        $is_rtt = GETPOST('is_rtt') ? true : false;
        $is_exception = GETPOST('is_exception') ? true : false;
        if ($isGroup)
            $groupId = GETPOST('group_id');

        // Si pas de date de début
        if (empty($date_debut)) {
            header('Location: card.php?action=request&error=nodatedebut');
            exit;
        }

        // Si pas de date de fin
        if (empty($date_fin)) {
            header('Location: card.php?action=request&error=nodatefin');
            exit;
        }

        // Si date de début après la date de fin
        if ($date_debut > $date_fin) {
            header('Location: card.php?action=request&error=datefin');
            exit;
        }

        // Check if there is already holiday for this period
        if (is_numeric($userid)) {
            $verifCP = $cp->verifDateHolidayCP($userid, $date_debut, $date_fin, $halfday);
            if (!$verifCP) {
                header('Location: card.php?action=request&error=alreadyCP');
                exit;
            } else if ($verifCP < 0) {
                header('Location: card.php?action=request&error=substituteCP');
                exit;
            }
        } else {
            if ($groupId < 0) {
                header('Location: card.php?action=request&error=noGroup');
                exit;
            }

            if (is_array($userid) && !count($userid)) {
                header('Location: card.php?action=request&error=noUserSelectedInGroup');
                exit;
            }
            // Vérif des conflits avec des congés collectifs existant:
            $verifGroupCP = $cp->verifDateHolidayForGroup($groupId, $date_debut, $date_fin, $halfday);
            if ($verifGroupCP < 0) {
                header('Location: card.php?action=request&error=checkDatesErrorForGroup');
                exit;
            } else if ($verifGroupCP == 0) {
                header('Location: card.php?action=request&error=alreadyGroupCp');
                exit;
            }
        }

        // Si aucun jours ouvrés dans la demande
        $nbopenedday = num_open_dayUser($userid, $date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);

        // hack (si 1 seul jour : num_open_day() ne vérifie pas s'il s'agit d'un jour férié)
        if ($date_debut_gmt == $date_fin_gmt && $nbopenedday == 1) {
            $nb_ferie = num_public_holidayUser($userid, $date_debut_gmt, $date_fin_gmt);
            if ($nb_ferie)
                $nbopenedday = 0;
        }
        if ($nbopenedday < 0.5) {
            header('Location: card.php?action=request&error=DureeHoliday');
            exit;
        }

        // Si pas de validateur choisi
        if ($valideur < 1) {
            if ($isDrh) {
                $valideur = $user->id;
            } else {
                header('Location: card.php?action=request&error=Valideur');
                exit;
            }
        }

        // Si absence exceptionnelle et rtt sélectionnés en même temps:
        // (même si théoriquement pas possible depuis le formulaire) 
        if ($is_rtt && $is_exception) {
            header('Location: card.php?action=request&error=BothExceptionAndRtt');
            exit;
        }

        $cp->fk_user = $userid;
        $cp->description = $description;
        $cp->date_debut = $date_debut;
        $cp->date_fin = $date_fin;
        $cp->fk_validator = $valideur;
        $cp->halfday = $halfday;
        $cp->type_conges = $is_exception ? 1 : ($is_rtt ? 2 : 0);
        if ($isGroup)
            $cp->fk_group = $groupId;
        $verif = $cp->create($userid);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            header('Location: card.php?id=' . $verif);
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?action=request&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    }
}

// Si mise à jour de la demande:
if ($action == 'update') {
    $date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
    $date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
    $date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
    $date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
    $starthalfday = GETPOST('starthalfday');
    $endhalfday = GETPOST('endhalfday');
    $halfday = 0;
    if ($starthalfday == 'afternoon' && $endhalfday == 'morning')
        $halfday = 2;
    else if ($starthalfday == 'afternoon')
        $halfday = -1;
    else if ($endhalfday == 'morning')
        $halfday = 1;

    $is_rtt = GETPOST('is_rtt') ? true : false;
    $is_exception = GETPOST('is_exception') ? true : false;

    // Si pas le droit de modifier une demande
    if (!$user->rights->holiday->write) {
        header('Location: card.php?action=request&error=CantUpdate');
        exit;
    }

    $cp = new SynopsisHoliday($db);
    $cp->fetch($_POST['holiday_id']);
    if (!$cp->id > 0) {
        echo "Elément inexistant.";
        llxFooter();
        die;
    }
    $isDrh = ($user->id === $cp->getConfCP('drhUserId'));
    $canedit = false;
    if ($isDrh || $droitAll) {
        $canedit = true;
    } else if (is_numeric($cp->fk_user)) {
        $canedit = (($user->id == $cp->fk_user && $user->rights->holiday->write));
    }

    // Si en attente de validation
    if ($cp->statut == 1) {
        // Si c'est le créateur ou qu'il a le droit de tout lire / modifier
        if ($canedit) {
            $valideur = $_POST['valideur'];
            $description = trim($_POST['description']);

            // Si pas de date de début
            if (empty($_POST['date_debut_'])) {
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=nodatedebut');
                exit;
            }

            // Si pas de date de fin
            if (empty($_POST['date_fin_'])) {
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=nodatefin');
                exit;
            }

            // Si date de début après la date de fin
            if ($date_debut > $date_fin) {
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=datefin');
                exit;
            }

            // Si pas de valideur choisi
            if ($valideur < 1) {
                if ($isDrh) {
                    $valideur = $user->id;
                } else {
                    header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=Valideur');
                    exit;
                }
            }

            // Si pas de jours ouvrés dans la demande
            $nbopenedday = num_open_dayUser($userid, $date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
            // hack (si 1 seul jour : num_open_day() ne vérifie pas s'il s'agit d'un jour férié)
            if ($date_debut_gmt == $date_fin_gmt && $nbopenedday == 1) {
                global $userHoliday;
                $userHoliday = $userid;
                $nb_ferie = num_public_holidayUser($userid, $date_debut_gmt, $date_fin_gmt);
                if ($nb_ferie)
                    $nbopenedday = 0;
            }
            if ($nbopenedday < 0.5) {
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=DureeHoliday');
                exit;
            }

            // Si absence exceptionnelle et rtt sélectionnés en même temps:
            // (même si théoriquement pas possible depuis le formulaire) 
            if ($is_rtt && $is_exception) {
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=BothExceptionAndRtt');
                exit;
            }

            // Si aucun user sélectionné dans le groupe:
            if (is_array($cp->fk_user)) {
                $groupUsers = GETPOST('groupUsers');
                if (!isset($groupUsers) || empty($groupUsers) || !count($groupUsers)) {
                    header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=noUserSelectedInGroup');
                    exit;
                }
                $cp->fk_user = $groupUsers;
            }

            if ($isGroup)
                $groupId = GETPOST('group_id');

            if (is_numeric($userid)) {
                $verifCP = $cp->verifDateHolidayCP($userid, $date_debut, $date_fin, $halfday);
                if (!$verifCP) {
                    header('Location: card.php?action=edit&error=alreadyCP');
                    exit;
                } else if ($verifCP < 0) {
                    header('Location: card.php?action=edit&error=substituteCP');
                    exit;
                }
            } else {
                if ($groupId < 0) {
                    header('Location: card.php?action=edit&error=noGroup');
                    exit;
                }

                if (is_array($userid) && !count($userid)) {
                    header('Location: card.php?action=edit&error=noUserSelectedInGroup');
                    exit;
                }
                // Vérif des conflits avec des congés collectifs existant:
                $verifGroupCP = $cp->verifDateHolidayForGroup($groupId, $date_debut, $date_fin, $halfday);
                if ($verifGroupCP < 0) {
                    header('Location: card.php?action=edit&error=checkDatesErrorForGroup');
                    exit;
                } else if ($verifGroupCP == 0) {
                    header('Location: card.php?action=edit&error=alreadyGroupCp');
                    exit;
                }
            }

            $cp->description = $description;
            $cp->date_debut = $date_debut;
            $cp->date_fin = $date_fin;
            $cp->fk_validator = $valideur;
            $cp->halfday = $halfday;

            // on part du principe que la mise à jour doit pouvoir concerner une modif du type de congé:
            $cp->type_conges = $is_exception ? 1 : ($is_rtt ? 2 : 0);

            // Update
            $verif = $cp->update($user->id);
            if ($verif > 0) {
                header('Location: card.php?id=' . $_POST['holiday_id']);
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: card.php?id=' . $_POST['holiday_id'] . '&action=edit&error=SQL_Create&msg=' . $cp->error);
                exit;
            }
        }
    } else {
        header('Location: card.php?id=' . $_POST['holiday_id']);
        exit;
    }
}

// Si suppression de la demande:
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($id);

    if ((($user->id == $cp->fk_user) || $user->rights->holiday->delete || $droitAll) &&
            ($cp->statut == 1)) {
        $error = 0;
        $result = $cp->delete($db);
        if ($result > 0) {
            header('Location: index.php');
            exit;
        }
    }
    $error = $langs->trans('ErrorCantDeleteCP');
}

// Si envoi de la demande:
if ($action == 'confirm_send') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($id);
    $drhUserId = $cp->getConfCP('drhUserId');
    // Si brouillon et créateur
    if ($cp->statut <= 3 && $user->id == $drhUserId) {
        // confirmation directe par le drh:
        $action = 'drh_confirm_valid';
    } else if ($cp->statut == 1 && ($user->id == $cp->fk_user || $droitAll)) {
        $cp->statut = 2;
        $agendaCheck = $cp->onStatusUpdate($user);
        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            // Envoi du mail au validateur, sauf s'il est lui même responsable de la validation.
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_user);
            $emailFrom = $expediteur->email;

            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_validator);
            $emailTo = $destinataire->email;

            if (!$emailTo) {
                header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=create'));
                exit;
            }

            $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
            if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                $societeName = $conf->global->MAIN_APPLICATION_TITLE;

            $dateBegin = dol_print_date($cp->date_debut, 'day');
            $dateEnd = dol_print_date($cp->date_fin, 'day');
            $typeCp = $cp->getTypeLabel(true);

            $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . " a valider.";

            $message = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n";
            $message .= "\n";
            $message .= 'Veuillez trouver ci-dessous une demande de ' . $typeCp . ' à valider.' . "\n";

            $delayForRequest = $cp->getConfCP('delayForRequest');
            //$delayForRequest = $delayForRequest * (60*60*24);
            $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

            // Si l'option pour avertir le valideur en cas de délai trop court
            if ($cp->getConfCP('AlertValidatorDelay')) {
                if ($cp->date_debut < $nextMonth) {
                    $message .= "\n";
                    $message .= 'Cette demande de ' . $typeCp . ' a été effectuée dans un délai de moins de ' . $cp->getConfCP('delayForRequest') . ' avant ceux-ci';
                    $message .= "\n";
                }
            }

            // Si rdv durant la période et pas de remplacent
            $nbRdv = count($cp->fetchRDV());
            if ($nbRdv > 0) {
                $message .= "\n";
                $message .= '' . $nbRdv . ' rdv sont prévus sur la période';
                $message .= "\n";
            }

            // Si l'option pour avertir le valideur en cas de solde inférieur à la demande
            if ($cp->getConfCP('AlertValidatorSolde')) {
                if (empty($cp->type_conges) || !$cp->type_conges) {
                    $soldes = $cp->getCPforUser($cp->fk_user, $cp->date_debut, $cp->date_fin, $cp->halfday, true);
                    if (!isset($soldes['error'])) {
                        if (($soldes['nbOpenDayCurrent'] > 0 && $soldes['nb_holiday_current'] < $soldes['nbOpenDayCurrent']) ||
                                ($soldes['nbOpenDayNext'] > 0 && $soldes['nb_holiday_next'] < $soldes['nbOpenDayNext'])) {
                            $message .= "\n";
                            $message .= $langs->transnoentities("HolidaysToValidateAlertSolde") . "\n";
                        }
                    }
                } else if ($cp->type_conges == 2) {
                    $nbopenedday = num_open_dayUser($cp->fk_user, $cp->date_debut_gmt, $cp->date_fin_gmt, 0, 1, $cp->halfday);
                    if ($nbopenedday > $cp->getRTTforUser($cp->fk_user)) {
                        $message .= "\n";
                        $message .= 'L\'utilisateur ayant fait cette demande de RTT n\'a pas le solde requis.' . "\n";
                    }
                }
            }
            $message .= "\n";
            $message .= "- " . $langs->transnoentitiesnoconv("Name") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
            $message .= "- " . $langs->transnoentitiesnoconv("Period") . " : du " . dol_print_date($cp->date_debut, 'day') . " au " . dol_print_date($cp->date_fin, 'day') . "\n";
            $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
            $message .= "\n";
//die($message);
            $mail = new CMailFile($subject, $emailTo, $emailFrom, $message);
            $result = $mail->sendfile();
            if (!$result) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $mail->error);
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=create'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    }
}

// Si Validation de la demande par le valideur:
if ($action == 'confirm_valid') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($id);
    $drhUserId = $cp->getConfCP('drhUserId');
    // Si statut en attente de validation et valideur = utilisateur
    if ($cp->statut == 2 && $user->id == $cp->fk_validator) {
        $cp->date_valid = dol_now();
        $cp->fk_user_valid = $user->id;
        $cp->statut = 3;

        $agendaCheck = $cp->onStatusUpdate($user);
        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            // *** La màj du solde se fait désormais suite à la validation par le DRH ***
            $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
            if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                $societeName = $conf->global->MAIN_APPLICATION_TITLE;

            $dateBegin = dol_print_date($cp->date_debut, 'day');
            $dateEnd = dol_print_date($cp->date_fin, 'day');
            $typeCp = $cp->getTypeLabel(true);

            $demandeur = new User($db);
            $demandeur->fetch($cp->fk_user);
            $demandeurEmail = $demandeur->email;

            $validator = new User($db);
            $validator->fetch($cp->fk_validator);
            $validatorEmail = $validator->email;

            $drh = new User($db);
            $drh->fetch($cp->getConfCP('drhUserId'));
            $drhEmail = $drh->email;

            $mailErrors = array();

            // *** Mail au demandeur *** 
            if ($demandeurEmail) {
                $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . ' validee par votre superviseur';
                $message = $langs->transnoentitiesnoconv("Hello") . " " . $demandeur->firstname . ",\n\n";
                $message .= 'Votre demande de ' . $typeCp . ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été validée par votre superviseur.';
                $message .= "\n" . 'Cette demande reste encore en attente d\'approbation par votre Directeur des Ressouces Humaines.' . "\n\n";
                $message .= "- " . $langs->transnoentitiesnoconv("ValidatedBy") . " : " . dolGetFirstLastname($validator->firstname, $validator->lastname) . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
                $mail = new CMailFile($subject, $demandeurEmail, $validatorEmail, $message);
                if (!$mail->sendfile())
                    $mailErrors[] = $mail->error;
            }
            // *** Mail au DRH: 
            if ($drhEmail) {
                $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . " a valider";
                $message = $langs->transnoentitiesnoconv("Hello") . " " . $drh->firstname . ",\n\n";
                $message .= 'Veuillez trouver ci-dessous une demande de ' . $typeCp . ' à valider' . "\n";
                $message .= 'Cette demande vient d\'être pré-validée par ' . $validator->firstname . ' ' . $validator->lastname . ".\n";
                if ((!empty($demandeur->fk_user) && $demandeur->fk_user > 0) &&
                        $validator->id != $demandeur->fk_user)
                    $message .= "Attention, il ne s\'agit pas du responsable hiérarchique du demandeur.\n";
                $message .= "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Name") . " : " . dolGetFirstLastname($demandeur->firstname, $demandeur->lastname) . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Period") . " : du " . dol_print_date($cp->date_debut, 'day') . " au " . dol_print_date($cp->date_fin, 'day') . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
                $message .= "\n";
                $mail = new CMailFile($subject, $drhEmail, $validatorEmail, $message);
                if (!$mail->sendfile())
                    $mailErrors[] = $mail->error;
            }
            if (count($mailErrors)) {
                $errorContent = $mailErrors[0];
                if (isset($mailErrors[1]))
                    $errorContent .= '_' . $mailErrors[1];
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $errorContent);
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    } else if ($cp->statut < 3 && $user->id == $drhUserId) {
        // confirmation directe par le drh:
        $action = 'drh_confirm_valid';
    }
}

// Si validation de la demande par le DRH: 
if ($action == 'drh_confirm_valid') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($id);
    $drhUserId = $cp->getConfCP('drhUserId');

    // Si statut en attente de validation et utilisateur = drh
    if (($cp->statut == 1 || $cp->statut == 2 || $cp->statut == 3) && $user->id == $drhUserId) {
        $cp->date_drh_valid = dol_now();
        $cp->fk_user_drh_valid = $user->id;
        if ($cp->statut < 3) {
            $cp->fk_user_valid = $user->id;
            $cp->date_valid = dol_now();
        }

        $cp->statut = 6;
        $agendaCheck = $cp->onStatusUpdate($user);
        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            $soldeUpdateError = '';
            switch ($cp->type_conges) {
                case 0: // congés payés ordinaires
                    $nbHolidayDeducted = $cp->getConfCP('nbHolidayDeducted');
                    // solde année en cours:
                    $soldes = $cp->getCpforUser($cp->fk_user, $cp->date_debut, $cp->date_fin, $cp->halfday, true);
                    if (isset($solde['error'])) {
                        $soldeUpdateError = $solde['error'];
                    } else {
                        if ($soldes['nbOpenDayCurrent'] > 0) {
                            $newSolde = $soldes['nb_holiday_current'] - ($soldes['nbOpenDayCurrent'] * $nbHolidayDeducted);
                            $cp->addLogCP($user->id, $cp->fk_user, $langs->transnoentitiesnoconv("Holidays") . ' (année en cours)', $newSolde, false, true);
                            $res = $cp->updateSoldeCP($cp->fk_user, $newSolde, true);
                            if ($res <= 0)
                                $soldeUpdateError = 'Echec de la mise à jour du solde des congés payés pour l\'année en cours.';
                        }
                        // solde année n+1
                        if ($soldes['nbOpenDayNext'] > 0) {
                            $newSolde = $soldes['nb_holiday_next'] - ($soldes['nbOpenDayNext'] * $nbHolidayDeducted);
                            $cp->addLogCP($user->id, $cp->fk_user, $langs->transnoentitiesnoconv("Holidays") . ' (année suivante)', $newSolde, false, false);
                            $res = $cp->updateSoldeCP($cp->fk_user, $newSolde, false);
                            if ($res <= 0)
                                $soldeUpdateError = 'Echec de la mise à jour du solde des congés payés pour l\'année n+1.';
                        }
                    }
                    break;

                case 1: // absence exceptionnelle, pas de mise à jour du solde des CP
                    break;

                case 2: // RTT
                    // Calcule du nombre de jours consommés: 
                    $nbopenedday = num_open_dayUser($cp->fk_user, $cp->date_debut_gmt, $cp->date_fin_gmt, 0, 1, $cp->halfday);
                    $soldeActuel = $cp->getRTTforUser($cp->fk_user);
                    $newSolde = $soldeActuel - ($nbopenedday * $cp->getConfCP('nbRTTDeducted'));
                    $cp->addLogCP($user->id, $cp->fk_user, 'RTT', $newSolde, true);
                    $res = $cp->updateSoldeRTT($cp->fk_user, $newSolde);
                    if ($res <= 0)
                        $soldeUpdateError = 'Echec de la mise à jour du solde des RTT.';
                    break;
            }

            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;

            $result = 1;
            if (!$emailTo) {
                if (empty($soldeUpdateError)) {
                    header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
                    exit;
                }
            } else {
                $expediteur = new User($db);
                $expediteur->fetch($drhUserId);
                $emailFrom = $expediteur->email;

                $dateBegin = dol_print_date($cp->date_debut, 'day');
                $dateEnd = dol_print_date($cp->date_fin, 'day');
                $typeCp = $cp->getTypeLabel(true);
                $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                    $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . " validee par votre DRH";

                $message = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
                $message .= 'Votre demande de ' . $typeCp . ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été validée par votre Directeur des Ressouces Humaines.' . "\n\n";
                $message .= "- " . $langs->transnoentitiesnoconv("ValidatedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
                $message .= "\n";
                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message);
                $result = $mail->sendfile();
            }
            if (!empty($soldeUpdateError)) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=soldeCPUpdate&error_content=' . $soldeUpdateError);
                exit;
            }
            if (!$result) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $mail->error);
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    }
}

// Si validation d'un congé collectif par le DRH:
if ($action == 'drh_group_valid') {
    echo 'Début<br/>';
    $cp = new SynopsisHoliday($db);
    $cp->fetch($id);
    $drhUserId = $cp->getConfCP('drhUserId');

    // Si statut en attente de validation et utilisateur = drh
    if (($cp->statut <= 3) && $user->id == $drhUserId) {
        $cp->date_drh_valid = dol_now();
        $cp->fk_user_drh_valid = $user->id;
        if ($cp->statut < 3) {
            $cp->fk_user_valid = $user->id;
            $cp->date_valid = dol_now();
        }

        $cp->statut = 6;
        $agendaCheck = $cp->onStatusUpdate($user);
        $verif = $cp->update($user->id);
        $mailErrors = 0;
//        $verif = 1;
        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            $soldeUpdateError = '';
            if (!is_array($cp->fk_user))
                $cp->fk_user = array($cp->fk_user);

            $expediteur = new User($db);
            $expediteur->fetch($drhUserId);
            $emailFrom = $expediteur->email;

            $destinataire = new User($db);

            $dateBegin = dol_print_date($cp->date_debut, 'day');
            $dateEnd = dol_print_date($cp->date_fin, 'day');
            $typeCp = $cp->getTypeLabel(true);
            $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
            if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                $societeName = $conf->global->MAIN_APPLICATION_TITLE;

            $subject = $societeName . " - Notification de " . str_replace('é', 'e', $typeCp);
            $base_message = 'Des ' . $typeCp . ' vous ont été atttribuées pour la période du ' . $dateBegin . ' au ' . $dateEnd . ' par votre Directeur des Ressouces Humaines.' . "\n\n";
            $base_message .= "- " . $langs->transnoentitiesnoconv("ValidatedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
            $base_message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
            $base_message .= "\n";

            $nErrors = 0;
            $nbHolidayDeducted = $cp->getConfCP('nbHolidayDeducted');
            $nbRttDeducted = $cp->getConfCP('nbRTTDeducted');
            $nbOpenDays = $cp->getCPforUser($user->id, $cp->date_debut, $cp->date_fin, $cp->halfday, true);

            foreach ($cp->fk_user as $user_id) {
//                echo 'User: '.$user_id.'<br/>';
                $infos = $cp->verifUserConflictsForGroupHoliday($user, $user_id);
//                echo 'Résult: <pre>';
//                print_r($infos);
//                echo '</pre>';

                $message = $base_message;

                if (count($infos) == 1) {
                    $message .= "Un de vos congé existant a été modifié car il entrait en conflit avec ce nouveau congé. \n\n";
                } else if (count($infos) > 1) {
                    $message .= "Certains de vos congés ont été modifiés car ils entraient en conflit avec ce nouveau congé. \n\n";
                }
                foreach ($infos as $info) {
                    $curCp = new SynopsisHoliday($db);
                    $curCp->fetch($info['id']);
                    $initBegin = new DateTime();
                    $initBegin->setTimestamp($info['initial_date_begin']);
                    $initEnd = new DateTime();
                    $initEnd->setTimestamp($info['initial_date_end']);
                    $message .= "\t- Congé du " . $initBegin->format('d / m / Y') . " au " . $initEnd->format('d / m / Y') . " (Dates initiales):\n";
                    $message .= "\t\tModification faite: ";
                    switch ($info['operation']) {
                        case 'cancel':
                            $message .= "congé annulé";
                            break;

                        case 'begin_update':
                            $newBegin = new DateTime();
                            $newBegin->setTimestamp((int) $curCp->date_debut);
                            $message .= "date de début reculée au " . $newBegin->format('d / m / Y');
                            if ($curCp->halfday < 0 || $curCp->halfday == 2)
                                $message .= " après-midi";
                            unset($newBegin);
                            break;

                        case 'end_update':
                            $newEnd = new DateTime();
                            $newEnd->setTimestamp((int) $curCp->date_fin);
                            $message .= "date de fin avancée au " . $newEnd->format('d / m / Y');
                            if ($curCp->halfday > 0 || $curCp->halfday == 2)
                                $message .= " matin";
                            unset($newEnd);
                            break;

                        case 'create':
                            $message .= "Congé séparé en deux périodes. Cette modification n\'affecte pas la durée totale de votre congé";
                            break;
                    }
                    $message .= ".\n\n";

                    if (count($info['errors'])) {
                        foreach ($info['errors'] as $errorMess) {
                            dol_syslog("Mise à jour du congé " . $info['rowid'] . " pour l'utilisateur " . $user_id . ": " . $errorMess, LOG_ERR);
                            $nErrors++;
                        }
                    }
                    unset($curCp);
                    unset($initBegin);
                    unset($initEnd);
                }

                $soldeUpdateError = '';
                switch ($cp->type_conges) {
                    case 0: // congés payés ordinaires
                        // solde année en cours:
                        $solde = $cp->getCurrentYearCPforUser($user_id);
                        $newSolde = $solde - ($nbOpenDays['nbOpenDayCurrent'] * $nbHolidayDeducted);
                        $cp->addLogCP($user->id, $user_id, $langs->transnoentitiesnoconv("Holidays") . ' (année en cours)', $newSolde, false, true);
                        $res = $cp->updateSoldeCP($user_id, $newSolde, true);
                        if ($res <= 0)
                            $soldeUpdateError = '.';
                        if ($res <= 0) {
                            dol_syslog('Echec de la mise à jour du solde des congés payés pour l\'année en cours. Utilisateur: ' . $user_id . '. Nouveau solde: ' . $newSolde, LOG_ERR);
                            $nErrors++;
                        }

                        // solde année n+1
                        if ($nbOpenDays['nbOpenDayNext'] > 0) {
                            $solde = $cp->getNextYearCPforUser($user_id);
                            $newSolde = $solde - ($nbOpenDays['nbOpenDayNext'] * $nbHolidayDeducted);
                            $cp->addLogCP($user->id, $user_id, $langs->transnoentitiesnoconv("Holidays") . ' (année suivante)', $newSolde, false, false);
                            $res = $cp->updateSoldeCP($user_id, $newSolde, false);
                            if ($res <= 0) {
                                dol_syslog('Echec de la mise à jour du solde des congés payés pour l\'année n+1. Utilisateur: ' . $user_id . '. Nouveau solde: ' . $newSolde, LOG_ERR);
                                $nErrors++;
                            }
                        }
                        break;

                    case 1: // absence exceptionnelle, pas de mise à jour du solde des CP
                        break;

                    case 2: // RTT
                        // Calcule du nombre de jours consommés:
                        $solde = $cp->getRTTforUser($user_id);
                        $newSolde = $solde - ($nbOpenDays['nbOpenDayTotal'] * $nbRttDeducted);
                        $cp->addLogCP($user->id, $user_id, 'RTT', $newSolde, true);
                        $res = $cp->updateSoldeRTT($user_id, $newSolde);
                        if ($res <= 0) {
                            dol_syslog('Echec de la mise à jour du solde des RTT. Utilisateur: ' . $user_id . '. Nouveau solde: ' . $newSolde, LOG_ERR);
                            $nErrors++;
                        }
                        break;
                }

                $destinataire->fetch($user_id);
                $emailTo = $destinataire->email;

                if ($emailTo) {
                    $messageHead = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
                    $mail = new CMailFile($subject, $emailTo, $emailFrom, $messageHead . $message);
                    if (!$mail->sendfile())
                        $mailErrors++;
                }
            }
            echo 'Fin<b>';
//            exit;
            if ($nErrors) {
                $mess = $nErrors . " erreur(s). Veuillez consulter le LOG.";
                header('Location: card.php?id=' . $_GET['id'] . '&error=soldeCPUpdate&error_content=' . $mess);
                exit;
            }
            if ($mailErrors) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . "Echec de l&apos;envoi de " . $mailErrors . " mail(s)");
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    }
}

// Si refus de la demande par le valideur:
if ($action == 'confirm_refuse') {
    if (!empty($_POST['detail_refuse'])) {
        $cp = new SynopsisHoliday($db);
        $cp->fetch($_GET['id']);

        // Si statut en attente de validation et valideur = utilisateur
        if ($cp->statut == 2 && $user->id == $cp->fk_validator) {
            $cp->date_refuse = date('Y-m-d H:i:s', time());
            $cp->fk_user_refuse = $user->id;
            $cp->statut = 5;
            $cp->detail_refuse = $_POST['detail_refuse'];

            $agendaCheck = $cp->onStatusUpdate($user);
            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if ($verif > 0) {
                $destinataire = new User($db);
                $destinataire->fetch($cp->fk_user);
                $emailTo = $destinataire->email;
                if (!$emailTo) {
                    header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
                    exit;
                }
                $expediteur = new User($db);
                $expediteur->fetch($cp->fk_validator);
                $emailFrom = $expediteur->email;

                $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                    $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $dateBegin = dol_print_date($cp->date_debut, 'day');
                $dateEnd = dol_print_date($cp->date_fin, 'day');
                $typeCp = $cp->getTypeLabel(true);
                $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . " refusee par votre superviseur";

                $message = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
                $message .= 'Votre demande de ' . $typeCp . ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été refusée par votre superviseur pour le motif suivant:' . "\n";
                $message .= GETPOST('detail_refuse', 'alpha') . "\n\n";
                $message .= "- " . $langs->transnoentitiesnoconv("ModifiedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message);
                $result = $mail->sendfile();
                if (!$result) {
                    header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $mail->error);
                    exit;
                }
                header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
                exit;
            }
        }
    } else {
        header('Location: card.php?id=' . $_GET['id'] . '&error=NoMotifRefuse');
        exit;
    }
}

// Si refus de la demande par le DRH:
if ($action == 'drh_confirm_refuse') {
    if (!empty($_POST['detail_refuse'])) {
        $cp = new SynopsisHoliday($db);
        $cp->fetch($_GET['id']);
        $drhUserId = $cp->getConfCP('drhUserId');

        // Si statut en attente de validation et utilisateur = drh
        if (($cp->statut == 2 || $cp->statut == 3) &&
                ($user->id == $drhUserId)) {
            $cp->date_refuse = date('Y-m-d H:i:s', time());
            $cp->fk_user_refuse = $user->id;
            $cp->statut = 5;
            $cp->detail_refuse = $_POST['detail_refuse'];

            $agendaCheck = $cp->onStatusUpdate($user);
            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if ($verif > 0) {
                $destinataire = new User($db);
                $destinataire->fetch($cp->fk_user);
                $emailTo = $destinataire->email;
                if (!$emailTo) {
                    header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
                    exit;
                }
                $expediteur = new User($db);
                $expediteur->fetch($drhUserId);
                $emailFrom = $expediteur->email;

                $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                    $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $dateBegin = dol_print_date($cp->date_debut, 'day');
                $dateEnd = dol_print_date($cp->date_fin, 'day');
                $typeCp = $cp->getTypeLabel(true);

                $subject = $societeName . " - Demande de " . str_replace('é', 'e', $typeCp) . ' refusee par votre DRH';
                $message = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
                $message .= 'Votre demande de ' . $typeCp . ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été refusée par votre DRH pour le motif suivant:' . "\n";
                $message .= GETPOST('detail_refuse', 'alpha') . "\n\n";
                $message .= "- " . $langs->transnoentitiesnoconv("ModifiedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
                $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message);
                $result = $mail->sendfile();
                if (!$result) {
                    header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $mail->error);
                    exit;
                }
                header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
                exit;
            }
        }
    } else {
        header('Location: card.php?id=' . $_GET['id'] . '&error=NoMotifRefuse');
        exit;
    }
}

// Si annulation de la demande:
if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($_GET['id']);
    $drhUserId = $cp->getConfCP('drhUserId');
    // Si statut en attente de validation et valideur ou drh = utilisateur
    if (($cp->statut == 2 || $cp->statut == 3 || $cp->statut == 6) &&
            ($user->id == $cp->fk_validator || $user->id == $cp->fk_user || $user->id == $drhUserId)) {
        $db->begin();
        $oldstatus = $cp->statut;
        $cp->date_cancel = dol_now();
        $cp->fk_user_cancel = $user->id;

        $cp->statut = 4;
        $agendaCheck = $cp->onStatusUpdate($user);
        $result = $cp->update($user->id);

        if ($result >= 0 && $oldstatus == 6 && $cp->type_conges != 1) { // holiday was already validated, status 6, so we must increase back sold
            $cp->recrediteSold();
        }
        if (!$error) {
            $db->commit();
        } else {
            $db->rollback();
        }

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if (!$error && $result > 0) {
            // To
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;
            if (!$emailTo) {
                header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
                exit;
            }
            $expediteur = new User($db);
            $expediteur->fetch($user->id);
            $emailFrom = $expediteur->email;

            $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
            if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                $societeName = $conf->global->MAIN_APPLICATION_TITLE;

            $dateBegin = dol_print_date($cp->date_debut, 'day');
            $dateEnd = dol_print_date($cp->date_fin, 'day');
            $typeCp = $cp->getTypeLabel(true);

            $subject = $societeName . " - " . 'Demande de ' . str_replace('é', 'e', $typeCp) . ' annulee';
            $message = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
            $message .= 'Votre demande de ' . $typeCp . ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été annulée.' . "\n\n";
            $message .= "- " . $langs->transnoentitiesnoconv("ModifiedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
            $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";
            $mail = new CMailFile($subject, $emailTo, $emailFrom, $message);
            $result = $mail->sendfile();
            if (!$result) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $mail->error);
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=delete'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=cantCancelCP&msg=' . $error);
            exit;
        }
    }
}

// Si annulation d'un congé collectif
if ($action == 'confirm_group_cancel' && GETPOST('confirm') == 'yes') {
    $cp = new SynopsisHoliday($db);
    $cp->fetch($_GET['id']);
    $drhUserId = $cp->getConfCP('drhUserId');

    // Si statut en attente de validation et valideur ou drh = utilisateur
    if (($cp->statut == 2 || $cp->statut == 3 || $cp->statut == 6) &&
            ($user->id == $drhUserId || $user->admin)) {
        $oldstatus = $cp->statut;
        $cp->date_cancel = dol_now();
        $cp->fk_user_cancel = $user->id;
        $cp->statut = 4;
        $agendaCheck = $cp->onStatusUpdate($user);
        $verif = $cp->update($user->id);

        if ($result >= 0 && $oldstatus == 6 && $cp->type_conges != 1) { // holiday was already validated, status 6, so we must increase back sold
            $cp->recrediteSold();
        }

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0) {
            if (!is_array($cp->fk_user))
                $cp->fk_user = array($cp->fk_user);

            $mailErrors = 0;
            $soldeUpdateErrors = 0;

            $expediteur = new User($db);
            $expediteur->fetch($drhUserId);
            $emailFrom = $expediteur->email;

            $destinataire = new User($db);

            $dateBegin = dol_print_date($cp->date_debut, 'day');
            $dateEnd = dol_print_date($cp->date_fin, 'day');
            $typeCp = $cp->getTypeLabel(true);
            $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
            if (!empty($conf->global->MAIN_APPLICATION_TITLE))
                $societeName = $conf->global->MAIN_APPLICATION_TITLE;

            $subject = $societeName . " - Annulation de " . str_replace('é', 'e', $typeCp);
            $message .= 'Vos ' . $typeCp . ' pour la période du ' . $dateBegin . ' au ' . $dateEnd . ' ont été annulé(es) par votre Directeur des Ressouces Humaines.' . "\n\n";
            $message .= "- " . $langs->transnoentitiesnoconv("ValidatedBy") . " : " . dolGetFirstLastname($expediteur->firstname, $expediteur->lastname) . "\n";
            $message .= "- " . $langs->transnoentitiesnoconv("Link") . " : " . $dolibarr_main_url_root . "/synopsisholiday/card.php?id=" . $cp->rowid . "\n\n";

            foreach ($cp->fk_user as $user_id) {
//                switch ($cp->type_conges) {
//                    case 0: // congés payés ordinaires
//                        break;
//
//                    case 1: // absence exceptionnelle, pas de mise à jour du solde des CP
//                        break;
//
//                    case 2: // RTT
//                        // Calcule du nombre de jours consommés: 
//                        break;
//                }

                $destinataire->fetch($user_id);
                $emailTo = $destinataire->email;

                if ($emailTo) {
                    $messageHead = $langs->transnoentitiesnoconv("Hello") . " " . $destinataire->firstname . ",\n\n";
                    $mail = new CMailFile($subject, $emailTo, $emailFrom, $messageHead . $message);
                    if (!$mail->sendfile())
                        $mailErrors++;
                }
            }
//            if ($soldeUpdateError) {
//                header('Location: card.php?id=' . $_GET['id'] . '&error=soldeCPUpdate&error_content=' . $soldeUpdateError);
//                exit;
//            }
            if ($mailErrors) {
                header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . "Echec de l&apos;envoi de " . $mailErrors . " mail(s)");
                exit;
            }
            header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: card.php?id=' . $_GET['id'] . '&error=SQL_Create&msg=' . $cp->error);
            exit;
        }
    }
}

// Si enregistrement du remplaçant par le DRH:
if ($action == 'save_substitute') {
    $substitute_id = GETPOST('substitute_user_id', 'int') ?: GETPOST('substitute_user_id_other_service', 'int');

    $cp = new SynopsisHoliday($db);
    $cp->fetch($_GET['id']);

    $cpUser = new User($db);
    $cpUser->fetch($cp->fk_user);

    $drhUserId = $cp->getConfCP('drhUserId');

    $fk_old_substitute = $cp->fk_substitute;
    $cp->fk_substitute = $substitute_id;

    $agendaCheck = $cp->onStatusUpdate($user);
    $result = $cp->update();

    if ($result > 0) {
        $rdvs = $cp->fetchRDV();

        $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
        if (!empty($conf->global->MAIN_APPLICATION_TITLE))
            $societeName = $conf->global->MAIN_APPLICATION_TITLE;

        $dateBegin = dol_print_date($cp->date_debut, 'day');
        $dateEnd = dol_print_date($cp->date_fin, 'day');

        $mailErrors = array();

        if (isset($fk_old_substitute) && $fk_old_substitute > 0) {
            // Mail d'annulation pour l'ancien remplaçant:
            $oldSubstitute = new User($db);
            $oldSubstitute->fetch($fk_old_substitute);

            $rdvErrors = false;
            foreach ($rdvs as $rdv_id) {
                $sql = 'SELECT `id` FROM ' . MAIN_DB_PREFIX . 'actioncomm WHERE `fk_user_action` = ' . $fk_old_substitute;
                $sql .= ' AND elementtype="action" AND `fk_element` = ' . $rdv_id;
                $resql = $db->query($sql);
                if ($resql) {
                    $obj = $db->fetch_object($resql);
                    if (isset($obj)) {
                        $actioncomm = new ActionComm($db);
                        $actioncomm->fetch($obj->id);
                        if ($actioncomm->delete() < 0)
                            $rdvErrors = true;
                        unset($actioncomm);
                    }
                } else
                    $rdvErrors = true;
            }

            if ($oldSubstitute->email) {
                $subject = $societeName . ' - ' . 'Annulation de remplacement';
                $message = $langs->transnoentitiesnoconv("Hello") . " " . $oldSubstitute->firstname . ",\n\n";
                $message .= 'Votre remplacement dans le cadre de la prise de congés de ' . dolGetFirstLastname($cpUser->firstname, $cpUser->lastname);
                $message .= ' du ' . $dateBegin . ' au ' . $dateEnd . ' a été annulé.' . "\n\n";
                if ($rdvErrors) {
                    $message .= 'Attention : certain rendez-vous concernant ce remplacement n\'ont pas pu être supprimés de votre agenda.' . "\n";
                    $message .= 'Veuillez les effacer manuellement.' . "\n\n";
                }
                $mail = new CMailFile($subject, $oldSubstitute->email, $user->email, $message);
                if (!$mail->sendfile()) {
                    $mailErrors[] = dolGetFirstLastname($oldSubstitute->firstname, $oldSubstitute->lastname);
                }
            }
        }
        if ($substitute_id > 0) {

            $substitute = new User($db);
            $substitute->fetch($substitute_id);

            $rdvErrors = false;
            foreach ($rdvs as $rdv_id) {
                $actioncomm = new ActionComm($db);
                $actioncomm->fetch($rdv_id);
                $actioncomm->id = null;
                $actioncomm->fk_element = $rdv_id;
                $actioncomm->elementtype = "action";
                $actioncomm->userownerid = $substitute->id;
                $note = $actioncomm->note;
                $actioncomm->note = 'Remplacement de ' . $cpUser->firstname . ' ' . $cpUser->lastname . ($note ? ' - ' . $note : '');
                if ($actioncomm->add($substitute) < 0)
                    $rdvErrors = true;
                unset($actioncomm);
            }
            if ($substitute->email) {
                $subject = $societeName . ' - ' . 'Notification de remplacement';
                $message = $langs->transnoentitiesnoconv("Hello") . " " . $substitute->firstname . ",\n\n";
                $message .= 'Vous avez été désigné comme remplaçant dans le cadre de la prise de congés de ' . dolGetFirstLastname($cpUser->firstname, $cpUser->lastname);
                $message .= ' du ' . $dateBegin . ' au ' . $dateEnd . ' par ' . dolGetFirstLastname($user->firstname, $user->lastname) . '.' . "\n\n";
                if (count($rdvs)) {
                    $message .= 'La personne que vous replacez devait effectuer ' . count($rdvs) . ' rendez-vous durant cette période.' . "\"n";
                    if (!$rdvErrors) {
                        $message .= 'Ceux-ci ont été ajoutés à votre agenda.';
                    } else {
                        $message .= 'Ceux-ci n\'ont pas pu être ajoutés à votre agenda en raison d\'une erreur technique.' . "\n";
                    }
                    $message .= "\n\n";
                }
                $mail = new CMailFile($subject, $substitute->email, $user->email, $message);
                if (!$mail->sendfile()) {
                    $mailErrors[] = dolGetFirstLastname($substitute->firstname, $substitute->lastname);
                }
            }
        }

        if (count($mailErrors)) {
            $errorMsg = '';
            foreach ($mailErrors as $err) {
                $errorMsg .= $err . ' - Echec de l\'envoi du mail. ';
            }
            header('Location: card.php?id=' . $_GET['id'] . '&error=mail&error_content=' . $errorMsg);
        }
        header('Location: card.php?id=' . $_GET['id'] . ($agendaCheck ? '' : '&error=agenda&agenda_error_type=update'));
        exit;
    } else {
        header('Location: card.php?id=' . $_GET['id'] . '&error=substitute_update&msg=' . $cp->error);
    }
}

/*
 * View
 */

$form = new Form($db);
$cp = new SynopsisHoliday($db);

$listhalfday = array('morning' => $langs->trans("Morning"), "afternoon" => $langs->trans("Afternoon"));

llxHeader(array(), $langs->trans('CPTitreMenu'));

$isDrh = ($user->id === $cp->getConfCP('drhUserId'));

if (empty($id) || $action == 'add' || $action == 'request' || $action == 'create') {
    // Si l'utilisateur n'a pas le droit de faire une demande
    if (($userid == $user->id && empty($user->rights->holiday->write)) ||
            ($userid != $user->id && !$droitAll)) {
        $errors[] = $langs->trans('CantCreateCP');
    } else {
        // Formulaire de demande de congés payés
        print_fiche_titre($langs->trans('MenuAddCP'));

        // Si il y a une erreur
        if (GETPOST('error')) {

            switch (GETPOST('error')) {
                case 'datefin' :
                    $errors[] = $langs->trans('ErrorEndDateCP');
                    break;
                case 'SQL_Create' :
                    $errors[] = $langs->trans('ErrorSQLCreateCP') . ' <b>' . htmlentities($_GET['msg']) . '</b>';
                    break;
                case 'CantCreate' :
                    $errors[] = $langs->trans('CantCreateCP');
                    break;
                case 'cantCancelCP':
                    $errors[] = 'Echec de l\'annulation. ' . ' <b>' . htmlentities($_GET['msg']) . '</b>';
                    break;
                case 'Valideur' :
                    $errors[] = $langs->trans('InvalidValidatorCP');
                    break;
                case 'nodatedebut' :
                    $errors[] = $langs->trans('NoDateDebut');
                    break;
                case 'nodatefin' :
                    $errors[] = $langs->trans('NoDateFin');
                    break;
                case 'DureeHoliday' :
                    $errors[] = $langs->trans('ErrorDureeCP');
                    break;
                case 'alreadyCP' :
                    $errors[] = $langs->trans('alreadyCPexist');
                    break;
                case 'substituteCP':
                    $errors[] = 'Vous ne pouvez pas déposer de congés sur cette période car vous avez été désigné comme remplaçant.';
                    break;
                case 'BothExceptionAndRtt':
                    $errors[] = 'Veuillez ne sélectionner qu\'un seul choix dans la partie "Absence exceptionnelle / RTT".';
                    break;
                case 'noGroup':
                    $errors[] = 'Veuillez sélectionner un groupe d\'utilisateurs.';
                    break;
                case 'noUserSelectedInGroup':
                    $errors[] = 'Veuillez sélectionner au moins un utilisateur dans le groupe.';
                    break;
                case 'checkDatesErrorForGroup':
                    $errors[] = 'Une erreur est survenue: les conflits avec des congés collectifs existants n\'ont pas pu être vérifiés.';
                    break;
                case 'alreadyGroupCp':
                    $errors[] = 'Un congé collectif pour le groupe choisi existe déjà sur cette période.';
                    break;
            }

            dol_htmloutput_mesg('', $errors, 'error');
        }


        $delayForRequest = $cp->getConfCP('delayForRequest');
        //$delayForRequest = $delayForRequest * (60*60*24);
        $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

//        print '<script type="text/javascript">
//	    function valider()
//	    {   
//    	    if(document.demandeCP.date_debut_.value != "")
//    	    {
//	           	if(document.demandeCP.date_fin_.value == "")';
////	           	{
////	               if(document.demandeCP.valideur.value != "-1") {
////	                 return true;
////	               }
////	               else {
////	                 alert("' . dol_escape_js($langs->transnoentities('InvalidValidatorCP')) . '");
////	                 return false;
////	               }
////	               }
////	            else
//        print '{
//	              alert("' . dol_escape_js($langs->transnoentities('NoDateFin')) . '");
//	              return false;
//	            }
//	        }
//	        else
//	        {
//	           alert("' . dol_escape_js($langs->transnoentities('NoDateDebut')) . '");
//	           return false;
//	        }
//       	}
//       </script>' . "\n";
        // Formulaire de demande
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" onsubmit="return valider()" name="demandeCP">' . "\n";
        print '<input type="hidden" name="action" value="create" />' . "\n";
        print '<input type="hidden" name="userID" value="' . $userid . '" />' . "\n";
        print '<div class="tabBar">';
        print '<span>' . $langs->trans('DelayToRequestCP', $cp->getConfCP('delayForRequest')) . '</span><br /><br />';

        print '<table class="border" width="100%">';
        print '<tbody>';
        print '<tr>';
        print '<td class="fieldrequired">' . $langs->trans("User") . '</td>';
        print '<td>';
        if ($isDrh) {
            print '<div style="margin-bottom: 15px">';
            print '<input type="radio" name="usersGroup" value="0" id="userGroup_no" checked/>';
            print '<label for="userGroup_no" style="margin-right: 15px">Congés individuels</label>';
            print '<input type="radio" name="usersGroup" value="1" id="userGroup_yes"/>';
            print '<label for="userGroup_no">Congés collectifs</label>';
            print '</div>';
        }
        print '<script type="text/javascript" src="' . $dolibarr_main_url_root . '/synopsisholiday/js/card.js"></script>';
        print '<div id="singleUserBlock">';
        print '<div style="display: inline-block; vertical-align: top; margin-right: 15px;">';
        if (!$droitAll) {
            print $form->select_users($userid, 'useridbis', 0, '', 1);
            print '<input type="hidden" name="userid" value="' . $userid . '">';
        } else
            print $form->select_users(GETPOST('userid') ? GETPOST('userid') : $user->id, 'userid', 0, '', 0);

//        $nb_holiday_current = $cp->getCPforUser($user->id) / $cp->getConfCP('nbHolidayDeducted');
//        $nb_rtt = $cp->getRTTforUser($user->id) / $cp->getConfCP('nbRTTDeducted');
//        print ' &nbsp; <span>' . $langs->trans('SoldeCPUser', round($nb_holiday, 0)) . '</span>';
//        print ' &nbsp; <span>Solde de RTT: <b>' . $nb_rtt . ' jours</b></span>';

        print '</div><div style="display: inline-block; vertical-align: top;">';

        $nbaquis_current = $cp->getCurrentYearCPforUser($user->id);
        $nbaquis_next = $cp->getNextYearCPforUser($user->id);
        $nbdeduced = $cp->getConfCP('nbHolidayDeducted');
        $nb_holiday_current = $nbaquis_current / $nbdeduced;
        $nb_holiday_next = $nbaquis_next / $nbdeduced;
        $nextCPYearDate = $cp->getCPNextYearDate(true, false);
        $nextCPYearDateAfter = $cp->getCPNextYearDate(true, true);
        $nbRtt = $cp->getRTTforUser($user->id) / $cp->getConfCP('nbRTTDeducted');
          print '<b>Année en cours : </b>';
          print $langs->trans('SoldeCPUser', round($nb_holiday_current, 2)) . ($nbdeduced != 1 ? ' (' . $nbaquis_current . ' / ' . $nbdeduced . ')' : '');
          print '&nbsp;(A utiliser avant le <b>' . $nextCPYearDate . '</b>).';
          print '<br/>';
          print '<b>Année n+1 : </b>';
          print $langs->trans('SoldeCPUser', round($nb_holiday_next, 2)) . ($nbdeduced != 1 ? ' (' . $nbaquis_next . ' / ' . $nbdeduced . ')' : '');
          print '&nbsp;(A utiliser à partir du <b>' . $nextCPYearDate . '</b> et avant le <b>' . $nextCPYearDateAfter . '</b>).';
          print '<br/>';
          print 'Solde RTT : <b>' . round($nbRtt, 2) . ' jours</b>';
         
        print '</div></div>';

        if ($isDrh) {
            print '<div id="userGroupBlock" style="display: none">';
            print '<label>Groupe: </label>';
            print $form->select_dolgroups(0, 'group_id', 1);
            print '<input class="butAction" type="button" id="showGroupUsers" value="Sélectionner les utilisateurs" style="margin-left: 15px; display: none"/>';

            print '<div id="groupUsersCheckboxes" style="position: absolute; display: none; background: #fff; padding: 10px; margin: 10px; border: 1px solid #969696">';
            print '<div style="text-align: right; margin: 10px"><input type="button" class="butAction" id="closeGroupUsers" value="Fermer"/></div>';
            print '<div class="loading" style="display: none">Chargement de la liste des utilisateurs en cours...</div>';
            print '<div id="groupUsersList" style="display: none"></div>';
            print '</div>';
            print '</div>';
        }

        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">' . $langs->trans("DateDebCP") . ' (' . $langs->trans("FirstDayOfHoliday") . ')</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if (!isset($_GET['datep'])) {
            $form->select_date(-1, 'date_debut_');
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('datepmonth'), GETPOST('datepday'), GETPOST('datepyear'));
            $form->select_date($tmpdate, 'date_debut_');
        }
        print ' &nbsp; &nbsp; ';
        print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday') ? GETPOST('starthalfday') : 'morning'));
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">' . $langs->trans("DateFinCP") . ' (' . $langs->trans("LastDayOfHoliday") . ')</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if (!isset($_GET['datep'])) {
            $form->select_date(-1, 'date_fin_');
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('datefmonth'), GETPOST('datefday'), GETPOST('datefyear'));
            $form->select_date($tmpdate, 'date_fin_');
        }
        print ' &nbsp; &nbsp; ';
        print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday') ? GETPOST('endhalfday') : 'afternoon'));
        print '</td>';
        print '</tr>';

        // Approved by
        print '<tr>';
        print '<td class="fieldrequired">' . $langs->trans("ReviewedByCP") . '</td>';
        // Liste des utiliseurs du groupe choisi dans la config
        $validator = new UserGroup($db);
//        $validator->id = "60";
        $excludefilter = $user->admin ? '' : 'u.rowid <> ' . $user->id;
        $valideurobjects = $validator->listUsersForGroup($excludefilter, 1);
        $valideurarray = array();
        foreach ($valideurobjects as $val)
            $valideurarray[$val] = $val;
        print '<td>';
        print $form->select_dolusers($user->fk_user, "valideur", 1, "", 0, $valideurarray); // By default, hierarchical parent
        print '</td>';
        print '</tr>';

        // Description
        print '<tr>';
        print '<td>' . $langs->trans("DescCP") . '</td>';
        print '<td>';
        print '<textarea name="description" id="description"  class="flat" rows="' . ROWS_3 . '" cols="70"></textarea>';

        print '</td>';
        print '</tr>';

        // Sélection RTT / Absence exceptionnelle:
        print '<tr>';
        print '<td>Absence exceptionnelle / RTT</td>';
        print '<td>';
        print '<span>';
        print '<input type="checkbox" name="is_exception" id="is_exception" style="margin-right: 10px"/>';
        print '<label for="is_exception">Il s\'agit d\'une demande d\'absence exceptionnelle (Ne décompte pas le solde)</label>';

        print '<br/><select name="choixRapide" style="display:none;">'
                . '<option value="">Raison</option>'
                . '<option value="">Maladie</option>'
                . '<option value="">Mariage</option>'
                . '<option value="">Naissance ou Adoption</option>'
                . '<option value="">Décès</option>'
                . '<option value="">Enfant malade</option>'
                . '<option value="">Déménagement</option>'
                . '<option value="">Congés paternité </option>'
                . '<option value="">Récupération </option>'
                . '<option value="">Autres</option>'
                . '</select>';
        print '</span><br/>';
        print '<span>';
        print '<input type="checkbox" name="is_rtt" id="is_rtt" style="margin-right: 10px"/>';
        print '<label for="is_rtt">Il s\'agit d\'une demande de RTT</label>';
        print '</span>';
        print '</td>';
        print '</tr>';

        print '</tbody>';
        print '</table>';
        print '<div style="clear: both;"></div>';
        print '</div>';
        print '</from>' . "\n";

        print '<center>';
        print '<input type="submit" value="' . $langs->trans("Save") . '" name="bouton" class="butAction">';
        print '&nbsp; &nbsp; ';
        print '<input type="button" value="' . $langs->trans("Cancel") . '" class="butAction" onclick="history.go(-1)">';
        print '</center>';

        // js - sélection d'un seul checkbox RTT ou absence exceptionnelle
//        print '<script type="text/javascript">';
//        print '$(document).ready(function() {';
//        print "$('#is_exception').change(function() {";
//        print "if ($(this).prop('checked') && $('#is_rtt').prop('checked'))";
//        print "$('#is_rtt').removeAttr('checked');";
//        print "});";
//        print "$('#is_rtt').change(function() {";
//        print "if ($(this).prop('checked') && $('#is_exception').prop('checked'))";
//        print "$('#is_exception').removeAttr('checked');";
//        print "});";
//        print '});';
//        print '</script>';
    }
} else {
    if ($error) {
        print '<div class="tabBar">';
        print $error;
        print '<br /><br /><input type="button" value="' . $langs->trans("ReturnCP") . '" class="butAction" onclick="history.go(-1)" />';
        print '</div>';
    } else {
        // Affichage de la fiche d'une demande de congés payés
        if ($id > 0) {
            $cp->fetch($id);

            if (!$cp->id > 0) {
                echo "Elément inexistant.";
                llxFooter();
                die;
            }
            $drhUserId = $cp->getConfCP('drhUserId');
            $canedit = false;
            if ($isDrh || $droitAll)
                $canedit = true;
            else if (is_numeric($cp->fk_user)) {
                $canedit = (($user->id == $cp->fk_user && $user->rights->holiday->write) ||
                        ($user->id != $cp->fk_user && ($droitAll)));
            }

            $valideur = new User($db);
            $valideur->fetch($cp->fk_validator);

            if (is_numeric($cp->fk_user)) {
                $userRequest = new User($db);
                $userRequest->fetch($cp->fk_user);
            } else {
                $userRequest = null;
            }

            //print_fiche_titre($langs->trans('TitreRequestCP'));
            // Si il y a une erreur
            if (GETPOST('error')) {
                switch (GETPOST('error')) {
                    case 'datefin' :
                        $errors[] = $langs->transnoentitiesnoconv('ErrorEndDateCP');
                        break;
                    case 'SQL_Create' :
                        $errors[] = $langs->transnoentitiesnoconv('ErrorSQLCreateCP') . ' ' . $_GET['msg'];
                        break;
                    case 'CantCreate' :
                        $errors[] = $langs->transnoentitiesnoconv('CantCreateCP');
                        break;
                    case 'Valideur' :
                        $errors[] = $langs->transnoentitiesnoconv('InvalidValidatorCP');
                        break;
                    case 'nodatedebut' :
                        $errors[] = $langs->transnoentitiesnoconv('NoDateDebut');
                        break;
                    case 'nodatefin' :
                        $errors[] = $langs->transnoentitiesnoconv('NoDateFin');
                        break;
                    case 'DureeHoliday' :
                        $errors[] = $langs->transnoentitiesnoconv('ErrorDureeCP');
                        break;
                    case 'NoMotifRefuse' :
                        $errors[] = $langs->transnoentitiesnoconv('NoMotifRefuseCP');
                        break;
                    case 'mail' :
                        $errors[] = $langs->transnoentitiesnoconv('ErrorMailNotSend') . "\n" . $_GET['error_content'];
                        break;
                    case 'agenda':
                        switch (GETPOST('agenda_error_type')) {
                            case 'create':
                                $errors[] = 'Echec de l\'ajout des congés dans votre agenda';
                                break;

                            case 'update':
                                $errors[] = 'Echec de la mise à jour de l\'état de votre demande de congés dans votre agenda';
                                break;

                            case 'delete':
                                $errors[] = 'Echec de la suppression des congés dans votre agenda';
                                break;
                        }
                        break;
                    case 'substitute_update':
                        $errors[] = 'Echec de l\'enregistrement du remplaçant (Erreur: ' . $_GET['msg'] . ')';
                        break;
                    case 'soldeCPUpdate':
                        $errors[] = $_GET['error_content'];
                        break;
                    case 'noUserSelectedInGroup':
                        $errors[] = 'Veuillez sélectionner au moins un utilisateur dans le groupe.';
                        break;
                    case 'BothExceptionAndRtt':
                        $errors[] = 'Veuillez ne sélectionner qu\'un seul choix dans la partie "Absence exceptionnelle / RTT".';
                        break;
                }

                dol_htmloutput_mesg('', $errors, 'error');
            }

            // On vérifie si l'utilisateur a le droit de lire cette demande
            if ($canedit) {
                if ($action == 'delete') {
                    if ($user->rights->holiday->delete || (is_numeric($cp->fk_user) && $user->id == $cp->fk_user)) {
                        print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleDeleteCP"), $langs->trans("ConfirmDeleteCP"), "confirm_delete", '', 0, 1);
                    }
                }

                // Si envoi en validation
                if ($action == 'sendToValidate' && $cp->statut == 1 && ((is_numeric($cp->fk_user) && $user->id == $cp->fk_user) || $droitAll)) {
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleToValidCP"), $langs->trans("ConfirmToValidCP"), "confirm_send", '', 1, 1);
                }

                // Si validation de la demande (par le valideur)
                if ($action == 'valid') {
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid", '', 1, 1);
                }

                // Si validation de la demande par le DRH:
                if ($action == 'drhValid') {
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "drh_confirm_valid", '', 1, 1);
                }

                // Si validation d'un congé collectif par le DRH:
                if ($action == 'groupCPValidate') {
                    $confirmMess = 'Etes-vous sûr de vouloir valider cette demande de congés collectifs?<br/>';
                    $confirmMess .= 'Celle-ci passera automatiquement à l\'état "Approuvé par le DRH"';
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleValidCP"), $confirmMess, "drh_group_valid", '', 1, 1);
                }

                // Si refus de la demande
                if ($action == 'refuse') {
                    $array_input = array(array('type' => "text", 'label' => $langs->trans('DetailRefusCP'), 'name' => "detail_refuse", 'size' => "50", 'value' => ""));
                    print $form->formconfirm("card.php?id=" . $id . "&action=confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 1, 0);
                }

                // Si refus par le DRH: 
                if ($action == 'drhRefuse') {
                    $array_input = array(array('type' => "text", 'label' => $langs->trans('DetailRefusCP'), 'name' => "detail_refuse", 'size' => "50", 'value' => ""));
                    print $form->formconfirm("card.php?id=" . $id . "&action=drh_confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 1, 0);
                }

                // Si annulation de la demande
                if ($action == 'cancel') {
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleCancelCP"), $langs->trans("ConfirmCancelCP"), "confirm_cancel", '', 1, 1);
                }

                // Si annulation d'un congé collectif:
                if ($action == 'group_cancel') {
                    $confirmMess = 'Etes-vous sûr de vouloir annuler ces congés collectifs?';
                    print $form->formconfirm("card.php?id=" . $id, $langs->trans("TitleCancelCP"), $confirmMess, "confirm_group_cancel", '', 1, 1);
                }

                $head = synopsisholiday_prepare_head($cp);

                dol_fiche_head($head, 'card', $langs->trans("CPTitreMenu"), 0, 'holiday');
                $edit = false;
                if ($action == 'edit' && $cp->statut == 1) {
                    $edit = true;
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id'] . '">' . "\n";
                    print '<input type="hidden" name="action" value="update"/>' . "\n";
                    print '<input type="hidden" name="holiday_id" value="' . $_GET['id'] . '" />' . "\n";
                }

                print '<table class="border" width="100%">';
                print '<tbody>';

                $linkback = '';

                print '<tr>';
                print '<td width="25%">' . $langs->trans("Ref") . '</td>';
                print '<td>';
                print $form->showrefnav($cp, 'id', $linkback, 1, 'rowid', 'ref');
                print '</td>';
                print '</tr>';

                print '<tr>';
                print '<td>' . $langs->trans("User") . '</td>';
                print '<td>';
                if (isset($userRequest))
                    print $userRequest->getNomUrl(1);
                else if (is_array($cp->fk_user)) {
                    $users = array();
                    print '<input type="hidden" name="usersGroup" id="usersGroup" value="1"/>';
                    if (isset($cp->fk_group) && !empty($cp->fk_group)) {
                        $group = new UserGroup($db);
                        $group->fetch($cp->fk_group);
                        print 'Groupe: <b>' . $group->name . '</b> ';
                        $users = $group->listUsersForGroup();
                        print '<input type="hidden" name="group_id" id="group_id" value="' . $cp->fk_group . '"/>';
                    } else {
                        foreach ($cp->fk_user as $user_id) {
                            $curUser = new User($db);
                            $curUser->fetch($user_id);
                            $users[] = $curUser;
                        }
                        print 'Congés collectifs ';
                        print '<input type="hidden" name="group_id" id="group_id" value="-1"/>';
                    }
                    print '<input class="butAction" id="showUsersList" type="button" value="Afficher la liste des utilisateurs"/>';
                    print '<div id="usersListContainer" style="display: none; background:#fff; padding: 15px; position: absolute; border: 1px solid #787878">';
                    print '<div style="margin-bottom: 10px; text-align: right">';
                    print '<input type="button" class="butAction" id="closeUsersList" value="Fermer"/>';
                    print '</div>';
                    foreach ($users as $curUser) {
                        if ($edit) {
                            print '<input type="checkbox" name="groupUsers[]" id="groupUser_' . $curUser->id . '" value="' . $curUser->id . '" style="margin-right: 10px"';
                            if (in_array($curUser->id, $cp->fk_user))
                                print ' checked';
                            print '/>';
                            print $curUser->lastname . ' ' . $curUser->firstname;
                            print '<br/>';
                        } else if (in_array($curUser->id, $cp->fk_user)) {
                            print $curUser->getNomUrl(1) . '<br/>';
                        }
                    }
                    print '</div>';
                    print '<script type="text/javascript" src="' . DOL_MAIN_URL_ROOT . '/synopsisholiday/js/card.js"></script>';
                }
                print '</td></tr>';

                $starthalfday = ($cp->halfday == -1 || $cp->halfday == 2) ? 'afternoon' : 'morning';
                $endhalfday = ($cp->halfday == 1 || $cp->halfday == 2) ? 'morning' : 'afternoon';

                if (!$edit) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateDebCP') . ' (' . $langs->trans("FirstDayOfHoliday") . ')</td>';
                    print '<td>' . dol_print_date($cp->date_debut, 'day');
                    print ' &nbsp; &nbsp; ';
                    print $langs->trans($listhalfday[$starthalfday]);
                    print '</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateDebCP') . ' (' . $langs->trans("FirstDayOfHoliday") . ')</td>';
                    print '<td>';
                    $form->select_date($cp->date_debut, 'date_debut_');
                    print ' &nbsp; &nbsp; ';
                    print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday') ? GETPOST('starthalfday') : $starthalfday));
                    print '</td>';
                    print '</tr>';
                }

                if (!$edit) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateFinCP') . ' (' . $langs->trans("LastDayOfHoliday") . ')</td>';
                    print '<td>' . dol_print_date($cp->date_fin, 'day');
                    print ' &nbsp; &nbsp; ';
                    print $langs->trans($listhalfday[$endhalfday]);
                    print '</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateFinCP') . ' (' . $langs->trans("LastDayOfHoliday") . ')</td>';
                    print '<td>';
                    $form->select_date($cp->date_fin, 'date_fin_');
                    print ' &nbsp; &nbsp; ';
                    print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday') ? GETPOST('endhalfday') : $endhalfday));
                    print '</td>';
                    print '</tr>';
                }
                print '<tr>';
                print '<td>' . $langs->trans('NbUseDaysCP') . '</td>';
                print '<td>' . num_open_dayUser($cp->fk_user, $cp->date_debut_gmt, $cp->date_fin_gmt, 0, 1, $cp->halfday) . '</td>';
                print '</tr>';

                // Status
                print '<tr>';
                print '<td>' . $langs->trans('StatutCP') . '</td>';
                print '<td>' . $cp->getLibStatut(2) . '</td>';
                print '</tr>';
                if ($cp->statut == 5) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DetailRefusCP') . '</td>';
                    print '<td>' . $cp->detail_refuse . '</td>';
                    print '</tr>';
                }

                // Description
                if (!$edit) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DescCP') . '</td>';
                    print '<td>' . nl2br($cp->description) . '</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>' . $langs->trans('DescCP') . '</td>';
                    print '<td><textarea name="description" id="description" class="flat" rows="' . ROWS_3 . '" cols="70">' . $cp->description . '</textarea></td>';
                    print '</tr>';
                }

                // Type de demande de congé:
                print '<tr>';
                print '<td>Type de congés</td>';
                if (!$edit) {
                    print '<td>' . SynopsisHoliday::$typesConges[$cp->type_conges] . '</td>';
                } else {
                    print '<td>';
                    print '<input type="checkbox" name="is_exception" id="is_exception" style="margin-right: 10px"';
                    if ($cp->type_conges == 1)
                        print ' checked="checked"';
                    print '/>';
                    print '<label for="is_rtt">Il s\'agit d\'une demande d\'absence exceptionnelle</label>';
                    print '</span><br/>';
                    print '<span>';
                    print '<input type="checkbox" name="is_rtt" id="is_rtt" style="margin-right: 10px"';
                    if ($cp->type_conges == 2)
                        print ' checked="checked"';
                    print '/>';
                    print '<label for="is_rtt">Il s\'agit d\'une demande de RTT</label>';
                    print '</span>';
                    print '</td>';
                }
                print '</tr>';
                print '<tr>';
                print '<td>Nombre de rdv sur la période</td>';
                $planned_rdvs = $cp->fetchRDV();
                $nb = count($planned_rdvs);
                print '<td' . ($nb > 0 ? ' class="redT"' : '') . '>' . $nb . ' ';

                foreach ($planned_rdvs as $id_rdv) {
                    $ac = new ActionComm($db);
                    $ac->fetch($id_rdv);
                    $nomUrl = $ac->getNomUrl(1);
                    print $nomUrl . ' ';
                }

                print '</td>';
                print '</tr>';

                print '</tbody>';
                print '</table>' . "\n";

                if ($edit) {
                    // js - sélection d'un seul checkbox RTT ou absence exceptionnelle
//                    print '<script type="text/javascript">';
//                    print '$(document).ready(function() {';
//                    print "$('#is_exception').change(function() {";
//                    print "if ($(this).prop('checked') && $('#is_rtt').prop('checked'))";
//                    print "$('#is_rtt').removeAttr('checked');";
//                    print "});";
//                    print "$('#is_rtt').change(function() {";
//                    print "if ($(this).prop('checked') && $('#is_exception').prop('checked'))";
//                    print "$('#is_exception').removeAttr('checked');";
//                    print "});";
//                    print '});';
//                    print '</script>';
                }
                print '<br><br>';

                // Info workflow
                print '<table class="border" width="50%">' . "\n";
                print '<tbody>';
                print '<tr class="liste_titre">';
                print '<td colspan="2">' . $langs->trans("InfosWorkflowCP") . '</td>';
                print '</tr>';

                if (!empty($cp->fk_user_create)) {
                    $userCreate = new User($db);
                    $userCreate->fetch($cp->fk_user_create);
                    print '<tr>';
                    print '<td>' . $langs->trans('RequestByCP') . '</td>';
                    print '<td>' . $userCreate->getNomUrl(1) . '</td>';
                    print '</tr>';
                }

                if (!$edit) {
                    print '<tr>';
                    print '<td width="50%">' . $langs->trans('ReviewedByCP') . '</td>';
                    print '<td>' . $valideur->getNomUrl(1) . '</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td width="50%">' . $langs->trans('ReviewedByCP') . '</td>';
                    // Liste des utiliseurs du groupes choisi dans la config
                    $idGroupValid = $cp->getConfCP('userGroup');

                    $validator = new UserGroup($db, $idGroupValid);
                    $valideur = $validator->listUsersForGroup('', 1);

                    print '<td>';
                    $form->select_users($cp->fk_validator, "valideur", 1, "", 0, $valideur, '');
                    print '</td>';
                    print '</tr>';
                }

                print '<tr>';
                print '<td>' . $langs->trans('DateCreateCP') . '</td>';
                print '<td>' . dol_print_date($cp->date_create, 'dayhour') . '</td>';
                print '</tr>';
                if ($cp->statut == 3 || $cp->statut == 6) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateValidCP') . '</td>';
                    print '<td>' . dol_print_date($cp->date_valid, 'dayhour') . '</td>';
                    print '</tr>';
                }
                if ($cp->statut == 6) {
                    print '<tr>';
                    print '<td>Date d\'approbation par le DRH</td>';
                    print '<td>' . dol_print_date($cp->date_drh_valid, 'dayhour') . '</td>';
                    print '</tr>';
                }
                if ($cp->statut == 4) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateCancelCP') . '</td>';
                    print '<td>' . dol_print_date($cp->date_cancel, 'dayhour') . '</td>';
                    print '</tr>';
                }
                if ($cp->statut == 5) {
                    print '<tr>';
                    print '<td>' . $langs->trans('DateRefusCP') . '</td>';
                    print '<td>' . dol_print_date($cp->date_refuse, 'dayhour') . '</td>';
                    print '</tr>';
                }
                // Indication du remplaçant:
                if ($cp->statut != 4 && $cp->statut != 5 && !empty($cp->fk_substitute)) {
                    print '<tr>';
                    print '<td>Remplaçant désigné</td>';
                    if ($cp->fk_substitute < 0)
                        print '<td>Aucun</td>';
                    else {
                        $substitute = new User($db);
                        $substitute->fetch($cp->fk_substitute);
                        print '<td>' . $substitute->getNomUrl(1) . '</td>';
                    }
                    print '</tr>';
                }

                print '</tbody>';
                print '</table>';

                if ($action == 'edit' && $cp->statut == 1) {
                    print '<br/><div align="center">';
                    if ($canedit && $cp->statut == 1) {
                        print '<input type="submit" value="' . $langs->trans("UpdateButtonCP") . '" class="butAction">';
                    }
                    print '</div>';

                    print '</form>';
                }

                if (is_numeric($cp->fk_user)) {
                    if ($cp->statut != 4 && $cp->statut != 5 &&
                            ($user->id == $drhUserId || $user->id == $cp->fk_validator || $user->id == $userRequest->fk_user)) {
                        // Choix du remplaçant du même service (DRH, responsable ou valideur seulement):
                        print '<br/><br/>' . "\n\n";
                        print '<table class="border" width="50%">' . "\n";
                        print '<tbody>' . "\n";
                        print '<tr class="liste_titre">';
                        print '<td colspan="3">Options réservées aux responsables hiérarchiques</td>';
                        print '</tr>' . "\n";
                        print '<tr>';
                        print '<td width="50%">Remplaçant du même service</td>' . "\n";
                        print '<td><form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id'] . '&action=save_substitute">' . "\n";

                        $tabExclude = array($cp->fk_user);
                        $req = "SELECT DISTINCT(h2.`fk_user`) as idUser FROM `" . MAIN_DB_PREFIX . "holiday` h1, `" . MAIN_DB_PREFIX . "holiday` h2 WHERE h1.rowid = " . $cp->id . " AND ("
                                . "(h2.`date_debut` >= h1.`date_debut` AND h2.`date_debut` <= h1.`date_fin`) || "//date deb dans la periode
                                . "(h2.`date_fin` <= h1.`date_fin` AND h2.`date_fin` >= h1.`date_debut`) || "//date fin dans la periode
                                . "(h2.`date_debut` <= h1.`date_debut` AND h2.`date_fin` >= h1.`date_fin`)"//date a cheval
                                . ") AND h2.`statut` = 6 AND h2.fk_user > 0";
//                                . " AND u.fk_user!=".$user->fk_user; // avec le même responsable

                        $sql = $db->query($req);
                        while ($ln = $db->fetch_object($sql))
                            $tabExclude[] = $ln->idUser;


                        // Exclure les utilisateurs qui ne partagent pas le même
                        // responsable que l'utilisateur qui fait la demande
                        $tabExclude2 = array();
                        $req = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user";
                        $req .= " WHERE fk_user != " . $userRequest->fk_user;
                        $req .= " OR fk_user IS NULL";
                        $sql = $db->query($req);
                        while ($ln = $db->fetch_object($sql))
                            $tabExclude2[] = $ln->rowid;

                        $excludes = array_unique(array_merge($tabExclude, $tabExclude2), SORT_REGULAR);

                        print $form->select_dolusers((isset($cp->fk_substitute) ? $cp->fk_substitute : -1), 'substitute_user_id', 1, $excludes, null, null, null, null, null, null, null, null, null, null, 1);
                        print '</td>' . "\n";
                        print '<td><input type="submit" value="Enregistrer" class="butAction"></td>';
                        print '</form>';



                        // Choix du remplaçant TOUS SERVICES (DRH, responsable ou valideur seulement):
//                        print '<br/><br/>' . "\n\n";
                        print '<tr>';
                        print '<td width="50%">Remplaçant d\'un service différent</td>' . "\n";
                        print '<td><form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id'] . '&action=save_substitute">' . "\n";
                        print $form->select_dolusers((isset($cp->fk_substitute) ? $cp->fk_substitute : -1), 'substitute_user_id_other_service', 1, $tabExclude, null, null, null, null, null, null, null, null, null, null, 1);
                        print '</td>' . "\n";
                        print '<td><input type="submit" value="Enregistrer" class="butAction"></td>';
                        print '</form>';
                        print '</tr>' . "\n";
                        print '</tbody>';
                        print '</table>';
                    }
                }

                dol_fiche_end();

                if (!$edit) {
                    print '<div class="tabsAction">';

                    // Boutons d'actions
                    if ($cp->statut == 1) {
                        if ($canedit)
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=edit" class="butAction">' . $langs->trans("EditCP") . '</a>';
                        if (($user->id == $drhUserId) && is_array($cp->fk_user)) {
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=groupCPValidate" class="butAction">' . $langs->trans("Validate") . '</a>';
                        } else if (($canedit && ($user->id == $cp->fk_user)) || $user->id == $drhUserId || $user->rights->holiday->write_all) {
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=sendToValidate" class="butAction">' . $langs->trans("SendRequestCP") . '</a>';
                        }
                        if ($user->id == $drhUserId || $droitAll || $user->rights->holiday->delete || $user->id == $cp->fk_user)
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=delete" class="butActionDelete">' . $langs->trans("DeleteCP") . '</a>';
                    } else if ($cp->statut == 2) {
                        if ($user->id != $drhUserId && $user->id == $cp->fk_validator) {
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=valid" class="butAction">' . $langs->trans("Approve") . '</a>';
                            print '<a href="card.php?id=' . $_GET['id'] . '&action=refuse" class="butAction">' . $langs->trans("ActionRefuseCP") . '</a>';
                        }
                    }
                    if (($user->id == $drhUserId) &&
                            (($cp->statut == 2) || ($cp->statut == 3))) {
                        print '<a href="card.php?id=' . $_GET['id'] . '&action=drhValid" class="butAction">' . $langs->trans("Approve") . ' DRH</a>';
                        print '<a href="card.php?id=' . $_GET['id'] . '&action=drhRefuse" class="butAction">' . $langs->trans("ActionRefuseCP") . '</a>';
                    }
                    if (($user->id == $cp->fk_validator || $user->id == $cp->fk_user || $user->id == $drhUserId) &&
                            ($cp->statut == 2 || $cp->statut == 3 || $cp->statut == 6)) { // Status validated or approved
                        if (($cp->date_debut > dol_now()) || $user->admin || $user->id == $drhUserId) {
                            if (is_array($cp->fk_user) && ($user->id == $drhUserId || $user->admin))
                                print '<a href="card.php?id=' . $_GET['id'] . '&action=group_cancel" class="butAction"" class="butActionRefused">' . $langs->trans("ActionCancelCP") . '</a>';
                            else
                                print '<a href="card.php?id=' . $_GET['id'] . '&action=cancel" class="butAction">' . $langs->trans("ActionCancelCP") . '</a>';
                        }
//                        else print '<a href="#" class="butActionRefused" title="' . $langs->trans("HolidayStarted") . '">' . $langs->trans("ActionCancelCP") . '</a>'; // lien inutile
                    }
                    print '</div>';
                }
            } else {
                print '<div class="tabBar">';
                print $langs->trans('ErrorUserViewCP');
                print '<br /><br /><input type="button" value="' . $langs->trans("ReturnCP") . '" class="butAction" onclick="history.go(-1)" />';
                print '</div>';
            }
        } else {
            print '<div class="tabBar">';
            print $langs->trans('ErrorIDFicheCP');
            print '<br /><br /><input type="button" value="' . $langs->trans("ReturnCP") . '" class="butAction" onclick="history.go(-1)" />';
            print '</div>';
        }
    }
}

// End of page
llxFooter();

if (is_object($db))
    $db->close();
