<?php

class RemiseGlobale extends BimpObject
{

    public $trigger_parent_process = true;
    public static $types = array(
        'amount'  => 'Montant fixe',
        'percent' => 'Pourcentage'
    );

    // Getters booléens: 


    public function isCreatable($force_create = false, &$errors = array())
    {
        $parent = $this->getParentObject();
        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'ID du parent absent';
            return 0;
        }
        if (!$parent::$remise_globale_allowed) {
            $errors[] = 'Les remises globales ne sont pas disponibles pour ' . $parent->getLabel('the_plur');
            return 0;
        }
        if (!$force_create && !$parent->areLinesEditable()) {
            $errors[] = BimpTools::ucfirst($parent->getLabel('the')) . ' ne peut plus être édité' . $parent->e();
            return 0;
        }

        return (int) parent::isCreatable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return (int) $this->isCreatable($force_edit, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return (int) $this->isCreatable($force_delete, $errors);
    }

    // Getters: 

    public function getParentObject()
    {
        if ((int) $this->getData('id_obj')) {
            switch ($this->getData('obj_type')) {
                case 'propal':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $this->getData('id_obj'));
                case 'order':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_obj'));
                case 'invoice':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('id_obj'));
            }
        }

        return null;
    }

    // Affichage: 

    public function displayValue()
    {
        switch ($this->getData('type')) {
            case 'percent':
                return BimpTools::displayFloatValue($this->getData('percent')) . '%';

            case 'amount':
                return BimpTools::displayMoneyValue((float) $this->getData('amount'));
        }

        return '';
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        $parent = $this->getParentObject();

        if (!BimpObject::objectLoaded($parent)) {
            $errors[] = 'ID ou type de l\'objet parent absent ou invalide';
        }

        switch ($this->getData('type')) {
            case 'percent':
                if (!(float) $this->getData('percent')) {
                    $errors[] = 'Veuillez indiquer le pourcentage de remise';
                }
                break;

            case 'amount':
                if (!(float) $this->getData('amount')) {
                    $errors[] = 'Veuillez indiquer le montant de la remise';
                }
                break;
        }

        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        parent::onSave($errors, $warnings);

        if ($this->trigger_parent_process) {
            $parent = $this->getParentObject();

            if (BimpObject::objectLoaded($parent)) {
                $warnings = BimpTools::merge_array($warnings, $parent->processRemisesGlobales());
            }
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $parent = $this->getParentObject();

            if (BimpObject::objectLoaded($parent) && is_a($parent, 'BimpComm')) {
                self::$cache[$parent->object_name . '_' . $parent->id . '_remises_globales'] = null;
            }
        }
        
        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $parent = $this->getParentObject();
        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors) && $this->trigger_parent_process && BimpObject::objectLoaded($parent)) {
            $warnings = BimpTools::merge_array($warnings, $parent->processRemisesGlobales());
        }

        return $errors;
    }
}
