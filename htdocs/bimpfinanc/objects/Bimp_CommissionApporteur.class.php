<?php

class Bimp_CommissionApporteur extends BimpObject
{

    CONST STATUS_DRAFT = 0;
    CONST STATUS_VALIDATED = 1;

    public static $status_list = array(
        self::STATUS_DRAFT     => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::STATUS_VALIDATED => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('success'))
    );

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);
        if (!count($errors))
            $errors = BimpTools::merge_array($errors, $this->addNewFatureLine());
        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $this->db->execute("UPDATE `llx_bimp_facture_line` SET `commission_apporteur` = 0  WHERE `commission_apporteur` LIKE '" . $this->id . "-%'");

        return parent::delete($warnings, $force_delete);
    }

    public function addNewFatureLine()
    {
        $errors = array();

        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres', array(), 'position', 'ASC');

        $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

        foreach ($tabsFiltres as $filtreObj) {
            $filtreFact = "SELECT rowid FROM llx_facture WHERE fk_statut IN (1, 2)";
            if($filtreObj->getData('contact_apporteur'))
                $filtreFact .= " AND rowid IN (SELECT DISTINCT(`element_id`) FROM `llx_element_contact` WHERE `fk_c_type_contact` = (SELECT rowid FROM `llx_c_type_contact`  WHERE `code` = 'APPORTEUR' and `source` = 'external' AND `element` = 'facture') AND `fk_socpeople` IN (SELECT `rowid` FROM `llx_socpeople` WHERE `fk_soc` = " . $parent->getData('id_fourn') . ")) ";
            $filters = array(
                'commission_apporteur' => array('0', ''),
                'f.fk_facture'         => array('IN' => $filtreFact)
            );
            $joins = array('f' => array(
                    'table' => 'facturedet',
                    'alias' => 'f',
                    'on'    => 'a.id_line = f.rowid'));

            $filterObj = new BC_FiltersPanel($factureLine);
            $filterObj->setFilters($filtreObj->getData('filter'));
            $errors = BimpTools::merge_array($errors, $filterObj->getSqlFilters($filters, $joins));

            $list = $factureLine->getList($filters, null, null, null, null, 'array', null, $joins);

            foreach ($list as $line) {
                if (!$this->db->execute('UPDATE llx_bimp_facture_line SET commission_apporteur = "' . $this->id . '-' . $filtreObj->id . '" WHERE id_line = ' . $line['id_line']))
                    $errors[] = 'Probléme de MAJ ligne';
            }
        }

        if (!count($errors))
            $errors = $this->calcTotal();

        return $errors;
    }

    public function actionDelLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ligne supprimé de la commission';

        if (isset($data['idLn']) && isset($data['idFiltre'])) {
            $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine', $data['idLn']);
            $apporteur = $this->getParentInstance();
            $filtreObj = $apporteur->getChildObject('filtres', $data['idFiltre']);
            if (is_object(($filtreObj)) && $filtreObj->isLoaded()) {
                $factureLine->updateField('commission_apporteur', 0);
            } else {
                $errors[] = 'Impossible de charger le bon filtre ' . $data['idFiltre'];
            }
        } else {
            $errors[] = 'Probléme de paramétres';
        }
        if (!count($errors))
            $errors = $this->calcTotal();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commission validée';

        $errors = BimpTools::merge_array($errors, $this->updateField('status', self::STATUS_VALIDATED));

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false, $no_triggers = false)
    {

        switch ($field) {
            case 'status':
                if ($value == self::STATUS_VALIDATED) {
                    $errors = $this->beforeValidate();
                    if (count($errors))
                        return $errors;
                }
                break;
        }

        return parent::updateField($field, $value, $id_object, $force_update, $do_not_validate, $no_triggers);
    }

    public function beforeValidate()
    {

        $errors = array();
        $warnings = array();

        $parent = $this->getParentInstance();

        $errors = BimpTools::merge_array($errors, $this->createFactureFourn($parent, $new_facture, $warnings));

        if (count($errors))
            return $errors;

        // Création des lignes
        $filtres = $parent->getChildrenObjects('filtres', array(), 'position', 'ASC');

        $new_facture->startLineTransaction();

        foreach ($filtres as $filtre) {
            if ($filtre->isLoaded()) {
                if ($filtre->getData('commition') != 0) {
                    $lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureLine',
                                                             array('commission_apporteur' => $this->id . '-' . $filtre->id));

                    if (count($lines))
                        $this->createLineLabel($filtre, $new_facture->id);

                    foreach ($lines as $line) {
                        if ((float) $line->qty && (float) $filtre->getData('commition')) {
                            if($filtre->getData('sur_marge'))
                                $amount = (float) $line->getMargePrevue();
                            else        
                                $amount = (float) $line->getTotalHTWithRemises(true);
                            $amount = $amount / (float) $line->qty * (float) $filtre->getData('commition') / 100;

                            if ($amount != 0) {
                                $errors = BimpTools::merge_array($errors, $this->createFactureFournLine($line, $new_facture, $amount));
                                $errors = BimpTools::merge_array($errors, $this->createRevalorisation($line, -$amount));
                            }
                        }
                    }
                }
            } else
                $errors[] = "Erreur avec un des filtres de la commission";
        }

        $new_facture->stopLineTransaction();

        $this->updateField('id_facture_fourn', $new_facture->id);

//        if (count($errors))
//            $errors = BimpTools::merge_array($errors, $new_facture->delete());

        return $errors;
    }

    public function createRevalorisation($line, $amount)
    {

        // Créa nouvelle revalorisation: 
        $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
        $reval_errors = $reval->validateArray(array(
            'id_facture'              => (int) $line->getData('id_obj'),
            'id_facture_line'         => (int) $line->id,
            'type'                    => 'commission_app',
            'qty'                     => (float) $line->qty,
            'amount'                  => (float) $amount,
            'date'                    => date('Y-m-d'),
            'id_commission_apporteur' => $this->id,
            'note'                    => 'Correction du prix d\'achat suite au commissionnement apporteur ' . $this->getLink()
        ));

        if (!count($reval_errors)) {
            $reval_warnings = array();
            $reval_errors = $reval->create($reval_warnings, true);
        }
        return $reval_errors;
    }

    public function createFactureFourn($parent, &$new_facture, &$warnings)
    {

        $fourn = $parent->getChildObject('fourn');

        $new_facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn');

        // TODO rajouter dan imp conf

        $id_entrepot = (int) BimpCore::getConf('default_id_entrepot');

        if (!$id_entrepot) {
            $warnings[] = 'Attention, Aucun entrepôt par défaut défini dans la configuration';
        }

        $errors = $new_facture->validateArray(array(
            'entrepot'          => $id_entrepot,
            'ef_type'           => 'C',
            'fk_soc'            => $fourn->id,
            'fk_cond_reglement' => (int) $new_facture->getCondReglementBySociete(),
            'fk_mode_reglement' => (int) $new_facture->getModeReglementBySociete(),
            'ref_supplier'      => 'commission-' . date('d/m/Y'),
            'datef'             => date('Y-m-d')
        ));

        if (count($errors))
            return $errors;

        $errors = BimpTools::merge_array($errors, $new_facture->create($warnings, true));

        return $errors;
    }

    public function createLineLabel($filter, $id_facture)
    {
        $errors = array();
        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');
        $errors = BimpTools::merge_array($errors, $new_line->validateArray(array(
                            'type'   => ObjectLine::LINE_TEXT,
                            'id_obj' => $id_facture,
        )));

        $s = 's';
        $new_line->desc = $filter->getLabel();
        if (empty($errors))
            $errors = BimpTools::merge_array($errors, $new_line->create($warnings, true));

        return $errors;
    }

    public function createFactureFournLine($line, $new_facture, $amount)
    {

        $errors = array();

//        if ($line->id_product < 1)
//            return $errors;

        $old_fac_parent = $line->getParentInstance();

        $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');

        
        if ($line->id_product > 0){
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $line->id_product);
            $label = $prod->getRef();
        }
        else{
            $dol_line = $line->getChildObject('dol_line');
            $label = $dol_line->getData('description');
        }

        $errors = BimpTools::merge_array($errors, $new_line->validateArray(array(
                            'type'     => ObjectLine::LINE_PRODUCT,
                            'id_obj'   => (int) $new_facture->getData('id'),
                            'editable' => 0,
        )));

        $new_line->pu_ht = $amount;
        $new_line->id_product = 55918;
        $new_line->qty = $line->qty;
        $new_line->desc = $old_fac_parent->getRef() . " " . $label;
        $new_line->tva_tx = 20;

        if (empty($errors))
            $errors = BimpTools::merge_array($errors, $new_line->create($warnings, true));


        return $errors;
    }

    public function calcTotal()
    {
        $errors = array();
        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres', array(), 'position', 'ASC');
        $tot = 0;
        foreach ($tabsFiltres as $filtre) {
            $champ = 'total_ht';
            if($filtre->getData('sur_marge')){
                $champ = '(total_ht - (buy_price_ht * qty))';
                //ajout des reval
                $res = $this->db->executeS("SELECT SUM(amount * qty) as tot FROM llx_bimp_revalorisation WHERE id_facture_line IN (SELECT bf.id FROM llx_bimp_facture_line bf WHERE `commission_apporteur` = '" . $this->id . "-" . $filtre->id . "') AND status IN (0,1) AND type != 'commission_app';");
                $tot += $res[0]->tot * $filtre->getData('commition') / 100;
            }
            $res = $this->db->executeS("SELECT SUM(".$champ.") as tot FROM `llx_facturedet` f, llx_bimp_facture_line bf WHERE bf.`id_line` = f.rowid AND `commission_apporteur` = '" . $this->id . "-" . $filtre->id . "'");
            $tot += $res[0]->tot * $filtre->getData('commition') / 100;
            
        }
        $errors = BimpTools::merge_array($errors, $this->updateField('total', $tot));
        return $errors;
    }

    public function actionAddNewFatureLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commissions ajoutées avec succès';

        $errors = $this->addNewFatureLine();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function getActionsButtons()
    {

//        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('addNewFatureLine')) {
                if ($this->canSetAction('addNewFatureLine')) {
                    $buttons[] = array(
                        'label'   => 'Ajouter commissions',
                        'icon'    => 'envelope',
                        'onclick' => $this->getJsActionOnclick('addNewFatureLine', array(), array(
                        ))
                    );
                }
            }

            if ($this->isActionAllowed('validate', $errors)) {
                if ($this->canSetAction('validate')) {
                    $buttons[] = array(
                        'label'   => 'Valider',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('validate', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la validation de cette commission'
                        ))
                    );
                } else {
                    $errors = 'Vous n\'avez pas la permission de valider cette commission';
                }
            }
        }
        return $buttons;
    }

    public function isActionAllowed($action, &$errors = array())
    {

        switch ($action) {
            case 'addNewFatureLine':
            case 'validate':
                return $this->getData('status') == self::STATUS_DRAFT;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function renderDetailsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commission absent');
        }

        $html = '';

        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres', array(), 'position', 'ASC');

        $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
        $list_name = 'commission_apporteur';
        foreach ($tabsFiltres as $filtre) {
            $bc_list = new BC_ListTable($factureLine, $list_name, 1, null, $filtre->getLabel(), 'fas_check');
            $bc_list->addFieldFilterValue('commission_apporteur', $this->id . '-' . $filtre->id);
            $bc_list->addIdentifierSuffix('comm_' . $filtre->id . '_' . $this->id);

            $html .= $bc_list->renderHtml();
        }

        $tabs[] = array(
            'id'      => 'facture',
            'title'   => 'Ligne de facture',
            'content' => $html
        );

        if ($this->getData('status') == 1) {
            $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
            $bc_list = new BC_ListTable($reval, 'default', 1, null, 'Revalorisation', 'fas_check');
            $bc_list->addFieldFilterValue('id_commission_apporteur', $this->id);
            $html = $bc_list->renderHtml();

            $tabs[] = array(
                'id'      => 'revalorisations',
                'title'   => 'Revalorisations',
                'content' => $html
            );
        }

        $html = BimpRender::renderNavTabs($tabs, 'commission_details');

        return $html;
    }

    public function getFiltres()
    {
        $result = array();
        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres', array(), 'position', 'ASC');
        foreach ($tabsFiltres as $filtre) {
            $result[$filtre->id] = $filtre->getLabel();
        }
        return $result;
    }

    public function actionChangeLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ligne changé avec succès';

        if (!$this->db->execute('UPDATE llx_bimp_facture_line SET commission_apporteur = "' . $this->id . '-' . $data['filtre'] . '" WHERE id = ' . $data['idLn']))
            $errors[] = 'Erreur inconnue';


        if (!count($errors))
            $errors = $this->calcTotal();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
