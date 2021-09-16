<?php

class Bimp_ImportPaiement extends BimpObject{
    var $id_mode_paiement = 'VIR';
    function create(&$warnings = array(), $force_create = false) {
        if(isset($_FILES['file']) && $_FILES['file']['name'] != ''){
            $errors = parent::create($warnings, $force_create);
            if(!count($errors)){
                $file_dir = $this->getFilesDir();

                $oldName =  $_FILES['file']['name'];
                $name = $this->getFileName();
                $_FILES['file']['name']= $name;
                if(file_exists($file_dir.$_FILES['file']['name']))
                        $errors[] = 'Fichier '.$_FILES['file']['name']. ' existe déja';
                else{
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                    dol_add_file_process($file_dir, 0, 0, 'file');


                    if(!count($errors)){
                        $datas = file_get_contents($file_dir.$_FILES['file']['name']);

                        $this->traiteData($datas, $errors);

                    }
                }
            }
        }
        else
            $errors[] = 'Fichier introuvable';
        
        
        
        return $errors;
    }
    
    function actionCreate_paiement($data, &$success){
        global $user;
        $success = 'Paiment(s) crée(s)';
        $errors = $wanings = array();
        $list = $this->getChildrenObjects('lignes', array('traite'=>0, 'type'=>'vir'));
        foreach($list as $child){
            $errorsLn = array();
            $totP = $child->getData('price');
            if($child->ok == true){
                foreach($child->getData('factures') as $idFact){
                    $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idFact); 
                    if($fact->getData('remain_to_pay') < $totP)
                        $montant = $fact->getData('remain_to_pay');
                    else
                        $montant = $totP;
                    $totP -= $montant;
                    if($montant > 0){
                        if (!class_exists('Paiement')) {
                            require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
                        }
                        $p = new Paiement($this->db->db);
                        $p->datepaye = dol_now();
                        $p->amounts = array(
                            $idFact => $montant
                        );
                        $p->paiementid = (int) dol_getIdFromCode($this->db->db, $this->id_mode_paiement, 'c_paiement');
                        $p->facid = (int) $idFact;

                        if ($p->create($user) < 0) {
                            $msg = 'Echec de l\'ajout à la facture du paiement n°' . $n;
                            $msg .= ' (' . BC_VentePaiement::$codes[$code]['label'] . ': ' . BimpTools::displayMoneyValue($montant, 'EUR') . ')';
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), $msg);
                        } else {
                            if (!empty($conf->banque->enabled)) {
                                if ($p->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $this->getData('banque'), '', '') < 0) {
                                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'Echec de l\'ajout du paiement n°' . $p->id . ' au compte bancaire ' . $this->getData('banque'));
                                }
                            }
                        }
                    }
                    else{
                        $errorsLn[] = 'Impossible de créer le paiment : '.$montant;
                    }
                    $fact->checkIsPaid();
                }
                if(!count($errorsLn))
                    $child->updateField('traite', 1);
                $errors = BimpTools::merge_array($errors, $errorsLn);
            }
        }
        
        return array('errors'=>$errors, 'warnings'=>$wanings);
    }
    
    function getFileName(){
        return 'origine'.$this->id.'.csv';
    }
    
    
    function traiteData($datas, $errors){
        $separateurecriture = '04178';
                    
        $tabLn = explode($separateurecriture, $datas);
        
        unset($tabLn[0]);
        
        foreach ($tabLn as $ln){
            $ln = $separateurecriture.$ln;
            
            
            $line = BimpCache::getBimpObjectInstance($this->module, 'Bimp_ImportPaiementLine');
            $line->set('id_import', $this->id);
            $line->set('data', $ln);
            $errors = BimpTools::merge_array($errors, $line->create());
            
           
        }
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
                    'label'   => 'Traiter les paiements rataché',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('create_paiement')
                );
            }
        }

        return $buttons;
    }
}
