<?php

BimpObject::loadClass('bimpcore', 'BimpDictionnary');

class BimpDict
{
	public static function getDictionnary($dict_code)
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict;
		}

		return null;

	}

	public static function getDictionnaryId($dict_code)
	{
		return (int) BimpCache::getBdb()->getValue('bimpcore_dictionnary', 'id', 'code = \'' . $dict_code . '\'');
	}

	public static function getValuesData($dict_code, $active_only = true)
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict->getValuesData($active_only);
		}

		return array();
	}

	public static function getValuesArray($dict_code, $active_only = true, $include_empty = false, $empty_label = '')
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict->getValuesArray($active_only, $include_empty, $empty_label);
		}

		return array();
	}

	public static function getValuesInvertedArray($dict_code, $active_only = true, $include_empty = false, $empty_label = '')
	{
		$dict = BimpCache::findBimpObjectInstance('bimpcore', 'BimpDictionnary', array('code' => $dict_code));

		if (BimpObject::objectLoaded($dict)) {
			return $dict->getValuesInvertedArray($active_only, $include_empty, $empty_label);
		}

		return array();
	}

	public static function addDefaultDictionnary($code, $name, $active = 1, $values_children_name = 'values', $key_field = 'id', $filters = array(), &$errors = array())
	{
		$id_dict = self::getDictionnaryId($code);
		if ($id_dict) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			$values_params = array(
				'children' => $values_children_name,
				'filters'  => $filters
			);

			if ($key_field !== 'id') {
				$values_params['key_field'] = $key_field;
			}

			return BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'          => $code,
				'name'          => $name,
				'values_params' => $values_params,
				'active'        => $active
			), true, $errors);
		}

		return null;
	}

	public static function addDolDictionnary($code, $name, $table, $id_dol_dict, $fields, $extra_values_params = array(), &$errors = array())
	{
		$id_dict = self::getDictionnaryId($code);
		if ($id_dict) {
			$errors[] = 'Un dictionnaire existe déjà pour le code "' . $code . '"';
		} else {
			$values_params = BimpTools::overrideArray(array(
				'table'          => $table,
				'id_dol_dict'    => $id_dol_dict,
				'fields'         => $fields,
				'key_field'      => 'rowid',
				'key_data_type'  => 'int',
				'label_field'    => 'label',
				'active_field'   => 'active',
				'position_field' => 'position',
				'filters'        => array()
			), $extra_values_params);

			foreach (array(
						 'key_field'      => 'id',
						 'key_data_type'  => 'int',
						 'label_field'    => 'label',
						 'active_field'   => 'active',
						 'position_field' => 'position',
						 'filters'        => array()
					 ) as $params_name => $def_value) {
				if ($values_params[$params_name] === $def_value) {
					unset($values_params[$params_name]);
				}
			}

			return BimpObject::createBimpObject('bimpcore', 'BimpDictionnary', array(
				'code'          => $code,
				'name'          => $name,
				'values_params' => $values_params,
				'active'        => 1
			), true, $errors);
		}

		return null;
	}

	public static function renderEditDictionnaryIcon($dict_code)
	{
		$html = '';

		$dict = self::getDictionnary($dict_code);

		if (BimpObject::objectLoaded($dict) && $dict->can('edit') && $dict->isEditable()) {
			$onclick = $dict->getLoadValuesListOnclick();

			if ($onclick) {
				$html .= BimpRender::renderIconButton('Editer la liste des valeurs', 'fas_list-ol', $onclick, array(
					'light' => true
				));
			}
		}

		return $html;
	}
}
