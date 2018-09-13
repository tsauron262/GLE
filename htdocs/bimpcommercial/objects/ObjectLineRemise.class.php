<?php

class ObjectLineRemise extends BimpObject
{

    const OL_REMISE_PERCENT = 1;
    const OL_REMISE_AMOUNT = 2;

    // Getters - Ovveride BimpObject

    public function isParentEditable()
    {
        $parent = $this->getParentInstance();
        if (BimpObject::objectLoaded($parent)) {
            return (int) $parent->isEditable();
        }

        return 0;
    }

    public function getParentModule()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');
        return ObjectLine::getModuleByType($this->getParentObjectType());
    }

    public function getParentObjectName()
    {
        BimpObject::loadClass('bimpcommercial', 'ObjectLine');
        return ObjectLine::getObjectNameByType($this->getParentObjectType());
    }

    // Getters: 

    public function getParentObjectType()
    {
        if (BimpTools::isSubmit('extra_data/parent_object_type')) {
            return BimpTools::getValue('extra_data/parent_object_type');
        }

        return (string) $this->getData('object_type');
    }

    public function showMarginsInForms()
    {
        $parent = $this->getParentInstance();
        if (!is_null($parent)) {
            return $parent->showMarginsInForms();
        }

        return 0;
    }

    // Affichage: 

    public function displayMontant()
    {
        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');
            switch ($type) {
                case self::OL_REMISE_PERCENT:
                    return str_replace('.', ',', '' . $this->getData('percent')) . ' %';

                case self::OL_REMISE_AMOUNT:
                    return BimpTools::displayMoneyValue((float) $this->getData('montant'), 'EUR');
            }
        }

        return '';
    }

    public function displayPerUnit()
    {
        if ((int) $this->getData('type') === self::OL_REMISE_AMOUNT) {
            return $this->displayData('per_unit');
        }

        return '';
    }

    // Rendus HTML: 

    public function renderFormMargins()
    {
        $parent = $this->getParentInstance();

        if (BimpObject::objectLoaded($parent) && method_exists($parent, 'renderFormMargins')) {
            return $parent->renderFormMargins();
        }

        return '';
    }

    // Overrrides: 

    public function create(&$warnings, $force_create = false)
    {
        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return array('Erreur technique: type d\'élément commercial absent ou invalide');
        }

        if (!$parent->isEditable()) {
            $msg = BimpTools::ucfirst($parent->getLabel('the')) . ' ne peut pas être modifié';
            if ($parent->isLabelFemale()) {
                $msg .= 'e';
            }
            $msg .= '. Il n\'est pas possible d\'ajouter une remise';
            return array($msg);
        }

        return parent::create($warnings, $force_create);
    }

    public function update(&$warnings, $force_update = false)
    {
        $parent = $this->getParentInstance();
        if (!BimpObject::objectLoaded($parent)) {
            return array('Erreur technique: type d\'élément commercial absent ou invalide');
        }

        if (!$parent->isEditable()) {
            $msg = BimpTools::ucfirst($parent->getLabel('the')) . ' ne peut pas être modifié';
            if ($parent->isLabelFemale()) {
                $msg .= 'e';
            }
            $msg .= '. Il n\'est pas possible d\'éditer cette remise';
            return array($msg);
        }

        return parent::update($warnings, $force_update);
    }

    public function delete($force_delete = false)
    {
        $parent = null;

        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();
            if (!BimpObject::objectLoaded($parent)) {
                return array('Erreur technique: type d\'élément commercial absent ou invalide');
            }
            if (!$parent->isEditable()) {
                $msg = BimpTools::ucfirst($parent->getLabel('the')) . ' ne peut pas être modifié';
                if ($parent->isLabelFemale()) {
                    $msg .= 'e';
                }
                $msg .= '. Il n\'est pas possible de supprimer cette remise';
                return array($msg);
            }
        }

        $errors = parent::delete($force_delete);

        if (BimpObject::objectLoaded($parent)) {
            $parent->calcRemise();
        }

        return $errors;
    }
}
