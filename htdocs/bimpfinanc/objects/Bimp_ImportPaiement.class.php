<?php

class Bimp_ImportPaiement extends BimpObject
{

    var $id_mode_paiement = 'VIR';

    function create(&$warnings = array(), $force_create = false)
    {

        if (isset($_FILES['file']) && $_FILES['file']['name'] != '') {

            $errors = parent::create($warnings, $force_create);

            if (!count($errors)) {
                $file_dir = $this->getFilesDir();

                $oldName = $_FILES['file']['name'];
                $name = $this->getFileName();
                $_FILES['file']['name'] = $name;
                if (file_exists($file_dir . $_FILES['file']['name']))
                    $errors[] = 'Fichier ' . $_FILES['file']['name'] . ' existe déja';
                else {
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                    dol_add_file_process($file_dir, 0, 0, 'file');

                    if (!count($errors)) {
                        $datas = file_get_contents($file_dir . $_FILES['file']['name']);

                        $this->traiteData($datas, $errors);
                    }
                }
            }
        } else
            $errors[] = 'Fichier introuvable';



        return $errors;
    }
    
    function create_paiement_from_list($list){
        global $user;
        $errors = array();
        if (!class_exists('Paiement')) {
            require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
        }
        foreach ($list as $child) {
            $errorsLn = array();
            $totP = $child->getData('price');
            
            if ($child->ok == true) {
                $p = new Paiement($this->db->db);

                if ((string) $child->getData('date')) {
                    $p->datepaye = strtotime($child->getData('date'));
                } else {
                    $p->datepaye = dol_now();
                }

                $p->ref = $child->getData('num');

                $p->amounts = array();
                
                foreach ($child->getData('factures') as $idFact) {
                    $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idFact);
                    if (($fact->getData('remain_to_pay')+0.10) < $totP)
                        $montant = $fact->getData('remain_to_pay');
                    else
                        $montant = $totP;
                    $totP -= $montant;
                    if ($montant > 0) {
                        $p->amounts[$idFact] = $montant;
                    } else {
                        $errorsLn[] = 'Impossible de créer le paiment facture '.$fact->getLink().' : ' . $montant.' reste a payer '.$fact->getData('remain_to_pay');
                    }
                }
                $p->paiementid = (int) dol_getIdFromCode($this->db->db, $this->id_mode_paiement, 'c_paiement');
//                        $p->facid = (int) $idFact;

                if ($p->create($user) < 0) {
                    $msg = 'Echec de l\'ajout à la facture du paiement de ' . $montant;
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), $msg);
                } else {
                    global $conf;
                    if (!empty($conf->banque->enabled)) {
                        if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $this->getData('banque'), '', '') < 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du paiement n°' . $p->id . ' au compte bancaire ' . $this->getData('banque'));
                        }
                        foreach ($child->getData('factures') as $idFact) {
                            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idFact);
                            $fact->checkIsPaid();
                        }
                        
                    }
                }
                
                
                
                if (!count($errorsLn))
                    $child->updateField('traite', 1);
                $errors = BimpTools::merge_array($errors, $errorsLn);
            }
        }
        return $errors;
    }

    function actionCreate_paiement($data, &$success)
    {
        $success = 'Paiment(s) crée(s)';
        $wanings = array();
        $list = $this->getChildrenObjects('lignes', array('traite' => 0, 'type' => 'vir'));
        $errors = $this->create_paiement_from_list($list);

        return array('errors' => $errors, 'warnings' => $wanings);
    }
    
    function actionCreate_all_paiement($data, &$success){
        $success = 'Paiment(s) crée(s)';
        $wanings = array();
        $list = BimpCache::getBimpObjectObjects($this->module, 'Bimp_ImportPaiementLine', array('traite' => 0, 'type' => 'vir'));
        $errors = $this->create_paiement_from_list($list);

        return array('errors' => $errors, 'warnings' => $wanings);
    }

    function getFileName()
    {
        return 'origine' . $this->id . '.csv';
    }

    function traiteData($datas, &$errors)
    {
        $separateurecriture = '04178';

        $tabLn = explode($separateurecriture, $datas);

        unset($tabLn[0]);

        foreach ($tabLn as $ln) {
            $ln = $separateurecriture . $ln;

            $line = BimpCache::getBimpObjectInstance($this->module, 'Bimp_ImportPaiementLine');
            $line->set('id_import', $this->id);
            $line->set('data', $ln);
            $errors = BimpTools::merge_array($errors, $line->create());
        }
    }
    
    
    public static function toCompteAttente(){
        $return = array();
        $list = BimpCache::getBimpObjectObjects('bimpfinanc', 'Bimp_ImportPaiementLine', array('traite' => 0, 'type' => 'vir', 'num' => ''));
        foreach ($list as $payin){
            $num = BimpTools::getNextRef('Bimp_ImportPaiementLine', 'num', 'PAYNI{AA}{MM}-', 5);
            $payin->updateField('num', $num);
            $return[] = array('num' => $num, 'amount' => $payin->getData('price'), 'date' => $payin->getData('date'), 'name' => $payin->getData('name'), 'id' => $payin->id);
        }
        return $return;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ((int) $this->getData('status') === 0) {
            return 1;
        }

        $errors[] = 'Cette commission n\'est plus au statut "brouillon"';

        return 0;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('validate')) {
            if ($this->canSetAction('validate')) {
                $buttons[] = array(
                    'label'   => 'Traiter les paiements rattachés',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('create_paiement')
                );
            }
        }

        return $buttons;
    }
}
