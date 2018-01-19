<?php

class BMP_TotalInter extends BimpObject
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

    public function getCategoriesArray()
    {
        $instance = BimpObject::getInstance($this->module, 'BMP_CategorieMontant');
        $categories = array();
        $list = $instance->getList();
        foreach ($list as $cat) {
            $categories[$cat['id']] = $cat['name'];
        }

        return $categories;
    }

    public function getTypes_montantsArray()
    {
        $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
        $types_montants = $instance->getList();
        $montants = array();

        foreach ($types_montants as $tm) {
            $montants[$tm['id']] = $tm['name'] . ' (' . BMP_TypeMontant::$types[(int) $tm['type']]['label'] . ')';
        }

        return $montants;
    }

    public function displayCategorie($id_categorie)
    {
        $instance = BimpObject::getInstance($this->module, 'BMP_CategorieMontant');
        if ($instance->fetch($id_categorie)) {
            return $instance->getData('name');
        }

        return BimpRender::renderAlerts('La catégorie d\'ID ' . $id_categorie . ' n\'existe pas');
    }

    public function displayTypeMontant($id_typeMontant)
    {
        $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
        if ($instance->fetch($id_typeMontant)) {
            return $instance->getData('name') . ' (' . BMP_TypeMontant::$types[(int) $instance->getData('type')]['label'] . ')';
        }

        return BimpRender::renderAlerts('Le type de montant d\'ID ' . $id_TypeMontant . ' n\'existe pas');
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

    public function getEventTotal($id_event, $id_coprod = 0)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $event = BimpObject::getInstance($this->module, 'BMP_Event');
        if (!$event->fetch((int) $id_event)) {
            return 0;
        }

        $total = 0;

        $all_frais = (int) $this->getData('all_frais');
        $all_recettes = (int) $this->getData('all_recettes');

        $amounts = $event->getTotalAmounts();


        if ($all_frais) {
            $total += $amounts['total_frais'];
        }

        if ($all_recettes) {
            $total += $amounts['total_recettes'];
        }

        $montant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
        $tm_instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');

        $asso = new BimpAssociation($this, 'categories_frais_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_categorie) {
            $tm_list = $tm_instance->getList(array(
                'id_category' => (int) $id_categorie,
                'type'        => (int) BMP_TypeMontant::BMP_TYPE_FRAIS
            ));

            foreach ($tm_list as $tm) {
                $montant->reset();
                if ($montant->find(array(
                            'id_event'   => (int) $id_event,
                            'id_montant' => (int) $tm['id'],
                            'id_coprod'  => (int) $id_coprod
                        ))) {
                    $total += (float) $montant->getData('amount');
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
                $montant->reset();
                if ($montant->find(array(
                            'id_event'   => (int) $id_event,
                            'id_montant' => (int) $tm['id'],
                            'id_coprod'  => (int) $id_coprod
                        ))) {
                    $total -= (float) $montant->getData('amount');
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
                $montant->reset();
                if ($montant->find(array(
                            'id_event'   => (int) $id_event,
                            'id_montant' => (int) $tm['id'],
                            'id_coprod'  => (int) $id_coprod
                        ))) {
                    $total += (float) $montant->getData('amount');
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
                $montant->reset();
                if ($montant->find(array(
                            'id_event'   => (int) $id_event,
                            'id_montant' => (int) $tm['id'],
                            'id_coprod'  => (int) $id_coprod
                        ))) {
                    $total -= (float) $montant->getData('amount');
                }
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_in');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            $montant->reset();
            if ($montant->find(array(
                        'id_event'   => (int) $id_event,
                        'id_montant' => (int) $id_type_montant,
                        'id_coprod'  => (int) $id_coprod
                    ))) {
                $total += (float) $montant->getData('amount');
            }
        }
        unset($asso);
        unset($list);

        $asso = new BimpAssociation($this, 'montants_ex');
        $list = $asso->getAssociatesList();

        foreach ($list as $id_type_montant) {
            $montant->reset();
            if ($montant->find(array(
                        'id_event'   => (int) $id_event,
                        'id_montant' => (int) $id_type_montant,
                        'id_coprod'  => (int) $id_coprod
                    ))) {
                $total -= (float) $montant->getData('amount');
            }
        }
        unset($asso);
        unset($list);

        if ($this->id === 3) {
            echo 'Total recettes bar: ' . $total;
            exit;
        }
        return $total;
    }

    public function getDisplayableList()
    {
        $return = array();

        $list = $this->getList(array(
            'display' => 1
        ));

        $asso = new BimpAssociation($this, 'types_montants');
        $typeMontant = BimpObject::getInstance($this->module, 'BMP_TypeMontant');

        foreach ($list as $item) {
            $code = null;
            $montants = $asso->getAssociatesList((int) $item['id']);
            $check = true;
            foreach ($montants as $id_montant) {
                $typeMontant->reset();
                if ($typeMontant->fetch($id_montant)) {
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
}
