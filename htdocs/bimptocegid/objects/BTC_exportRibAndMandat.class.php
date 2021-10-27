<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/BTC_export.class.php';

class BTC_exportRibAndMandat extends BTC_export {
    
    private $structure_rib;
    private $structure_mandat;
    
    public function export_rib_exported(int $id_rib) {
        $errors = [];
        $rib = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_SocBankAccount", $id_rib);
        $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $rib->getData('fk_soc'));
        if(!$rib->isValid()) {
            return $this->printRIBtra($rib, $client);
        } else {
            mailSyn2("Compta RIB", 'al.bernard@bimp.fr', null, $rib->getNomUrl() . " non valide");
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
    
    private function printRIBtra(Bimp_SocBankAccount $rib, Bimp_Societe $client):string {
        $this->structure_rib = Array(
            "FIXE" => $this->sizing("***", 3),
            "IDENTIFIANT" => $this->sizing("RIB", 3),
            "AUXILIAIRE" => $this->sizing($client->getData('code_compta'), 17),
            "NUMERORIB" => $this->sizing('', 6),
            "PRINCIPAL" => $this->sizing(($rib->getData("default_rib") ? 'X' : '-'),1),
            "ETABBQ" => $this->sizing($rib->getData('code_banque'), 5),
            "GUICHET" => $this->sizing($rib->getData('code_guichet'),5),
            "NUMEROCOMPTE" => $this->sizing($rib->getData('number'), 11),
            "CLERIB" => $this->sizing($rib->getData('cle_rib'), 2),
            "DOMICILIATION" => $this->sizing($this->suppr_accents($rib->getData('domiciliation')), 24),
            "VILLE" => $this->sizing($this->suppr_accents(""), 35),
            "PAYS" => $this->sizing('', 3),
            "DEVISE" => $this->sizing('', 3),
            "CODEBIC" => $this->sizing($rib->getData('bic'), 35),
            "SOCIETE" => $this->sizing("", 3),
            "SALAIRE" => $this->sizing("", 1),
            "ACOMPTE" => $this->sizing("", 1),
            "FRAISPROF" => $this->sizing("", 1),
            "CODEIBAN" => $this->sizing($rib->getIban(false), 70),
            "NATECO" => $this->sizing('', 3),
            "TYPEPAYS" => $this->sizing('', 1),
            "ETABBQ_1" => $this->sizing($rib->getData('code_banque'), 8),
            "NUMEROCOMPTE_1" => $this->sizing($rib->getData('number'), 20)
        );
        return (string) implode("", $this->structure_rib) . "\n";
    }
    
    /**
     * @rôle Retourner la ligne TRA du mandat de prélèvement correspondante au RIB envoyé
     * @param Bimp_SocBankAccount $rib
     * @param Bimp_Societe $client
     * @return string
     */
    
    private function printMANDATtra(Bimp_SocBankAccount $rib, Bimp_Societe $client, Bimp_Facture $facture):string {
        $date = new DateTime($rib->getData('datec'));
        $this->structure_mandat = Array(
            "FIXE" => $this->sizing("***", 3),
            "IDENTIFIANT" => $this->sizing("MDT", 3),
            "ICS" => $this->sizing('FR02ZZZ008801', 35),
            "RUM" => $this->sizing($rib->getData('rum'), 35),
            "LIBELLE" => $this->sizing(strtoupper($this->suppr_accents($client->getName())), 35),
            "IBAN" => $this->sizing(str_replace(" ", "", $rib->getIban(false)), 70),
            "BIC" => $this->sizing($rib->getData('bic'), 35),
            "GENERAL" => $this->sizing("", 17),
            "AUXILIAIRE" => $this->sizing($client->getData('code_compta'), 17),
            "PAIEMENT" => $this->sizing($this->recurrentORponctuel($facture->getData('ef_type')), 3),
            "TYPE" => $this->sizing($this->parORpro($client), 3),
            "STATUT" => $this->sizing("1FI", 3),
            "DATECREATION" => $this->sizing($date->format('dmY'), 8),
            "DATEENVOICLI" => $this->sizing("", 8),
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
        return ($secteur == "CT" || $secteur == "CTC" || $secteur == "CTE") ? '1PR' : '2PP';        
    }
    
    private function parORpro(Bimp_Societe $client):string {
        return ($client->getData('type') == 8) ? '1PD' : '2PI';
    }
    
}