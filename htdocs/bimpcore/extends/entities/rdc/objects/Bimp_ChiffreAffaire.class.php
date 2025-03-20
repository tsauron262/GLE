<?php

class Bimp_ChiffreAffaire_ExtEntity extends BimpObject {
	public static $parentObjectArray = array(
		0 => 'Bimp_Societe',
		1 => 'Bimp_Concurrence',
	);
	public static $parentIdProperty = array(
		0 => 'fk_soc',
		1 => 'id',
	);
	public static $objLabelArray = array(
		0 => 'CA société',
		1 => 'CA concurrence',
	);
	public static $periodsRdcArray = array(
		0 => 'Annee',
		1 => 'Semestre',
		2 => 'Mois',
	);

	// getters
	public function getParentObject($type) 	{
		return self::$parentObjectArray[$type];
	}
	public function getParent_IdProperty($type) 	{
		return self::$parentIdProperty[$type];
	}

	public function getTypesObjetsRdc() {
		return self::$objLabelArray;
	}

	public function getPeriodsRdc() {
		return self::$periodsRdcArray;
	}
}
