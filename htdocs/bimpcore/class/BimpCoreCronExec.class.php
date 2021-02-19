<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpCoreCronExec
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function bimpDailyChecks()
    {
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
                                $cc = 'f.martinez@bimp.fr';
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

        return 'OK';
    }
}
