<?php

class BimpCGV extends BimpObject
{
	public function canCreate()
	{
		global $user;
		if ($user->admin) return 1;
		else return 0;
	}

	public function canDelete()
	{
		return $this->canCreate();
	}

	static function getCGV($type, $secteur, $date_piece, $id_centre)
	{
		$filtre = array(
			'types_pieces' => array(
				'part_type' => 'middle',
				'part' => '[' . $type . ']'
			),
			'secteurs' => array(
				'part_type' => 'middle',
				'part' => '[' . $secteur . ']'
			),
			'date_start' => array(
				'operator' => '<=',
				'value' => $date_piece
			)
		);
		if( $secteur == 'S' && $id_centre > 0)	{
			$filtre['id_centre'] = $id_centre;
		}
		return BimpCache::findBimpObjectInstance('bimpcore', 'BimpCGV', $filtre, true, false, false, 'date_start');
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		$errors = parent::create($warnings, $force_create);
		if(empty($errors))	{
			$file = BimpTools::getPostFieldValue('file', array(), 'array');
			if (!empty($file)) {
				$files_dir = $this->getFilesDir();
				BimpTools::moveTmpFiles($warnings, $file, $files_dir, 'CGV_file.pdf');
			}
		}
	}

	public function dispayPdfButton()
	{
		$file = $this->getFilesDir() . 'CGV_file.pdf';
		$html = '';
		if (file_exists($file)) {
			$url = $this->getFileUrl('CGV_file.pdf');
			if ($url) {
				$html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
				$html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Fichier des CGV';
				$html .= '</span>';
			}
		}
		return $html;
	}
}
