<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';
require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/BV_Lib.php';

class BimpValidationCronExec extends BimpCron
{

    public function sendRappels()
    {
        if (in_array(date('N'), array(7))) {
            $this->output = 'Pas d\'éxécution le dimanche';
            return 0;
        }
        
        $this->current_cron_name = 'Rappel demandes de validation en attente';
        $this->output = '';
        
        $errors = array();

        $bdb = BimpCache::getBdb();

        $rows = $bdb->getRows('bv_demande', 'status = 0', null, 'array', array('id'));

        $users_demandes = array();

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $demande = BimpCache::getBimpObjectInstance('bimpvalidation', 'BV_Demande', (int) $r['id']);

                if (BimpObject::objectLoaded($demande)) {
                    if (!isset($users_demandes[(int) $demande->getData('id_user_affected')])) {
                        $users_demandes[(int) $demande->getData('id_user_affected')] = array();
                    }

                    $users_demandes[(int) $demande->getData('id_user_affected')][] = $demande;
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $bdb->err();
        }

        if (empty($users_demandes)) {
            $this->output .= 'Aucune demande de validation à traiter';
        } else {
            foreach ($users_demandes as $id_user => $demandes) {
                $email = $bdb->getValue('user', 'email', 'rowid = ' . $id_user);
                if (!$email) {
                    $errors[] = 'Aucun adresse email pour user #' . $id_user;
                    continue;
                }

                $email = BimpTools::cleanEmailsStr($email);
                $nb_demandes = count($demandes);
                $s = ($nb_demandes > 1 ? 's' : '');
                $subject = 'Rappel : ' . $nb_demandes . " demande$s de validation en attente d'acceptation";
                $msg = "Bonjour,<br/><br/>";
                $msg .= "$nb_demandes demande$s de validation sont tojours en attente de traitement : <br/><br/>";

                foreach ($demandes as $demande) {
                    $obj = $demande->getObjInstance();
                    $msg .= " - Validation " . $demande->displayValidationType();

                    if (BimpObject::objectLoaded($obj)) {
                        $url = $obj->getUrl();
                        $msg .= ' <a href="' . $url . '">' . $obj->getLabel('of_the') . ' ' . $obj->getRef() . '</a><br/><br/>';
                    }
                }

                if (!mailSyn2($subject, $email, '', $msg)) {
                    $errors[] = 'Echec envoi mail à ' . $email . " ($nb_demandes validation$s)";
                } else {
                    $this->output .= $email . ' - Envoi ok ' . " ($nb_demandes validation$s)<br/>";
                }
            }
        }

        if (count($errors)) {
            $this->output .= "<br/>Erreurs <pre>" . print_r($errors, 1) . "</pre><br/>";
        }

        return 0;
    }

    public function checkAffectedUsers()
    {
        $this->current_cron_name = 'Vérif disponibilité utilisateurs affectés aux demandes de validation';
        $this->output = '';
        $errors = array();
        
        if (in_array(date('N'), array(6,7))) {
            $this->output = 'Pas d\'éxécution le week-end';
            return 0;
        }

        $bdb = BimpCache::getBdb();

        $rows = $bdb->getRows('bv_demande', 'status = 0', null, 'array', array('id'));
        $n = 0;

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $demande = BimpCache::getBimpObjectInstance('bimpvalidation', 'BV_Demande', (int) $r['id']);

                if (BimpObject::objectLoaded($demande)) {
                    $n++;
                    $demande_errors = $demande->checkAffectedUser(true);

                    if (count($demande_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($demande_errors, 'Demande #' . $r['id']);
                    }
                }
            }
        } else {
            $errors[] = 'Erreur SQL - ' . $bdb->err();
        }

        $this->output .= "$n demande(s) traitée(s)<br/>";

        if (count($errors)) {
            $this->output .= "<br/>Erreurs <pre>" . print_r($errors, 1) . "</pre><br/>";
        } else {
            $this->output .= 'Aucune erreur';
        }

        return 0;
    }
}
