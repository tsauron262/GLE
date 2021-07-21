<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

class BT_ficheInter_facturation extends BimpObject
{

    public $juste_depacement_facturable = true;

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        if ($force_edit) {
            return 1;
        }

        switch ($field) {
            case "remise":
                return 1;
        }

        return 0;
    }

    public function isActionAllowed($action, &$errors = [])
    {
        switch ($action) {
            case 'addRemise':
                $fi = $this->getParentInstance();
                if (!BimpObject::objectLoaded($fi)) {
                    $errors[] = 'Fiche inter absente';
                    return 0;
                }

                if ((int) $fi->getData('fk_statut') !== BT_ficheInter::STATUT_VALIDER) {
                    $errors[] = 'La fiche inter n\'est pas au statut "Validée"';
                    return 0;
                }

                if ((float) $this->getData('total_ht_depacement') <= 0) {
                    $errors[] = 'Il n\'y a pas de montant pour le déplacement';
                    return 0;
                }

                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = Array();

        if ($this->isActionAllowed('addRemise')) {
            $buttons[] = array(
                'label'   => "Appliquer une remise",
                'icon'    => 'fas_percent',
                'onclick' => $this->getJsActionOnclick('addRemise', array(), array(
                    'form_name' => "addRemisePercent"
                ))
            );
        }

        return $buttons;
    }

    // Getters données: 

    public function getTotalTTC()
    {
        return $this->getData('total_ht_vendu') + (($this->getData('tva_tx') * $this->getData('total_ht_vendu')) / 100);
    }

    // Affichage: 

    public function displayTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotalTTC(), 'EUR', 0, 0, 0, 2, 1);
    }

    public function displayHTfacture()
    {
        $return = "";

        $remise = $this->getdata('remise');
        $ht_vendu = $this->getData('total_ht_vendu');
        $ht_depacement = $this->getData('total_ht_depacement');

        if ($this->juste_depacement_facturable) {
            $return .= "<strong class='info bs-popover'" . BimpRender::renderPopoverData('Juste le dépacement est facturable') . ">" . BimpRender::renderIcon("fas_car") . "</strong>&nbsp;&nbsp;";
            $facturable = ($ht_depacement - ($remise * $ht_depacement) / 100);
        } else {
            $facturable = (($ht_depacement + $ht_vendu) - ($remise * $ht_depacement) / 100);
        }

        $return .= BimpTools::displayMoneyValue($facturable) . " HT";

        return $return;
    }

    public function displayAlertes()
    {
        $html = "";

        if ($this->getData('remise') > 5) {
            $html .= "<strong class='bs-popover rowButton' " . BimpRender::renderPopoverData('<strong>Soumis à falidation financière</strong><br />Pourcentage de plus de 5%', "top", true) . " >" . BimpRender::renderIcon("fas_percent") . "</strong>";
        }

        return $html;
    }

    public function displayAssocLines()
    {
        $html = "";
        $lines = $this->getData('fi_lines');

        if (count($lines) > 0) {
            foreach ($lines as $id_line) {
                $line = BimpCache::getBimpObjectInstance("bimptechnique", "BT_ficheInter_det", $id_line);

                if (BimpObject::objectLoaded($line)) {
                    $html .= "#" . $line->id . "<br/>" . $line->display_service_ref(false);
                }
            }
        }
        return $html;
    }

    // Actions: 

    public function actionAddRemise($data, &$success = '')
    {
        $warnings = Array();
        $errors = Array();

        $success = 'Remise ajoutée avec succès';

        if ($data['remise'] != $this->getData('remise')) {
            $this->set('remise', $data['remise']);
            $this->update($warnings, true);
        }

        return Array(
            'warnings' => $warnings,
            'errors'   => $errors
        );
    }
}
