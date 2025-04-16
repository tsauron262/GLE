<?php

class BimpDictionnary extends BimpObject
{

	// Droits users :

	public function canCreate()
	{
		return 1;
//		global $user;
//		return (int) $user->admin;
	}

	public function canEdit()
	{
		return $this->canCreate();
	}

	public function canDelete()
	{
		return (int) BimpCore::isUserDev();
	}

	// Getters params :

	public function getListsButtons()
	{
		$buttons = array();

		if ($this->isLoaded($errors)) {
			if ($this->canEdit()) {
				$onclick = $this->getLoadValuesListOnclick();
				if ($onclick) {
					$buttons[] = array(
						'label'   => 'Valeurs',
						'icon'    => 'fas_list-ol',
						'onclick' => $onclick
					);
				}
			}
		}

		return $buttons;
	}

	public function getLoadValuesListOnclick()
	{
		$onclick = '';

		$values_params = $this->getData('values_params');

		if (isset($values_params['children'])) {
			$child_instance = $this->getChildObject($values_params['children']);
			$filters = (isset($values_params['filters']) ? $values_params['filters'] : array());
			$params = array(
				'title'     => 'Valeurs du dictionnaire ' . addslashes($this->getData('name')),
				'id_parent' => $this->id
			);

			if (!empty($filters)) {
				$params['extra_filters'] = $filters;
			}

			$onclick = $child_instance->getJsLoadModalList('dictionnary', $params);
		} elseif (isset($values_params['id_dol_dict']) && (int) $values_params['id_dol_dict']) {
			$onclick = 'window.open(\'' . DOL_URL_ROOT . '//admin/dict.php?id=' . $values_params['id_dol_dict'] . '\');';
		}

		return $onclick;
	}

	// Getters données :

	public function getValuesData($active_only = true, $force_reload = false)
	{
		if ($this->isLoaded()) {
			$code = $this->getData('code');
			if ($code) {
				$cache_key = 'dictionnary_values_data_' . $code;

				if ($active_only) {
					$cache_key .= '_active';
				}

				if (!isset(self::$cache[$cache_key]) || $force_reload) {
					$values_params = $this->getData('values_params');

					if (!$force_reload && BimpCache::cacheServerExists('dictionnary_' . $code)) {
						$values = BimpCache::getCacheServeur('dictionnary_' . $code);
					} else {
						$values = array();

						$key_field = (isset($values_params['key_field']) ? $values_params['key_field'] : 'id');
						$position_field = (isset($values_params['position_field']) ? $values_params['position_field'] : 'position');
						$filters = (isset($values_params['filters']) ? $values_params['filters'] : array());

						if (isset($values_params['children'])) {
							foreach ($this->getChildrenObjects($values_params['children'], $filters, 'position', 'asc') as $val) {
								$val_data = $val->getDataArray(true);

								if (isset($values_params['extra_data'])) {
									$val_extra_data = array();
									if (isset($val_data['extra_data'])) {
										$val_extra_data = $val_data['extra_data'];
										unset($val_data['extra_data']);
									}

									foreach ($values_params['extra_data'] as $data_name => $data_params) {
										$val_data[$data_name] = (isset($val_extra_data[$data_name]) ? $val_extra_data[$data_name] : (isset($data_params['default']) ? $data_params['default'] : ''));
									}
								}

								$values[$val_data[$key_field]] = $val_data;
							}
						} else {
							$table = (isset($values_params['table']) ? $values_params['table'] : '');

							if ($table) {
								$sql = BimpTools::getSqlFullSelectQuery($table, null, $filters, array(), array(
									'order_by' => ($position_field ? : $key_field)
								));

								$rows = $this->db->executeS($sql, 'array');

								if (!empty($rows)) {
									foreach ($rows as $r) {
										$values[$r[$key_field]] = $r;
									}
								}
							}
						}

						BimpCache::setCacheServeur('dictionnary_' . $code, $values);
					}

					if ($active_only) {
						$active_field = (isset($values_params['active_field']) ? : 'active');
						if ($active_field) {
							foreach ($values as $code => $value) {
								if (isset($value[$active_field]) && !(int) $value[$active_field]) {
									unset($values[$code]);
								}
							}
						}
					}

					self::$cache[$cache_key] = $values;
				}

				return self::$cache[$cache_key];
			}
		}

		return array();
	}

	public function getByValue($label, $active_only = true, $createIfNotExist = false)
	{
		if ($this->isLoaded()) {
			$values = $this->getValuesInvertedArray($active_only);
			if (isset($values[$label])) {
				return $values[$label];
			} elseif ($createIfNotExist) {
				$values_params = $this->getData('values_params');
				$position_field = (isset($values_params['position_field']) ? $values_params['position_field'] : 'position');
				$filters = (isset($values_params['filters']) ? $values_params['filters'] : array());

				if (isset($values_params['children'])) {
					$code = urlencode($label);
					$child_instance = BimpObject::createBimpObject($this->module, 'BimpDictionnaryValue', array(
						'id_dict' => $this->id,
						'code'    => $code,
						'label'   => $label
					), true, $errors, $warnings);
					$values = $this->getValuesInvertedArray($active_only, false, '', true);
					if (isset($values[$label])) {
						return $values[$label];
					}
				}
			}
		}

		return null;
	}

	public function getValuesArray($active_only = true, $include_empty = false, $empty_label = '')
	{
		$empty_value = 0; // todo : gérer selon type int ou string

		if ($this->isLoaded()) {
			$code = $this->getData('code');
			if ($code) {
				$cache_key = 'dictionnary_values_array_' . $code;

				if ($active_only) {
					$cache_key .= '_active';
				}

				$values_params = $this->getData('values_params');
				$key_data_type = (isset($values_params['key_data_type']) ? $values_params['key_data_type'] : 'int');

				if ($key_data_type == 'string') {
					$empty_value = '';
				}

				if (!isset(self::$cache[$cache_key])) {
					$values = self::getValuesData($active_only);

					if (!empty($values)) {
						$label_field = (isset($values_params['label_field']) ? $values_params['label_field'] : 'label');

						foreach ($values as $key => $value) {
							if (isset($value['icon']) || isset($value['class'])) {
								self::$cache[$cache_key][$key] = array(
									'label'   => (isset($value[$label_field]) ? $value[$label_field] : $key),
									'icon'    => (isset($value['icon']) ? $value['icon'] : ''),
									'classes' => array((isset($value['class']) ? $value['class'] : ''))
								);
							} else {
								self::$cache[$cache_key][$key] = $value[$label_field];
							}
						}
					}
				}
			}
		}

		return ($include_empty ? array($empty_value => $empty_label) : array());
	}

	public function getValuesInvertedArray($active_only = true, $include_empty = false, $empty_label = '', $forceReload = false)
	{
		$empty_value = 0; // todo : gérer selon type int ou string
		if ($this->isLoaded()) {
			$code = $this->getData('code');
			if ($code) {
				$cache_key = 'dictionnary_values_inverted_array_' . $code;

				if ($active_only) {
					$cache_key .= '_active';
				}

				if (!isset(self::$cache[$cache_key]) || $forceReload) {
					$values = self::getValuesData($active_only, $forceReload);

					if (!empty($values)) {
						$values_params = $this->getData('values_params');
						$label_field = (isset($values_params['label_field']) ? $values_params['label_field'] : 'label');

						foreach ($values as $key => $value) {
							self::$cache[$cache_key][$value[$label_field]] = $key;
						}
					}
				}


				return self::getCacheArray($cache_key, $include_empty, $empty_label, $empty_value);
			}
		}

		return ($include_empty ? array($empty_value => $empty_label) : array());
	}
}
