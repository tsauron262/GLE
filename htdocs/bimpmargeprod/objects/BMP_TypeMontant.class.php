<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";
class BMP_TypeMontant extends Abstract_margeprod
{

    const BMP_TYPE_FRAIS = 1;
    const BMP_TYPE_RECETTE = 2;

    public static $types = array(
        1 => array('label' => 'Frais', 'icon' => '', 'classes' => array('danger')),
        2 => array('label' => 'Recette', 'icon' => '', 'classes' => array('success'))
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
        return BimpCache::getBimpObjectFullListArray($this->module, 'BMP_CategorieMontant');
    }

    public function getAllTypes()
    {
        return self::getBimpObjectFullListArray($this->module, $this->object_name);
    }

    // Cache: 

    public static function getTypesMontantsArray($include_empty = 0)
    {
        $cache_key = 'bmp_types_montants_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_TypeMontant');
            $types_montants = $instance->getList();
            foreach ($types_montants as $tm) {
                self::$cache[$cache_key][(int) $tm['id']] = $tm['name'] . ' (' . self::$types[(int) $tm['type']]['label'] . ')';
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Traitements: 

    public function rebuildAllCalcMontantsCaches()
    {

        $errors = array();
        $calc_montant = BimpObject::getInstance($this->module, 'BMP_CalcMontant');

        $list = $calc_montant->getList(array(), null, null, 'id', 'asc', 'array', array('id'));

        foreach ($list as $item) {
            $calc_montant = BimpCache::getBimpObjectInstance($this->module, 'BMP_CalcMontant', (int) $item['id']);
            if ($calc_montant->isLoaded()) {
                $errors = array_merge($errors, $calc_montant->rebuildTypesMontantsCache());
            }
        }


        return $errors;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $cache_errors = $this->rebuildAllCalcMontantsCaches();

            if (count($cache_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $cache_errors = $this->rebuildAllCalcMontantsCaches();

            if (count($cache_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $cache_errors = $this->rebuildAllCalcMontantsCaches();

            if (count($cache_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($cache_errors, 'Des erreurs sont survenues lors de la reconstruction du cache');
            }
        }

        return $errors;
    }
}
