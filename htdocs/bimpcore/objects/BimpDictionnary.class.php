<?php

class BimpDictionnary extends BimpObject
{
	public static $default_table = 'bimpcore_dictionnary_value';
	public static $default_fields = array(
		'dictionnary' => array('label' => 'Dictionnaire', 'required' => 1),
		'code'        => array('label' => 'Code', 'required' => 1),
		'label'       => array('label' => 'Libellé', 'required' => 1),
		'icon'        => array('label' => 'Icône'),
		'class'       => array(
			'label' => 'Classe', 'values' => array(
				'info'      => array('label' => 'Information', 'classes' => array('info')),
				'warning'   => array('label' => 'Alerte', 'classes' => array('info')),
				'danger'    => array('label' => 'Danger', 'classes' => array('info')),
				'success'   => array('label' => 'Succès', 'classes' => array('info')),
				'important' => array('label' => 'Important', 'classes' => array('info'))
			)
		),
		'active'      => array('label' => 'Activé', 'type' => 'bool', 'default' => 1),
		'position'    => array('label' => 'Position', 'type' => 'int', 'default' => 1)
	);

	public static function addDefaultDictionnary($code, $name, $active = 1)
	{
		$bdb = self::getBdb();
		$errors = array();

		if ((int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'' . $code . '\'')) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			$dict = BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'    => $code,
				'name'    => $name,
				'table'   => self::$default_table,
				'filters' => array(
					'dictionnary' => $code
				),
				'active'  => $active
			), true, $errors);
		}

		return $errors;
	}

	public static function addDictionnary($code, $name, $table, $filters, $fields, $key_field, $label_field, $active_field, $active = 1)
	{
		$bdb = self::getBdb();
		$errors = array();

		if ((int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'' . $code . '\'')) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			$dict = BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'         => $code,
				'name'         => $name,
				'table'        => $table,
				'filters'      => $filters,
				'fields'       => $fields,
				'key_field'    => $key_field,
				'label_field'  => $label_field,
				'active_field' => $active_field,
				'active'       => $active
			), true, $errors);
		}

		return $errors;
	}
}
