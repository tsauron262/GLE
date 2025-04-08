<?php

BimpObject::loadClass('bimpcore', 'BimpDictionnary');

class BimpDict
{
	public static function getValuesData($dict_code, $active_only = true)
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict->getValuesData($active_only);
		}

		return array();
	}

	public static function getValuesArray($dict_code, $active_only = true, $include_empty = false, $empty_value = '', $empty_label = '')
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict->getValuesArray($active_only, $include_empty, $empty_value, $empty_label);
		}

		return array();
	}

	public static function addDefaultDictionnary($code, $name, $active = 1, $values_children_name = 'values', $filters = array(), &$errors = array())
	{
		$bdb = BimpCache::getBdb();

		if ((int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'' . $code . '\'')) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			return BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'          => $code,
				'name'          => $name,
				'values_params' => array(
					'children' => $values_children_name,
					'filters'  => $filters
				),
				'active'        => $active
			), true, $errors);
		}

		return null;
	}

	public static function addDictionnary($code, $name, $table, $filters, $fields, $key_field, $label_field, $active_field = '', $position_field = '', $active = 1)
	{
		$bdb = BimpCache::getBdb();
		$errors = array();

		if ((int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'' . $code . '\'')) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'          => $code,
				'name'          => $name,
				'values_params' => array(
					'table'          => $table,
					'filters'        => $filters,
					'fields'         => $fields,
					'key_field'      => $key_field,
					'label_field'    => $label_field,
					'active_field'   => $active_field,
					'position_field' => $position_field
				),
				'active'        => $active
			), true, $errors);
		}

		return $errors;
	}

	public static function renderEditDictionnaryIcon($dict_code)
	{
		$html = '';

//		$html .= BimpRender::renderIconButton();

		return $html;
	}
}
