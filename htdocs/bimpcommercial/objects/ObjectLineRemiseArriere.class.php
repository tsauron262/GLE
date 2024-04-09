<?php

class ObjectLineRemiseArriere extends BimpObject
{

    // Getters array: 

    public function getTypesArray()
    {
        $className = '';
        BimpObject::loadClass('bimpcore', 'Bimp_ProductRA', $className);
        return $className::$types;
    }

    // Getters params: 

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

    public function getParentObjectType()
    {
        if (BimpTools::isSubmit('extra_data/parent_object_type')) {
            return BimpTools::getValue('extra_data/parent_object_type', '', 'aZ09comma');
        }

        return (string) $this->getData('object_type');
    }

    public function getListFilters()
    {
        if (BimpTools::isSubmit('extra_data/parent_object_type')) {
            return array(
                array(
                    'name'   => 'object_type',
                    'filter' => BimpTools::getValue('extra_data/parent_object_type', '', 'aZ09comma')
                )
            );
        } elseif ((string) $this->getData('object_type')) {
            return array(
                array(
                    'name'   => 'object_type',
                    'filter' => $this->getData('object_type')
                )
            );
        }

        // Erreur: (parent_object_type est obligatoire) on bloque la liste par précaution
        return array(
            array(
                'name'   => 'id',
                'filter' => 0
            )
        );
    }

    public function getListExtraHeaderButtons()
    {
        $buttons = array();

        $line = $this->getParentInstance();

        if (BimpObject::objectLoaded($line)) {
            $buttons[] = array(
                'label'   => 'Ajouter une remise arrière du produit',
                'icon'    => 'fas_plus-circle',
                'onclick' => $line->getJsActionOnclick('addProductRA', array(), array(
                    'form_name' => 'product_ra'
                ))
            );
        }

        return $buttons;
    }

    // Getters data : 

    public function getRemiseAmount(&$errors = array())
    {
        $amount = 0;

        $line = $this->getParentInstance();
        if (!BimpObject::objectLoaded($line)) {
            $errors[] = 'Ligne liée absente';
        } else {
            $product = $line->getProduct();
            $remise_percent = (float) $this->getData('value');

            switch ($this->getData('type')) {
                case 'crt':
                    $eco_taxe = 0;
                    $copie_privee = 0;
                    if (BimpObject::objectLoaded($product)) {
                        if (!$remise_percent) {
                            $remise_percent = (float) $product->getRemiseCrt();
                        }

                        $eco_taxe = (float) $product->getData('rpcp');
                        $copie_privee = (float) $product->getData('deee');
                    }

                    if ($remise_percent) {
                        $amount = (float) ($line->pu_ht - ($eco_taxe + $copie_privee)) * ($remise_percent / 100);
                    }
                    break;

                default:
                    $amount = (float) ($line->pa_ht * ($remise_percent / 100));
                    break;
            }
        }

        return $amount;
    }
}
