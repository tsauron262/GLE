<?php

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

class BR_OrderLine extends BimpObject
{

    const PRODUIT = 1;
    const SERVICE = 2;

    public static $types = array(
        self::PRODUIT => 'Produit',
        self::SERVICE => 'Service'
    );

    // Getters: 

    public function isOrderInvoiced()
    {
        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            if ((int) $commande->getData('id_facture')) {
                return 1;
            }
        }

        return 0;
    }

    public function getAvoirsArray()
    {
        $avoirs = array(
            0 => 'Créer un nouvel avoir'
        );

        $commande = $this->getParentInstance();

        if (BimpObject::objectLoaded($commande)) {
            $asso = new BimpAssociation($commande, 'avoirs');
            foreach ($asso->getAssociatesList() as $id_avoir) {
                $avoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
                if ($avoir->isLoaded()) {
                    if ((int) $avoir->dol_object->statut === (int) Facture::STATUS_DRAFT) {
                        $DT = new DateTime($this->db->db->iDate($avoir->dol_object->date_creation));
                        $avoirs[(int) $id_avoir] = $avoir->dol_object->ref . ' (créé le ' . $DT->format('d / m / Y à H:i') . ')';
                    }
                }
            }
        }

        krsort($avoirs);

        return $avoirs;
    }

    public function getRemovableQty()
    {
        return (int) $this->getShipmentAvailableQty();
    }

    public function getShipmentAvailableQty()
    {
        if ($this->isLoaded()) {
            return (int) $this->getData('qty') - (int) $this->getData('qty_shipped');
        }

        return 0;
    }

    public function getRemovableOrderLinesArray()
    {
        $lines = array(
            0 => ''
        );
        $commande = $this->getParentInstance();
        if (BimpObject::objectLoaded($commande)) {
            foreach ($commande->getChildrenObjects('order_lines') as $line) {
                if ((int) $line->getRemovableQty() > 0) {
                    $lines[(int) $line->id] = $line->displayData('id_product', 'nom', false, true);
                }
            }
        }
        return $lines;
    }

    public function getRemoveOrderLineType()
    {
        $fields = BimpTools::getValue('fields', array());
        $id = (int) isset($fields['id_br_order_line']) ? $fields['id_br_order_line'] : 0;
        if ($id) {
            if ($this->fetch($id)) {
                return (int) $this->getData('type');
            }
        }
        return '';
    }

    public function getListExtraBtn()
    {
        $buttons = array();
        if ((int) $this->getData('type') === self::SERVICE && (int) $this->getRemovableQty() > 0) {
            $buttons[] = array(
                'label'   => 'Retirer de la commande client',
                'icon'    => 'times-circle',
                'onclick' => $this->getJsActionOnclick('removeServiceOrderLine', array(), array(
                    'form_name' => 'remove'
                ))
            );
        }

        return $buttons;
    }

    // Rendus: 

    public function renderRemovableQtyInput()
    {
        $removable_qty = (int) $this->getRemovableQty();
        return BimpInput::renderInput('qty', 'qty', $removable_qty, array(
                    'data' => array(
                        'data_type' => 'number',
                        'min'       => 1,
                        'max'       => $removable_qty,
                        'decimals'  => 0,
                        'unsigned'  => 1
                    )
        ));
    }

    // Traitements: 

    public function createFromOrderLine(Commande $commande, OrderLine $line, Bimp_Product $product = null)
    {
        $errors = array();

        if (!BimpObject::objectLoaded($commande)) {
            $errors[] = 'Commande invalide';
        }

        if (!BimpObject::objectLoaded($line)) {
            $errors[] = 'Ligne de commande invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        if (!isset($line->fk_product) || !$line->fk_product) {
            return array();
        }

        if (!is_null($product) || !BimpObject::objectLoaded($product)) {
            $product_type = $this->db->getValue('product', 'fk_product_type', '`rowid` = ' . (int) $line->fk_product);
        } else {
            $product_type = $product->getData('fk_product_type');
        }

        if (is_null($product_type)) {
            return array('Produit invalide pour la ligne de commande ' . $line->id);
        }

        $this->reset();

        $this->validateArray(array(
            'id_commande'   => (int) $commande->id,
            'id_product'    => (int) $line->fk_product,
            'id_order_line' => (int) $line->id,
            'qty'           => (int) $line->qty,
            'type'          => ((int) $product_type === 0 ? self::PRODUIT : self::SERVICE)
        ));

        $errors = $this->create($warnings, true);

        return $errors;
    }

    public function removeServiceFromOrder($qty, $id_avoir = 0)
    {
        $errors = array();

        $qty = (int) $qty;
        if (!$qty) {
            $errors[] = 'Quantités à retirer absentes ou égales à 0';
        } else {
            $commande = $this->getParentInstance();
            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'ID de la commande absent ou invalide';
            } else {
                $id_line = (int) $this->getData('id_order_line');
                if (!$id_line) {
                    $errors[] = 'ID de la ligne de commande absent ou invalide';
                }
                $errors = $commande->removeOrderLine($id_line, $qty, $id_avoir);

                $commande->checkIsFullyShipped();
                $commande->checkIsFullyInvoiced();
            }
        }

        return $errors;
    }

    public function addToCreditNote($qty, $id_avoir = null, $id_equipment = null)
    {
        $errors = array();

        if ($this->isLoaded() && (int) $this->getData('id_order_line')) {
            $commande = $this->getParentInstance();
            if (BimpObject::objectLoaded($commande)) {
                $errors = $commande->addLineToCreditNote((int) $this->getData('id_order_line'), (int) $qty, $id_avoir, $id_equipment);
            } else {
                $errors[] = 'ID de la commande absent';
            }
        } else {
            $errors[] = 'ID de la ligne de commande absent';
        }

        return $errors;
    }

    // Actions: 

    public function actionRemoveServiceOrderLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Service(s) retiré(s) de la commande avec succès';

        if (!isset($data['id_avoir'])) {
            $data['id_avoir'] = 0;
        }

        if (!isset($data['qty']) || !(int) $data['qty']) {
            $errors[] = 'Quantités à retirer absentes ou égales à 0';
        } else {
            $errors = $this->removeServiceFromOrder((int) $data['qty'], (int) $data['id_avoir']);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
