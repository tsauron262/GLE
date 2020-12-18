<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfichinter/objects/objectInter.class.php';

class Bimp_Fichinter extends ObjectInter {

    public $force_update_date_ln = false;
    public static $dol_module = 'fichinter';
    public $extraFetch = false;
    public static $nature_list = array(
        0 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Installation', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Dépannage', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Télémaintenance', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Formation', 'icon' => 'check', 'classes' => array('info')),
        5 => array('label' => 'Audit', 'icon' => 'check', 'classes' => array('info')),
        6 => array('label' => 'Suivi', 'icon' => 'check', 'classes' => array('info')),
    );
    public static $type_list = array(
        -1 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info'))
    );
    
//    function __construct($module, $object_name) {
//        global $user, $db;
//
//        $this->redirectMode = 4;
//        return parent::__construct($module, $object_name);
//    }

    public function fetch($id, $parent = null) {
        $return = parent::fetch($id, $parent);
        if (!$this->checkLink())
            $this->extra_left .= "<br/><br/><span class='alert-danger alert'>Cette FI n'est liée à aucun objet.</span>";
        $this->warnings[] = 'test';
        return $return;
    }

    public function getExtra($field) {
        if ($field == "di") {
            if ($this->isLoaded() && is_a($this->dol_object, 'Synopsisfichinter')) {
                $return = array();
                $dis = $this->dol_object->getDI();
                require_once DOL_DOCUMENT_ROOT . '/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php';
                $di = new Synopsisdemandeinterv($this->db->db);
                foreach ($dis as $diI) {
                    $di->fetch($diI);
                    $return[] = $di->getNomUrl(1);
                }
                return implode("<br/>", $return);
            }
        } else
            return parent::getExtra($field);
    }

    public function getInstanceName() {
        if ($this->isLoaded()) {
            return $this->getData('ref');
        }

        return ' ';
    }

    public function getCommercialSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a') {
        $joins["commerciale"] = array("table" => "societe_commerciaux", "alias" => "sc", "on" => "sc.fk_soc = " . $main_alias . ".fk_soc");
        $filters["sc.fk_user"] = $value;
    }

    public function displayCommercial() {
        global $user;
        $html = "";
        if ($this->isLoaded() && $this->getData("fk_soc") > 0) {
            $soc = $this->getInstance("bimpcore", "Bimp_Societe");
            $soc->fetch($this->getData("fk_soc"));
            $userT = $this->getInstance("bimpcore", "Bimp_User");
            foreach ($soc->dol_object->getSalesRepresentatives($user) as $userTab) {
                $userT->fetch($userTab['id']);
                $html .= $userT->dol_object->getNomUrl(1);
            }
        }

        return $html;
    }

    public function traiteDate() {
        if ($this->getData("datei") != $this->getInitData("datei") && $this->force_update_date_ln) {
            $lines = $this->getChildrenObjects("lines");
            foreach ($lines as $line) {
                $line->set("datei", $this->getData("datei"));
                $line->update();
            }
        }
    }

    public function update(&$warnings = array(), $force_update = false) {
        $this->traiteDate();

        return parent::update($warnings, $force_update);
    }

    public function getActionsButtons() {
        global $conf, $langs, $user;
        $langs->load('propal');

        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label' => 'Générer le PDF',
                'icon' => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );
            if ($this->getData('fk_statut') == 1) {
                $buttons[] = array(
                    'label' => 'Facturer',
                    'icon' => 'fas_sync',
                    'onclick' => $this->getJsActionOnclick('generateFacture', array(), array())
                );
            }
        }
        return $buttons;
    }

    public function getFactures() {
        $tabFact = array();
        $sql = BimpTools::getSqlSelect(array('fk_target'));
        $sql .= BimpTools::getSqlFrom('element_element');
        $sql .= BimpTools::getSqlWhere(array(
                    'fk_source' => (int) $this->getData('id'),
                    'sourcetype' => 'fichinter',
                    'targettype' => 'facture'));

        $rows = $this->db->executeS($sql);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $facture_already_link = new Facture($this->db->db);
                $facture_already_link->fetch($row->fk_target);
                $tabFact[] = $facture_already_link;
            }
        }
        return $tabFact;
    }

    public function actionGenerateFacture($data, &$success, $errors = array(), $warnings = array()) {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        global $user;

        $new_facture = new Facture($this->db->db);
        $new_facture->date = dol_now();
        $new_facture->socid = $this->getData('fk_soc');
        $success = 'Facture généré avec succès';

        if ($this->isLoaded()) {

            // Check if a facture isn't already linked with this FI
            $tabFact = $this->getFactures();

            if (empty($tabFact)) {
                foreach ($tabFact as $fact) {
                    $url = $fact->getNomUrl();
                    $errors[] = "Cette FI est déjà liée à la facture " . $url;
                }
            } else {

                $new_facture_id = $new_facture->create($user);
                if ($new_facture_id > 0) {

                    // Set line of facture
                    $lines = $this->getChildrenObjects('lines');
                    foreach ($lines as $line) {
                        $desc = $line->getData('desc');
                        $type = $line->getTypeinter_listArray();
                        $type = $type[$line->getData('fk_typeinterv')]['label'];
                        $pu_ht = $line->getData('pu_ht');
                        $qty = $line->getData('qte') * (($line->getData('isForfait'))? 1 : ($line->getData('duration') / 3600));
                        $tx_tva = $line->getData('tx_tva');
                        $new_facture->addline($type . " : ".$desc, $pu_ht, $qty, $tx_tva, null, null, $line->getData('fk_depProduct'), null, null, null, null, null, null, 'HT', 0, 1);
                    }

                    // Create link between FI and new facture
                    $result = $this->db->insert('element_element', array(
                        'fk_source' => (int) $this->getData('id'),
                        'sourcetype' => 'fichinter',
                        'fk_target' => (int) $new_facture_id,
                        'targettype' => 'facture'), true);

                    if (!$result > 0)
                        $errors[] = "La liaison FI-facture ne s'est pas effectuée correctement";

                    $success = 'Facture généré avec succès';

                    $url = DOL_URL_ROOT . '/compta/facture/card.php?facid=' . $new_facture->id;
                    $success_callback = 'window.open(\'' . $url . '\', \'_blank\');';
                } else {
                    $errors = BimpTools::merge_array($errors, $new_facture->errors);
                }
            }
        }

        return array(
            'errors' => $errors,
            'warnings' => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function checkLink() {
        if ($this->getData('fk_commande') > 0 or $this->getData('fk_contrat') > 0 or count($this->getFactures()) > 0)
            return 1;
        return 0;
    }

    public function display_fact() {
        $return = array();
        $tab = $this->getFactures();
        foreach($tab as $fact)
            $return[] = $fact->getNomUrl(1);
        return implode("<br/>", $return);
    }
        
    public function createFromContrat($contrat, $data) {
        global $user;
        
        $fi = $this->getInstance('bimpfichinter', 'Bimp_Fichinter');
        
        $fi->set('fk_contrat', $contrat->id);
        $fi->set('fk_statut', 0);
        $fi->set('fk_user_author', $user->id);
        $fi->set('note_private', $data['private']);
        $fi->set('note_public', $data['public']);
        $fi->set('fk_soc', $contrat->getData('fk_soc'));
        
        return $fi->create();
    }
  

}
