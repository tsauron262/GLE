<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

class OrderFournPDF extends BimpDocumentPDF
{

    public static $type = 'order_supplier';
    public $commande = null;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("main");
        $this->langs->load("dict");
        $this->langs->load("companies");
        $this->langs->load("bills");
        $this->langs->load("products");
        $this->langs->load("orders");

        $this->pdf->addCgvPages = false;
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'CommandeFournisseur')) {
            if (BimpObject::objectLoaded($this->object)) {
                $this->bimpCommObject = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $this->object->id);

                $this->commande = $this->object;
                $this->commande->fetch_thirdparty();
                $this->thirdparty = $this->commande->thirdparty;

                global $user;


                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject('Commande fournisseur');
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("Invoice") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));
            }
        } else {
            $this->errors[] = 'Aucune commande fournisseur spécifiée';
        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        $docName = 'Commande Fournisseur';
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->commande->ref);

        $this->header_vars['doc_name'] = $docName;
        $this->header_vars['doc_ref'] = $docRef;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= '<div>';

        // Réf fournisseur: 
        if ($this->commande->ref_supplier) {
            $html .= '<span style="font-weight: bold;">Réf. fournisseur : </span>' . $this->langs->convToOutputCharset($this->commande->ref_supplier) . '<br/>';
        }

        // Date: 
        if (!empty($this->commande->date)) {
            $html .= '<span style="font-weight: bold;">Date : </span>' . dol_print_date($this->commande->date, "day", false, $this->langs) . '<br/>';
        }

        // Date de livraison: 
        if (!empty($this->commande->date_livraison)) {
            $html .= '<span style="font-weight: bold;">Date de livraison: </span>' . dol_print_date($this->commande->date_livraison, "day", false, $this->langs) . '<br/>';
        }

        // Code fournisseur: 
        if (isset($this->commande->thirdparty->code_fournisseur)) {
            $html .= '<span style="font-weight: bold;">Code fournisseur : </span>' . $this->langs->transnoentities($this->commande->thirdparty->code_fournisseur) . '<br/>';
        }

        // Objets liés:
        $linkedObjects = pdf_getLinkedObjects($this->commande, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                $refObject = '<span style="font-weight: bold;">' . $lo['ref_title'] . '</span> : ' . $lo['ref_value'];
                if (!empty($lo['date_value'])) {
                    $refObject .= ' / ' . $lo['date_value'];
                }

                $html .= $refObject . '<br/>';
            }
        }

        $html .= '</div>';

        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function getPaymentInfosHtml()
    {
        $html = '<div style="font-size: 7px; line-height: 8px;">';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Conditions de réglement: 
        if ((int) $this->bimpCommObject->getData('fk_cond_reglement')) {
            $html .= '<tr><td>';
            $html .= '<strong>Conditions de réglement: </strong><br/>';
            $html .= $this->bimpCommObject->displayData('fk_cond_reglement', 'default', false, true);
            $html .= '</td></tr>';
        }

        // Mode de réglement: 
        if ((int) $this->bimpCommObject->getData('fk_mode_reglement')) {
            $html .= '<tr><td>';
            $html .= '<strong>Mode de réglement: </strong><br/>';
            $html .= $this->bimpCommObject->displayData('fk_mode_reglement', 'default', false, true);
            $html .= '</td></tr>';
        }

        $html .= '</table></div>';

        return $html;
    }

    public function renderAfterLines()
    {
        
    }

    public function getAfterTotauxHtml()
    {
        return '';
    }
}
