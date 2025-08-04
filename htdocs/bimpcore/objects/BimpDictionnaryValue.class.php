<?php

class BimpDictionnaryValue extends BimpObject
{
	public static $classes = array(
		''          => '',
		'info'      => array('label' => 'Information', 'classes' => array('info')),
		'warning'   => array('label' => 'Alerte', 'classes' => array('warning')),
		'danger'    => array('label' => 'Danger', 'classes' => array('danger')),
		'success'   => array('label' => 'Succès', 'classes' => array('success')),
		'important' => array('label' => 'Important', 'classes' => array('important'))
	);

	// Getters droits users :

	// Getters booléens:

	// Getters params:
	public function getCreateJsCallback()
	{
		$id_dictionary = (int) $this->getData('id_dict');

		if ($id_dictionary) {
			return 'onDictionnaryChange(' . $id_dictionary . ')';
		}

		return '';
	}

	public function getUpdateJsCallback() {
		return $this->getCreateJsCallback();
	}

	// Getters array:
	public function getDefaultListExtraHeaderButtons()
	{
		$buttons = array();

		$buttons[] = array(
			'label'   => 'Exporter en SQL',
			'icon'    => 'fas_download',
			'onclick' => $this->getJsActionOnclick('exportSQL', array('id_dict' => $this->parent->id), array(
//				'confirm_msg' => 'Veuillez confirmer'
			))
		);
		return $buttons;
	}
	// Getters données:

	// Affichages:

	// Rendus HTML:

	// Actions:
	public function actionExportSQL($data, &$success)
	{
		$errors = array();
		$warnings = array();
		$success = 'Export terminé';

		$id_dict = $data['id_dict'];
		$dictionnary = BimpCache::getBimpObjectInstance('bimpcore', 'BimpDictionnary', $id_dict);
		$dictionnary_data = $dictionnary->getDataArray();
		unset($dictionnary_data['id']);
		$dictionnary_data['values_params'] = json_encode($dictionnary_data['values_params']);
		$keys = array_keys($dictionnary_data);
		$values = array_values($dictionnary_data);
		$sql = "INSERT INTO llx_bimpcore_dictionnary (" . implode(',', $keys) . ") VALUES (";
		$first = true;
		foreach ($values as $value) {
			if(!$first) {
				$sql .= ", ";
			}
			else {
				$first = false;
			}
			$sql .= "'" . str_replace("'", "\\'", $value) . "'";
		}
		$sql .= ");";
		$sql .= "\n";
		$sql .= "\n";

		$dictionnary_values = $this->db->getRows('bimpcore_dictionnary_value', 'id_dict = ' . $id_dict, null, 'array', array('code','label','icon','class','active','position','extra_data'));
//		$errors[] = '<pre>' . print_r($dictionnary_values, true) . '</pre>';

		$sql .= "INSERT INTO llx_bimpcore_dictionnary_value (id_dict, code, label, icon, class, active, position, extra_data) VALUES ";
		$first = true;
		foreach ($dictionnary_values as $dv) {
			if(!$first) {
				$sql .= ", ";
			}
			else {
				$first = false;
			}
			$sql .= "(LAST_INSERT_ID(), '" . $dv['code'] . "', '" . str_replace("'", "\\'", $dv['label']) . "', '" . $dv['icon'] . "', '" . $dv['class'] . "', '" . $dv['active'] . "', '" . $dv['position'] . "', '" . $dv['extra_data'] . "')";
		}
		$sql .= ";";

		// creation du fichier SQL
		global $conf;
		$dir = $conf->bimpcore->multidir_output[$conf->entity];

		$list_name = $dictionnary->getData('code') . '_list';
		$file_name = $dictionnary->getData('code');

		if (!file_exists($dir . '/' .$list_name))	{
			if (!mkdir($dir . '/' .$list_name))	{
				$errors[] = 'Echec de la création du dossier';
			}
		}
		if (!$errors) {
			if (!file_put_contents($dir . '/'  . $list_name . '/' . $file_name . '.sql', $sql)) {
				$errors[] = 'Echec de la création du fichier "' . $file_name . '"';
			} else {
				$warnings[] = $dir . '/'  . $list_name . '/' . $file_name . '.sql';
				$url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($list_name . '/' . $file_name . '.sql');
				$success_callback = 'window.open(\'' . $url . '\')';
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings,
			'success_callback' => $success_callback
		);
	}
	// Overrides:

}
