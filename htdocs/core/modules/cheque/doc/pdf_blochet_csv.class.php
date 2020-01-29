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

require_once(DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php');
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

            $this->object = $object;
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
                $this->out .= $this->addCell('Facture');
                $this->out .= $this->addCell('Code Client');
                $this->out .= $this->addCell('Code Compta');
                $this->out .= $this->addCell('Libelle Client');
                $this->out .= $this->addCell('Total Facture');
                $this->out .= $this->addCell('Montant Pai Fact');
                $this->out .= $this->addCell('Restant Pai Fact');
                $this->out .= $this->addCell('Ref paiement');
                $this->out .= $this->addCell('Emeteur');
                $this->out .= $this->addCell('Banque');
                $this->out .= $this->addCell('Montant Cheque');
                $this->out .= $this->addCell('Num Cheque', 'last');

                $this->out .= $this->sautLigne;

                $sql = $this->db->query("SELECT *, pf.amount as paifact, b.amount as paitot, f.rowid as facid, p.ref as refP FROM `llx_paiement` p, llx_bank b, llx_paiement_facture pf, llx_facture f, llx_societe s WHERE s.rowid = f.fk_soc AND f.rowid = fk_facture AND p.`fk_bank` = b.rowid AND`fk_bordereau` = ".$this->object->id." AND pf.`fk_paiement` = p.rowid ORDER by fk_bank");
                    
                $memoireIdBank = $total = $i = 0;   
		while($ln = $this->db->fetch_object($sql))
		{
                    $facObject = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $ln->facid);
                    $restant = $facObject->getRemainToPay(true);
                    $facture = $facObject->getData('ref');
                    $totFact = $facObject->getData('total_ttc');
                    
                    $codeCli = $ln->code_client;
                    $codeCompta = $ln->code_compta;
                    $libCli = $ln->nom;
                    $paifact = $ln->paifact;
                    
                    
                    
                    
                    if($memoireIdBank != $ln->fk_bank){
                        $i++;
                        $memoireIdBank = $ln->fk_bank;
                        $numCheque = $ln->num_chq;//$this->lines[$j]->num_chq?$this->lines[$j]->num_chq:'';
                        $banque = $ln->banque;
                        $emeteur = $ln->emetteur;
                        $montant = $ln->paitot;
                        $total += $montant;
                        $refP = $ln->refP;
                    }
                    else{
                        $numCheque = $banque = $emeteur = $montant = $refP = "";
                    }
                    
                    
                    $this->out .= $this->addCell($facture);
                    $this->out .= $this->addCell($codeCli);
                    $this->out .= $this->addCell($codeCompta);
                    $this->out .= $this->addCell($libCli);
                    $this->out .= $this->addCell($this->toPriceCsv($totFact));
                    $this->out .= $this->addCell($this->toPriceCsv($paifact));
                    $this->out .= $this->addCell($this->toPriceCsv ($restant));
                    $this->out .= $this->addCell($refP);
                    $this->out .= $this->addCell($emeteur);
                    $this->out .= $this->addCell($banque);
                    $this->out .= $this->addCell(price($montant));
                    $this->out .= $this->addCell($numCheque, 'last');
                    
                    $this->out .= $this->sautLigne;
		}
            
            $this->out .= $this->sautLigne;
            
            $this->out .= $this->addCell('Total');
            $this->out .= $this->addCell($i);
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell('');
            $this->out .= $this->addCell(price($total));
            $this->out .= $this->addCell('', 'last');
	}
        
        public function addCell($text, $option = ''){
            $return = $this->protec.$text.$this->protec;
            if($option != 'last')
                $return .= $this->sep;
            return $return;
        }
        
        
        public function toPriceCsv($number){
            return str_replace(".", ",", round($number,2));
        }

}

