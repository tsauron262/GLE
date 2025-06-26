<?php

class Bimp_Contact_ExtEntity extends Bimp_Contact	{
	public function getFlagImport()
	{
		$html = '';
		$import_key = $this->getData('import_key');
		if ($import_key) {
			if(strpos($import_key, 'IMP_FLO') !== false)
				$html .= '<span class="" title="Importé Florian">';
			else
				$html .= '<span class="success" title="Importé Salesforce">';
			$html .= BimpRender::renderIcon('fas_file-import', 'iconRight');
			$html .= '</span>';
		}
		return $html;
	}
}
