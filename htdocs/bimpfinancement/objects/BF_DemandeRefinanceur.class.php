<?php

class BF_DemandeRefinanceur extends BimpObject
{

    public static $coefALaCon = 0.0833333333333;

    const BF_REFINANCEUR_RIEN = 0;
    const BF_REFINANCEUR_ETUDE = 1;
    const BF_REFINANCEUR_ACCORD = 2;
    const BF_REFINANCEUR_REFUS = 3;
    const BF_REFINANCEUR_SOUS_CONDITION = 4;

    public static $payments = array(
        0 => '-',
        1 => 'Prélévement auto',
        2 => 'Virement',
        3 => 'Mandat administratif'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'mensuel',
        3  => 'trimestriel',
        6  => 'semestriel',
        12 => 'annuel'
    );
    public static $period_label = array(
        1  => 'mois',
        3  => 'trimestre',
        6  => 'semestre',
        12 => 'an'
    );
    public static $period_label_plur = array(
        1  => 'mois',
        3  => 'trimestres',
        6  => 'semestres',
        12 => 'ans'
    );
    public static $status_list = array(
        // Oblkigatoirement une constante pour self::
        self::BF_REFINANCEUR_RIEN           => array('label' => '-', 'classes' => array('important')),
        self::BF_REFINANCEUR_ACCORD         => array('label' => 'Accord', 'classes' => array('success')),
        self::BF_REFINANCEUR_REFUS          => array('label' => 'Refus', 'classes' => array('danger')),
        self::BF_REFINANCEUR_ETUDE          => array('label' => '&Eacute;tude', 'classes' => array('warning')),
        self::BF_REFINANCEUR_SOUS_CONDITION => array('label' => 'Sous-condition', 'classes' => array('warning')),
    );

    public function isCreatable($force_create = false, &$errors = array())
    {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            if (!(int) $demande->getData('accepted')) {
                return 1;
            }
        }

        return 0;
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        return $this->isCreatable($force_edit, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return $this->isCreatable($force_delete, $errors);
    }

    public function displayRefinanceur()
    {
        if ($this->isLoaded()) {
            $refinanceur = BimpCache::getBimpObjectInstance($this->module, 'BF_Refinanceur', (int) $this->getData('id_refinanceur'));

            if (!$refinanceur->isLoaded()) {
                return $this->renderChildUnfoundMsg('id_refinanceur', $refinanceur);
            } else {
                return $refinanceur->getName();
            }
        }

        return '';
    }

    public static function getRefinanceursArray($include_empty = true)
    {
        $cache_key = 'bf_refinanceurs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance('bimpfinancement', 'BF_Refinanceur');

            foreach ($instance->getList(array(), null, null, 'id', 'asc', 'array', array('id', 'id_societe')) as $item) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $item['id_societe']);
                if ($soc->isLoaded()) {
                    self::$cache[$cache_key][(int) $item['id']] = $soc->getName();
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }
}
