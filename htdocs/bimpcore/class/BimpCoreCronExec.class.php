<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCoreCronExec extends BimpCron
{

    public function bimpDailyChecks()
    {
        $this->current_cron_name = 'Vérifs quotidiennes BimpCore';
        $bdb = new BimpDb($this->db);

        // Vérifs des factures en financement impayées à 30 jours. 
        $modes = array();

        $rows = $bdb->getRows('c_paiement', 'code IN(\'FIN\',\'SOFINC\',\'FINAPR\',\'FLOC\',\'FINLDL\',\'FIN_YC\')', null, 'array', array('id'));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $modes[] = $r['id'];
            }
        }

        $dt_lim = new DateTime();
        $dt_lim->sub(new DateInterval('P30D'));

        $where = 'paye = 0 AND fk_statut = 1 AND paiement_status < 2 AND fk_mode_reglement IN(' . implode(',', $modes) . ') AND date_lim_reglement < \'' . $dt_lim->format('Y-m-d') . '\' AND datec > \'2019-06-30\'';
        $rows = $bdb->getRows('facture', $where, null, 'array', array('rowid'));

        if (is_array($rows)) {
            $now = date('Y-m-d');
            foreach ($rows as $r) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

                if (BimpObject::objectLoaded($facture)) {
                    $fac_date_lim = $facture->getData('date_lim_reglement');

                    if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string) $fac_date_lim) && strtotime($fac_date_lim) > 0) {
                        $date_check = new DateTime($fac_date_lim);

                        while ($date_check->format('Y-m-d') <= $now) {
                            if ($date_check->format('Y-m-d') == $now) {
                                $soc = $facture->getChildObject('client');

                                // Envoi e-mail:
                                $cc = '';
                                $subject = 'Facture financement impayée - ' . $facture->getRef();

                                if (BimpObject::objectLoaded($soc)) {
                                    $subject .= ' - Client: ' . $soc->getRef() . ' - ' . $soc->getName();
                                }

                                $comms = $bdb->getRows('societe_commerciaux', 'fk_soc = ' . (int) $facture->getData('fk_soc'), null, 'array', array(
                                    'fk_user'
                                ));

                                if (is_array($comms)) {
                                    foreach ($comms as $c) {
                                        $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $c['fk_user']);

                                        if (BimpObject::objectLoaded($commercial)) {
                                            $cc .= ($cc ? ', ' : '') . BimpTools::cleanEmailsStr($commercial->getData('email'));
                                        }
                                    }
                                }

                                $msg = 'Bonjour, ' . "\n\n";
                                $msg .= 'La facture "' . $facture->getLink() . '" dont le mode de paiement est de type "financement" n\'a pas été payée alors que sa date limite de réglement est le ';
                                $msg .= date('d / m / Y', strtotime($fac_date_lim));

                                mailSyn2($subject, 'recouvrementolys@bimp.fr,m.albert@bimp.fr', '', $msg, array(), array(), array(), $cc);
                                break;
                            }

                            $date_check->add(new DateInterval('P15D'));
                        }
                    }
                }
            }
        }

        // Nettoyages des fichiers temporaires
        BimpTools::cleanTempFiles();

        // Vérifs des RDV SAV à annuler:
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        BS_SAV::checkSavToCancel();

        // Vérifs des notifs relances client désactivées. 
        BimpObject::loadClass('bimpcore', 'Bimp_Client');
        Bimp_Client::checkRelancesDeactivatedToNotify();

        // Vérifs des licenses arrivées à échéance: 
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        Bimp_Commande::checkLinesEcheances();

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
        $host = 'ftp-edi.groupe-ldlc.com';
        $port = 21;
        $login = 'bimp-erp';
        $pword = 'Yu5pTR?(3q99Aa';

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
                $ftp_dir = '/FTP-BIMP-ERP/statsapple/' . date('Y') . '/';

                foreach ($result['files'] as $fileName) {
                    if (!ftp_put($ftp, $ftp_dir . $fileName, $local_dir . $fileName)) {
                        $errors[] = 'Echec de l\'envoi du fichier "' . $local_dir . $fileName . '"';
                    }
                }
            }

            ftp_close($ftp);
        }

        if (count($errors)) {
            BimpCore::addlog('Echec de l\'envoi FTP des rapports Apple', Bimp_Log::BIMP_LOG_URGENT, 'bimpcomm', null, array(
                'Erreurs' => $errors
            ));

            $this->output = 'Echec envoi ftp (cf log)';
            return -1;
        }

        return 0;
    }

    public function mailMessageNote()
    {
        $this->current_cron_name = 'Notes non lues';
        BimpObject::loadClass('bimpcore', 'BimpNote');

        BimpNote::cronNonLu();
        $this->output = 'OK';
        return 0;
    }

    public function mailCronErreur()
    {
        $bdb = new BimpDb($this->db);

        $rows = $bdb->getRows('cronjob', '`datenextrun` < DATE_ADD(now(), INTERVAL -1 HOUR) AND status = 1', null, 'array', array('rowid', 'label'));

        $i = 0;
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $i++;
                mailSyn2('Cron en erreur', 'dev@bimp.fr', null, 'Attention, le cron ' . $r['label'] . ' d\'id ' . $r['rowid'] . ' est en erreur...');
            }
        }
        $this->output = $i . ' erreurs';
        return 0;
    }
}
