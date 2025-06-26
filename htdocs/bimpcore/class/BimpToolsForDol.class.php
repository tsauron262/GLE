<?php
require_once(DOL_DOCUMENT_ROOT.'/bimpcommercial/objects/Bimp_Facture.class.php');

class BimpToolsForDol extends BimpTools{
    public function __construct($db) {

        require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';

        require_once (DOL_DOCUMENT_ROOT.'/synopsistools/SynDiversFunction.php');

//        return parent::__construct('bimpcore', 'BimpTools');
    }

	public function wget($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$this->output .= $result;
		return 0;
	}
}
