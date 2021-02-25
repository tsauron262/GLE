<?php

class Bimp_SocRemiseExcept extends BimpObject
{

    public function canCreate()
    {
        return 0;
    }

    public function canEdit()
    {
        return 0;
    }

    public function canDelete()
    {
        return 0;
    }

    // Getters données: 

    public function getFacLineParent()
    {
        if ((int) $this->getData('fk_facture_line')) {
            $id_fac = (int) $this->db->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $this->getData('fk_facture_line'));

            if ($id_fac) {
                $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);
                if (BimpObject::objectLoaded($fac)) {
                    return $fac;
                }
            }
        }

        return null;
    }

    // Affichages: 

    public function displayFacDest()
    {
        $fac = null;
        if ((int) $this->getData('fk_facture')) {
            $fac = $this->getChildObject('facture');
        }

        if (!BimpObject::objectLoaded($fac)) {
            $fac = $this->getFacLineParent();
        }

        if (BimpObject::objectLoaded($fac)) {
            return $fac->getLink();
        }
        
        return '';
    }
}
