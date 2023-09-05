<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/BTC_export.class.php';

class BTC_exportRibAndMandat extends BTC_export {
    
    private $structure_rib;
    private $structure_mandat;
    
    public function export_rib_exported(int $id_rib, $errors = []) {
        $rib = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount", $id_rib);
        $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $rib->getData('fk_soc'));
        if($rib->isValid($errors)) {
            $rib->updateField('exported', 1);
            return $this->printRIBtra($rib, $client);
        } else {
            BimpTools::mailGrouper(BimpCore::getConf('devs_email'), null, $rib->getNomUrl() . " non valide");
        }
    }
    
    /**
     * 
     * @param Bimp_Facture $facture
     * @param Bimp_Societe $client
     * @return string
     */
    
    public function export_rib(Bimp_Facture $facture, Bimp_Societe $client):string {
        $errors = [];
        $rib = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount", $facture->getData('rib_client'));
        
        return (!$rib->getData('exported')) ? $this->printRIBtra($rib, $client, $facture) : "";
    }
    
    public function passTo_exported(Bimp_Facture $facture){
        $rib = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount", $facture->getData('rib_client'));
        $rib->updateField('exported', 1);
    }
    
    /**
     * 
     * @param Bimp_Facture $facture
     * @param Bimp_Societe $client
     * @return string
     */
    
    public function export_mandat(Bimp_Facture $facture, Bimp_Societe $client):string {
        $errors = [];
        $rib = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount", $facture->getData('rib_client'));

        return (!$rib->getData('exported')) ? $this->printMANDATtra($rib, $client, $facture) : "";
    }
    
    /**
     * @rôle Retourner la ligne TRA correspondante au RIB envoyé
     * @param Bimp_SocBankAccount $rib
     * @param Bimp_Societe $client
     * @return string
     */
    
    private function champTra($val, $size, $def = 'X'){
        if($val == '')
            $val = $def;
        return $this->sizing($val, $size);
    }
    
    private function printRIBtra(Bimp_SocBankAccount $rib, Bimp_Societe $client):string {
        $this->structure_rib = Array(
            "FIXE" => $this->champTra("***", 3),
            "IDENTIFIANT" => $this->champTra("RIB", 3),
            "AUXILIAIRE" => $this->champTra($client->getData('code_compta'), 17),
            "NUMERORIB" => $this->champTra('', 6, ''),
            "PRINCIPAL" => $this->champTra(($rib->getData("default_rib") ? 'X' : '-'),1),
            "ETABBQ" => $this->champTra($rib->getData('code_banque'), 5),
            "GUICHET" => $this->champTra($rib->getData('code_guichet'),5),
            "NUMEROCOMPTE" => $this->champTra($rib->getData('number'), 11),
            "CLERIB" => $this->champTra($rib->getData('cle_rib'), 2),
            "DOMICILIATION" => $this->champTra($this->suppr_accents($rib->getData('domiciliation')), 24),
            "VILLE" => $this->champTra($this->suppr_accents(""), 35),
            "PAYS" => $this->champTra($rib->getCodePays(), 3),
            "DEVISE" => $this->champTra($rib->getDevise(), 3),
            "CODEBIC" => $this->champTra(str_replace(" ", "", $rib->getData('bic')), 35),
            "SOCIETE" => $this->champTra("001", 3),
            "SALAIRE" => $this->champTra("-", 1),
            "ACOMPTE" => $this->champTra("-", 1),
            "FRAISPROF" => $this->champTra("-", 1),
            "CODEIBAN" => $this->champTra($rib->getIban(false), 70),
            "NATECO" => $this->champTra('359', 3),
            "TYPEPAYS" => $this->champTra('', 1, ''),
            "ETABBQ_1" => $this->champTra($rib->getData('code_banque'), 8),
            "NUMEROCOMPTE_1" => $this->champTra($rib->getData('number'), 20)
        );
        return (string) implode("", $this->structure_rib) . "\n";
    }
    
    /**
     * @rôle Retourner la ligne TRA du mandat de prélèvement correspondante au RIB envoyé
     * @param Bimp_SocBankAccount $rib
     * @param Bimp_Societe $client
     * @return string
     */
    
    private function printMANDATtra(Bimp_SocBankAccount $rib, Bimp_Societe $client, Bimp_Facture $facture = null, $force_ef_type = "C"):string {
        
        $ef_type = (is_object($facture)) ? $facture->getData('ef_type') : $force_ef_type;
        
        $date = new DateTime($rib->getData('datec'));
        $this->structure_mandat = Array(
            "FIXE" => $this->sizing("***", 3),
            "IDENTIFIANT" => $this->sizing("MDT", 3),
            "ICS" => $this->sizing(BimpCore::getConf('code_ics'), 35),
            "RUM" => $this->sizing($rib->getData('rum'), 35),
            "LIBELLE" => $this->sizing(strtoupper($this->suppr_accents($client->getName())), 35),
            "IBAN" => $this->sizing(str_replace(" ", "", $rib->getIban(false)), 70),
            "BIC" => $this->sizing(str_replace(" ", "", $rib->getData('bic')), 35),
            "GENERAL" => $this->sizing("", 17),
            "AUXILIAIRE" => $this->sizing($client->getData('code_compta'), 17),
            "PAIEMENT" => $this->sizing($this->recurrentORponctuel($ef_type), 3),
            "TYPE" => $this->sizing($this->parORpro($client), 3),
            "STATUT" => $this->sizing("1FI", 3),
            "DATECREATION" => $this->sizing($date->format('dmY'), 8),
            "DATEENVOICLI" => $this->sizing($date->format('dmY'), 8),
            "DATESIGNATURE" => $this->sizing($date->format('dmY'), 8),
            "DATEMVTENVOI" => $this->sizing($date->format('dmY'), 8),
            "DATEMVTREJET" => $this->sizing("", 8),
            "OLDIBAN" => $this->sizing("", 70),
            "OLDBIC" => $this->sizing("", 35),
            "TEXTELIBRE1" => $this->sizing("", 35),
            "TEXTELIBRE2" => $this->sizing("", 35),
            "TEXTELIBRE3" => $this->sizing("", 35),
            "DATELIBRE1" => $this->sizing("", 8),
            "DATELIBRE2" => $this->sizing("", 8),
            "DATELIBRE3" => $this->sizing("", 8),
            "MONTANTLIBRE1" => $this->sizing("", 20),
            "MONTANTLIBRE2" => $this->sizing("", 20),
            "MONTANTLIBRE3" => $this->sizing("", 20),
            "BOOLEANLIBRE1" => $this->sizing("", 1),
            "BOOLEANLIBRE2" => $this->sizing("", 1),
            "BOOLEANLIBRE3" => $this->sizing("", 1),
            "FERME" => $this->sizing("", 1),
            "DOCUMENT" => $this->sizing("", 255)
        );
        
        return (string) implode("", $this->structure_mandat) . "\n";
    }
    
    
    
    private function recurrentORponctuel(string $secteur):string {
        return /*($secteur == "CT" || $secteur == "CTC" || $secteur == "CTE") ? */'1PR'/* : '2PP'*/;        
    }
    
    private function parORpro(Bimp_Societe $client):string {
        return /*($client->getData('type') == 8) ? */'1PD'/* : '2PI'*/;
    }
    
    // Getters
    protected function getListByExportedStatut($want_exported_statut = 0) {        
        return BimpCache::getBimpObjectObjects("bimpcore", "Bimp_SocBankAccount", ['exported' => $want_exported_statut]);
    }
    

    // Actions
    public function actionExportExportedMandat($data, &$success) {
        
        $warnings = Array();
        $errors = Array();
        
        $export_dir = PATH_TMP  ."/" . 'exportCegid' . '/BY_MY/' ;
        $client = BimpCache::getBimpObjectInstance("bimpcore", 'Bimp_Societe');
        
        $ecriture = $this->head_tra();
        
        if($data['all']) {
            $file = $export_dir . "exported_mandats.tra";
            $list = $this->getListByExportedStatut(1);
            foreach($list as $rib) {
                $client->fetch($rib->getData('fk_soc'));
                $ecriture .= $this->printMANDATtra($rib, $client);
            }
        } else {
            
            if(!$data['rum']) $errors[] = "Numéro du mandat manquant";
            $rib = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_SocBankAccount');
            if(!count($errors)) {
                $file = $export_dir . $data['rum'] . '.tra';
                if($rib->find(['rum' => $data['rum']], 1)) {
                    $client->fetch($rib->getData('fk_soc'));
                    $ecriture .= $this->printMANDATtra($rib, $client);
                } else {
                    $errors[] = "Numéro du RIB faux";
                }
            }
        }

        

        
        if($this->write_tra_w($ecriture, $file)) {
            $success = "Exportés";
        } else {
            $errors[]  = "Erreur inconu";
        }
               
                
        
        return Array(
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        );
        
    }
    
    // display
    public function displayExportedRib() {
        $instance = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount");
        
        $html .= $instance->renderList("default", true, "Liste des RIBs exportés en compta", null, ['exported' => 1]);
                
       $html .= '<span class="btn btn-default" data-trigger="hover" data-placement="top"  data-content="Supprimer la facture" onclick="' . $this->getJsActionOnclick("exportExportedMandat", array(), array("form_name" => "export_one_mandat")) . '")">Exporter les mandats déjà exportés</span>';

        
        return $html;
    }
    
}
