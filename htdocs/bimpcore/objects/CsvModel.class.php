<?php

class CsvModel extends BimpObject
{

	public function getObj()
	{
		$module = $this->getData('obj_module');
		$obj_name = $this->getData('Obj_name');

		if ($module && $obj_name) {
			return BimpObject::getInstance($module, $obj_name);
		}

		return null;
	}

	public function displayObj()
	{
		$html = '';

		$obj = $this->getObj();

		if (is_a($obj, 'BimpOject')) {
			$icon = $obj->getConf("icon");

			if ($icon) {
				$html .= BimpRender::renderIcon($icon, 'iconLeft');
			}

			$html .= BimpTools::ucfirst($this->getLabel());
		}

		return $html;
	}

	public static function getObjectCsvModelsArray(BimpObject $obj, $include_empty = true, $empty_label = '')
	{
		$k = $obj->module . '_' . $obj->object_name . '_csv_models_array';

		if (!isset(self::$cache[$k])) {
			self::$cache[$k] = array();
			$rows = self::getBdb()->getRows('bimpcore_csv_model', 'obj_module = \'' . $obj->module . '\' AND obj_name = \'' . $obj->object_name . '\'', null, 'array', array('id', 'name'));

			if (is_array($rows)) {
				foreach ($rows as $r) {
					self::$cache[$k][$r['id']] = $r['name'];
				}
			}
		}

		return self::getCacheArray($k, $include_empty, 0, $empty_label);
	}
}
