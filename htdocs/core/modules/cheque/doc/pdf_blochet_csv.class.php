<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/cheque/doc/pdf_blochet.class.php
 *	\ingroup    banque
 *	\brief      File to build cheque deposit receipts
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';


/**
 *	Class of file to build cheque deposit receipts
 */
class BordereauChequeBlochet_csv extends ModeleChequeReceipts
{
	var $sep = ";";
        var $protec = '"';
        var $sautLigne = "\n";
        var $out = '';

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "blochet";

	}

	/**
	 *	Fonction to generate document on disk
	 *
	 *	@param	RemiseCheque	$object			Object RemiseCheque			
	 *	@param	string			$_dir			Directory
	 *	@param	string			$number			Number
	 *	@param	Translate		$outputlangs	Lang output object
     *	@return	int     						1=ok, 0=ko
	 */
	function write_file($object, $_dir, $number, $outputlangs)
	{

            $this->Body($pdf, $pagenb, $pages, $outputlangs);

            
            
            $dir = $_dir . "/".get_exdir($number,0,1,0,$object,'cheque').$number;

            if (! is_dir($dir))
            {
                    $result=dol_mkdir($dir);

                    if ($result < 0)
                    {
                            $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
                            return -1;
                    }
            }

            $file = $dir . "/bordereau-".$number.".csv";

            file_put_contents($file, $this->out);
                
                
            if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

            $this->result = array('fullpath'=>$file);
		
            $outputlangs->charset_output=$sav_charset_output;
               // die('fin'.$file);
	    return 1;   // Pas d'erreur
	}



	/**
	 *	Output array
	 *
	 *	@param	PDF			$pdf			PDF object
	 *	@param	int			$pagenb			Page nb
	 *	@param	int			$pages			Pages
	 *	@param	Translate	$outputlangs	Object lang
	 *	@return	void
	 */
	function Body(&$pdf, $pagenb, $pages, $outputlangs)
	{
		$num=count($this->lines);
                    $this->out .= $this->addCell('Num Cheque');
                    $this->out .= $this->addCell('Banque');
                    $this->out .= $this->addCell('Emeteur');
                    $this->out .= $this->addCell('Montant', 'last');
                    
                    $this->out .= $this->sautLigne;
		for ($j = 0; $j < $num; $j++)
		{
                    $numCheque = $this->lines[$j]->num_chq?$this->lines[$j]->num_chq:'';
                    $banque = dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->bank_chq),44);
                    $emeteur = dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq),50);
                    $montant = price($this->lines[$j]->amount_chq);
                    
                    
                    
                    
                    $this->out .= $this->addCell($numCheque);
                    $this->out .= $this->addCell($banque);
                    $this->out .= $this->addCell($emeteur);
                    $this->out .= $this->addCell($montant, 'last');
                    
                    $this->out .= $this->sautLigne;
		}
                
	}
        
        public function addCell($text, $option){
            $return = $this->protec.$text.$this->protec;
            if($option != 'last')
                $return .= $this->sep;
            return $return;
        }

}

