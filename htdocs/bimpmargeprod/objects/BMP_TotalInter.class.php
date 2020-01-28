<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";
class BMP_TotalInter extends Abstract_margeprod
{

    public static $assos_name = array(
        'categories' => array(
            'categories_frais_in',
            'categories_recettes_in',
            'categories_frais_ex',
            'categories_recettes_ex'
        ),
        'montants'   => array(
            'montants_in',
            'montants_ex'
        )
    );

    // Getters: 
    
    public function canDelete()
    {
        global $user;
        
        if ($user->admin) {
            return 1;
        }
        
        return 0;
    }

    public function getCategoriesBMPArray()
    {
        return $this->getBimpObjectFullListArray($this->module, 'BMP_CategorieMontant');
    }

    public function getTypes_montantsArray()
    {
        BimpObject::loadClass($this->module, 'BMP_TypeMontant');
        return BMP_TypeMontant::getTypesMontantsArray();
    }

    public function getEventTotal($id_event, $id_coprod = 0)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $event = BimpCache::getBimpObjectInstance($this->module, 'BMP_Event', (int) $id_event);
        if (!$event->isLoaded()) {
            return 0;
        }

        $total = 0;

        $all_frais = (int) $this->getData('all_frais');
        $all_recettes = (int) $this->getData('all_recettes');

        $amounts = $event->getTotalAmounts();

        if ($all_frais) {
            $total += $amounts['total_frais_ht'];
        }

        if ($all_recettes) {
            $total += $amounts['total_recettes_ht'];
        }

        $tm_instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');

        $asso = new BimpAssociation($this, 'categories_frais_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_FRAIS
            ));

            foreach ($tm_list as $tm) {
                $total += (float) $event->getMontantAmount((int) $tm['id'], $id_coprod);
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_frais_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_FRAIS
            ));

            foreach ($tm_list as $tm) {
                $total -= (float) $event->getMontantAmount((int) $tm['id'], $id_coprod);
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_recettes_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_RECETTE
            ));

            foreach ($tm_list as $tm) {
                $total += (float) $event->getMontantAmount((int) $tm['id'], $id_coprod);
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_recettes_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => BMP_TypeMontant::BMP_TYPE_RECETTE
            ));

            foreach ($tm_list as $tm) {
                $total -= (float) $event->getMontantAmount((int) $tm['id'], $id_coprod);
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            $total += (float) $event->getMontantAmount((int) $id_type_montant, $id_coprod);
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            $total -= (float) $event->getMontantAmount((int) $id_type_montant, $id_coprod);
        }
        unset($asso);
        unset($list);

        return $total;
    }

    public function getDisplayableList()
    {
        $return = array();

        $list = $this->getList(array(
            'display' => 1
        ));

        $asso = new BimpAssociation($this, 'types_montants');
        
        foreach ($list as $item) {
            $code = null;
            $montants = $asso->getAssociatesList((int) $item['id']);
            $check = true;
            foreach ($montants as $id_montant) {
                $typeMontant = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', (int) $id_montant);
                if ($typeMontant->isLoaded()) {
                    $m_code = $typeMontant->getData('code_compta');
                    if (is_null($code)) {
                        $code = $m_code;
                        continue;
                    }
                    if ($code !== $m_code) {
                        $check = false;
                        break;
                    }
                }
            }
            if ($check) {
                $return[] = (int) $item['id'];
            }
        }

        return $return;
    }

    public function getAllTypesMontantsList()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $return = array();

        $all_frais = (int) $this->getData('all_frais');
        $all_recettes = (int) $this->getData('all_recettes');

        $typeMontant = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
        $typesMontants = $typeMontant->getList(array(), null, null, 'id', 'asc', 'array', array(
            'id', 'type', 'id_category'
        ));

        if ($all_frais) {
            foreach ($typesMontants as $tm) {
                if ((int) $tm['type'] === 1) {
                    if (!in_array((int) $tm['id'], $return)) {
                        $return[] = (int) $tm['id'];
                    }
                }
            }
        }

        if ($all_recettes) {
            foreach ($typesMontants as $tm) {
                if ((int) $tm['type'] === 2) {
                    if (!in_array((int) $tm['id'], $return)) {
                        $return[] = (int) $tm['id'];
                    }
                }
            }
        }

        $tm_instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');

        $asso = new BimpAssociation($this, 'categories_frais_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_FRAIS
            ));

            foreach ($tm_list as $tm) {
                if (!in_array((int) $tm['id'], $return)) {
                    $return[] = (int) $tm['id'];
                }
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_frais_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_FRAIS
            ));

            foreach ($tm_list as $tm) {
                foreach ($return as $key => $tid) {
                    if ((int) $tid === (int) $tm['id']) {
                        unset($return[$key]);
                    }
                }
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_recettes_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_RECETTE
            ));

            foreach ($tm_list as $tm) {
                if (!in_array((int) $tm['id'], $return)) {
                    $return[] = (int) $tm['id'];
                }
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'categories_recettes_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => BMP_TypeMontant::BMP_TYPE_RECETTE
            ));

            foreach ($tm_list as $tm) {
                foreach ($return as $key => $tid) {
                    if ((int) $tid === (int) $tm['id']) {
                        unset($return[$key]);
                    }
                }
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            if (!in_array((int) $id_type_montant, $return)) {
                $return[] = (int) $id_type_montant;
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            foreach ($return as $key => $tid) {
                if ((int) $tid === (int) $id_type_montant) {
                    unset($return[$key]);
                }
            }
        }
        unset($asso);
        unset($list);

        return $return;
    }

    // Affichage: 

    public function displayCategorie($id_categorie)
    {
        $instance = BimpCache::getBimpObjectInstance($this->module, 'BMP_CategorieMontant', (int) $id_categorie);
        if ($instance->isLoaded()) {
            return $instance->getData('name');
        }

        return BimpRender::renderAlerts('La catégorie d\'ID ' . $id_categorie . ' n\'existe pas');
    }

    public function displayTypeMontant($id_typeMontant)
    {
        $instance = BimpCache::getBimpObjectInstance($this->module, 'BMP_TypeMontant', (int) $id_typeMontant);
        if ($instance->isLoaded()) {
            return $instance->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $instance->getData('type')]['label'] . ')';
        }

        return BimpRender::renderAlerts('Le type de montant d\'ID ' . $id_typeMontant . ' n\'existe pas');
    }

    public function defaultDisplayCategories_frais_inItem($id_categorie)
    {
        return $this->displayCategorie($id_categorie);
    }

    public function defaultDisplayCategories_recettes_inItem($id_categorie)
    {
        return $this->displayCategorie($id_categorie);
    }

    public function defaultDisplayCategories_frais_exItem($id_categorie)
    {
        return $this->displayCategorie($id_categorie);
    }

    public function defaultDisplayCategories_recettes_exItem($id_categorie)
    {
        return $this->displayCategorie($id_categorie);
    }

    public function defaultDisplayMontants_inItem($id_typeMontant)
    {
        return $this->displayTypeMontant($id_typeMontant);
    }

    public function defaultDisplayMontants_exItem($id_typeMontant)
    {
        return $this->displayTypeMontant($id_typeMontant);
    }

    // Overrides: 

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if ($this->isLoaded()) {
            $calcMontant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

            $list = $calcMontant->getList(array(
                'type_source'     => 2,
                'id_total_source' => (int) $this->id
                    ), null, null, 'id', 'asc', 'array', array(
                'id'
            ));

            foreach ($list as $item) {
                $calcMontant = BimpCache::getBimpObjectInstance($this->module, 'BMP_CalcMontant', (int) $item['id']);
                if ($calcMontant->isLoaded()) {
                    $cache_errors = $calcMontant->rebuildTypesMontantsCache();
                    if (count($cache_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
                    }
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        if ($this->isLoaded()) {
            $id = $this->id;
        } else {
            $id = null;
        }

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && !is_null($id)) {
            $calcMontant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

            $list = $calcMontant->getList(array(
                'type_source'     => 2,
                'id_total_source' => (int) $id
                    ), null, null, 'id', 'asc', 'array', array(
                'id'
            ));

            foreach ($list as $item) {
                $calcMontant = BimpCache::getBimpObjectInstance($this->module, '', (int) $item['id']);
                if ($calcMontant->isLoaded()) {
                    $calcMontant->set('id_total_source', 0);
                    $calcMontant->update();
                }
            }
        }

        return $errors;
    }
}
