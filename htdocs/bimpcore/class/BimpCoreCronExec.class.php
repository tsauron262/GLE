<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCoreCronExec extends BimpCron
{

    public function bimpDailyChecks()
    {
        $this->current_cron_name = 'Vérifs quotidiennes BimpCore';

        // Nettoyages des fichiers temporaires
        BimpTools::cleanTempFiles();

        // Vérifs des RDV SAV à annuler:
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        BS_SAV::checkSavToCancel();

        // Vérifs des notifs relances client désactivées. 
        if ((int) BimpCore::getConf('use_relances_paiements_clients', null, 'bimpcommercial')) {
            BimpObject::loadClass('bimpcore', 'Bimp_Client');
            Bimp_Client::checkRelancesDeactivatedToNotify();
        }

        $this->output = 'OK';
        return 0;
    }

    public function generateAppleReport()
    {
        $this->current_cron_name = 'Rapports Apple';

        $vente = BimpObject::getInstance('bimpcommercial', 'Bimp_Vente');

        $dt = new DateTime();
        $dow = (int) $dt->format('w');
        if ($dow > 0) {
            $dt->sub(new DateInterval('P' . $dow . 'D')); // Premier dimanche précédent. 
        }
        $date_to = $dt->format('Y-m-d');

        $dt->sub(new DateInterval('P7D'));
        $date_from = $dt->format('Y-m-d');

        $csv_types = array(
            'inventory' => 1,
            'sales'     => 1,
        );

        // Génération des fichiers: 

        $errors = array();
        $result = $vente->generateAppleCSV($csv_types, $date_from, $date_to, false, $errors, true);

        if (!count($errors) && (!isset($result['files']) || empty($result['files']))) {
            $errors[] = 'Echec de la génération des rapports Apple pour une raison inconnue';
        }

        if (count($errors)) {
            BimpCore::addlog('Echec génération auto rapports Apple', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', null, array(
                'Erreurs' => $errors
            ));

            $this->output = 'Echec génération (cf log)';
            return -1;
        }

        // Envoi FTP: 
        $host = BimpCore::getConf('exports_ldlc_ftp_serv');
        $port = 21;
        $login = BimpCore::getConf('exports_ldlc_ftp_user');
        $pword = BimpCore::getConf('exports_ldlc_ftp_mdp');

        $ftp = ftp_connect($host, $port);

        if ($ftp === false) {
            $errors[] = 'Echec de la connexion FTP avec le serveur "' . $host . '"';
        } else {
            if (!ftp_login($ftp, $login, $pword)) {
                $errors[] = 'Echec de la connexion FTP - Identifiant ou mot de passe incorrect';
            } else {
                if (defined('FTP_SORTANT_MODE_PASSIF')) {
                    ftp_pasv($ftp, true);
                } else {
                    ftp_pasv($ftp, false);
                }

                $local_dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/' . date('Y') . '/';
                $ftp_dir = '/'.BimpCore::getConf('exports_ldlc_ftp_dir').'/statsapple/' . date('Y') . '/';

                foreach ($result['files'] as $fileName) {
                    if (!ftp_put($ftp, $ftp_dir . $fileName, $local_dir . $fileName)) {
                        $errors[] = 'Echec de l\'envoi du fichier "' . $local_dir . $fileName . '" vers "'.$ftp_dir . $fileName.'"';
                    }
                }
            }

            ftp_close($ftp);
        }

        if (count($errors)) {
            BimpCore::addlog('Echec de l\'envoi FTP des rapports Apple', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', null, array(
                'Erreurs' => $errors
            ));

            $this->output = 'Echec envoi ftp (cf log)'.print_r($errors,1);
            return -1;
        }

        return 0;
    }

    public function mailNotesNonLues()
    {
        $this->current_cron_name = 'Notes non lues';

        BimpObject::loadClass('bimpcore', 'BimpNote');
        BimpNote::cronNonLu();

        $this->output = 'OK';
        return 0;
    }

    public function mailCronErreur()
    {
        $this->current_cron_name = 'Alerte crons en erreur';

        $bdb = new BimpDb($this->db);

        $rows = $bdb->getRows('cronjob', '`datenextrun` < DATE_ADD(now(), INTERVAL -1 HOUR) AND status = 1', null, 'array', array('rowid', 'label'));

        $i = 0;
        $msg = '';
        if (is_array($rows)) {
            $url_base = DOL_URL_ROOT . '/cron/card.php?id=';
            foreach ($rows as $r) {
                $i++;
                $msg .= '<br/><a href="' . $url_base . $r['rowid'] . '">Cron #' . $r['rowid'] . ' ' . $r['label'] . '</a>';
            }
        }

        if ($msg) {
            $msg = $i . ' Cron(s) en erreur : <br/>' . $msg;
            $this->output = 'Envoi mail pour ' . $i . ' erreur(s) : ';
            if (mailSyn2($i . ' Cron(s) en erreur', BimpCore::getConf('devs_email', 'dev@bimp.fr'), null, $msg)) {
                $this->output .= '[OK]';
            } else {
                $this->output .= '[ECHEC]';
            }
        } else {
            $this->output .= 'Aucun cron en erreur';
        }

        return 0;
    }
}
