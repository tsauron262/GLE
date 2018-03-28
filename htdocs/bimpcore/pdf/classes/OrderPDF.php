<?php

class OrderPDF extends BimpDocumentPDF
{

    public static $type = 'order';
    public $commande = null;
    public $doc_type = 'commande';
    public static $doc_types = array(
        'commande' => 'Commande',
        'bl'       => 'Bon de livraison'
    );

    public function __construct($db, $doc_type = 'commande')
    {
        if (!array_key_exists($doc_type, self::$doc_types)) {
            $doc_type = 'commande';
        }
        
        $this->doc_type = $doc_type;

        parent::__construct($db);

        $this->langs->load("orders");
        $this->langs->load("products");
        $this->commande = new Commande($db);
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Commande')) {
            if (isset($this->object->id) && $this->object->id) {
                $this->commande = $this->object;
                $this->commande->fetch_thirdparty();

                global $user;

                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("Order"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("Invoice") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));
            } else {
                $this->errors[] = 'Commande invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune commande spécifiée';
        }

        parent::initData();
    }

    protected function initHeader()
    {
        parent::initHeader();

        global $db, $conf;

        if (isset($this->commande->thirdparty) && !is_null($this->commande->thirdparty)) {
            $soc = $this->commande->thirdparty;
        } elseif (isset($this->commande->socid) && $this->commande->socid) {
            $soc = new Societe($db);
            $soc->fetch($this->commande->socid);
        } else {
            $soc = null;
        }

        // Titre commande:
        $docName = self::$doc_types[$this->doc_type];

        if ($this->sitationinvoice) {
            $docName = $this->langs->transnoentities('InvoiceSituation');
        }

        // Réf commande: 
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->commande->ref);
        if ($this->commande->statut == Facture::STATUS_DRAFT) {
            $docRef = '<span style="color: #800000"> ' . $docRef . ' - ' . $this->langs->transnoentities("NotValidated") . '</span>';
        }

        $rows = '';
        $nRows = 0;

        // Ref. client:
        if ($this->commande->ref_client) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('RefCustomer') . ' : ' . $this->langs->convToOutputCharset($this->commande->ref_client) . '</div>';
            $nRows++;
        }

        // Ref commande de remplacement: 
        $objectidnext = $this->commande->getIdReplacingInvoice('validated');
        if ($this->commande->type == 0 && $objectidnext) {
            $commandeReplacing = new Facture($db);
            $commandeReplacing->fetch($objectidnext);
            $rows .= '<div class="row">' . $this->langs->transnoentities('ReplacementByInvoice') . ' : ' . $this->langs->convToOutputCharset($commandeReplacing->ref) . '</div>';
            $nRows++;
        }

        // Ref commande remplacée
        if ($this->commande->type == 1) {
            $commandeReplaced = new Facture($db);
            $commandeReplaced->fetch($this->commande->fk_commande_source);
            $rows .= '<div class="row">' . $this->langs->transnoentities('ReplacementInvoice') . ' : ' . $this->langs->convToOutputCharset($commandeReplaced->ref) . '</div>';
            $nRows++;
        }

        if ($this->commande->type == 2 && !empty($this->commande->fk_commande_source)) {
            $commandeReplaced = new Facture($db);
            $commandeReplaced->fetch($this->commande->fk_commande_source);
            $rows .= '<div class="row">' . $this->langs->transnoentities('CorrectionInvoice') . ' : ' . $this->langs->convToOutputCharset($commandeReplaced->ref) . '</div>';
            $nRows++;
        }

        // Dates: 
        $rows .= '<div class="row">' . $this->langs->transnoentities('DateInvoice') . ' : ' . dol_print_date($this->commande->date, "day", false, $this->langs) . '</div>';
        $nRows++;

        if (!empty($conf->global->INVOICE_POINTOFTAX_DATE)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DatePointOfTax') . ' : ' . dol_print_date($this->commande->date_pointoftax, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        if ($this->commande->type != 2) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DateDue') . ' : ' . dol_print_date($this->commande->date_lim_reglement, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        // Code client: 
        if (isset($soc->code_client)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('CustomerCode') . ' : ' . $this->langs->transnoentities($soc->code_client) . '</div>';
            $nRows++;
        }

        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $contacts = $this->commande->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $usertmp = new User($db);
                $usertmp->fetch($contacts[0]);
                $rows .= '<div class="row">' . $this->langs->transnoentities('SalesRepresentative') . ' : ' . $usertmp->getFullName($this->langs) . '</div>';
                $nRows++;
            }
        }

        $linkedObjects = pdf_getLinkedObjects($this->commande, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                $refObject = $lo['ref_title'] . ' : ' . $lo['ref_value'];
                if (!empty($lo['date_value'])) {
                    $refObject .= ' / ' . $lo['date_value'];
                }

                $rows .= '<div class="row">' . $refObject . '</div>';
                $nRows++;
            }
        }

        $this->pdf->topMargin = 40;

        if ($nRows > 2) {
            $this->pdf->topMargin += 4 * ($nRows - 2);
        }

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . 'header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }
}

class BLPDF extends OrderPDF
{

    public function __construct($db)
    {
        parent::__construct($db, 'bl');
    }
}
