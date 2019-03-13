<?php

require_once DOL_DOCUMENT_ROOT."/bimpmargeprod/objects/Abstract_margeprod.class.php";
class BMP_Vendeur extends Abstract_margeprod
{

    public function canDelete()
    {
        global $user;
        
        if ($user->admin) {
            return 1;
        }
        
        return 0;
    }
    
    public static function getVendeurs($active_only = true)
    {
        $cache_key = 'bmp_vendeurs';
        $filters = array();
        if ($active_only) {
            $cache_key .= '_active_only';
            $filters['active'] = 1;
        }

        if (!isset(self::$cache[$cache_key])) {
            $instance = BimpObject::getInstance('bimpmargeprod', 'BMP_Vendeur');
            $list = $instance->getList($filters, null, null, 'id', 'asc', 'array', array('id'));
            if (is_array($list)) {
                foreach ($list as $item) {
                    $vendeur = BimpCache::getBimpObjectInstance($instance->module, $instance->object_name, (int) $item['id']);
                    if ($vendeur->isLoaded()) {
                        self::$cache[$cache_key][] = $vendeur;
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public function getTarifsArray()
    {
        BimpObject::loadClass($this->module, 'BMP_Event');
        return BMP_Event::getPredefTarifsArray();
    }
    
    public function getDefaultTarifs()
    {
        $tarifs = array();

        BimpObject::loadClass($this->module, 'BMP_Event');
        foreach (BMP_Event::getPredefTarifsArray() as $key => $label) {
            $tarifs[] = $key;
        }

        return implode(',', $tarifs);
    }

    public function displayVendeur($display_name = 'nom_url', $display_input_value = true, $no_html = false)
    {
        if ((int) $this->getData('id_soc')) {
            return $this->displayData('id_soc', $display_name, $display_input_value, $no_html);
        }

        return $this->displayData('label', 'default', $display_input_value, $no_html);
    }

    // Overrides:

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if (!(int) $this->getData('id_soc')) {
                if (!(string) $this->getData('label')) {
                    $errors[] = 'Vous devez soit sélectionner une société enregistrée, soit indiquer le nom du vendeur';
                }
            }
        }

        return $errors;
    }
}
