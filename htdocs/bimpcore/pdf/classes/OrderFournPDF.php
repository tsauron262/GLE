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
        global $langs;
        $this->langs = $langs;

        $this->langs->load("main");
        $this->langs->load("dict");
        $this->langs->load("companies");
        $this->langs->load("bills");
        $this->langs->load("products");
        $this->langs->load("orders");

        parent::__construct($db);

        $this->pdf->addCgvPages = false;

        $this->target_label = 'Fournisseur';
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

        $primary = BimpCore::getParam('pdf/primary', '000000');

        $html .= '<div class="row">';
        $html .= '<span style="font-weight: bold; color: #' . $primary . ';">';
        $html .= 'Livraison :</span><br/>';

        if (BimpObject::objectLoaded($this->bimpCommObject)) {
            switch ($this->bimpCommObject->getData('delivery_type')) {
                case Bimp_CommandeFourn::DELIV_ENTREPOT:
                default:
                    $entrepot = $this->bimpCommObject->getChildObject('entrepot');
                    if (BimpObject::objectLoaded($entrepot)) {
                        if ($entrepot->address) {
                            $html .= $entrepot->address . '<br/>';
                            if ($entrepot->zip) {
                                $html .= $entrepot->zip . ' ';
                            } else {
                                $html .= '<span style="color: #A00000; font-weight: bold">Code postal non défini</span> ';
                            }
                            if ($entrepot->town) {
                                $html .= $entrepot->town;
                            } else {
                                $html .= '<span style="color: #A00000; font-weight: bold">Ville non définie</span>';
                            }
                        } else {
                            $html .= '<span style="color: #A00000; font-weight: bold">Erreur: adresse non définie pour l\'entrepôt "' . $entrepot->label . ' - ' . $entrepot->lieu . '"</span>';
                        }
                    } elseif ((int) $this->bimpCommObject->getData('entrepot')) {
                        $html .= '<span style="color: #A00000; font-weight: bold">Erreur: l\'entrepôt #' . $this->bimpCommObject->getData('entrepot') . ' n\'existe pas</span>';
                    } else {
                        $html .= '<span style="color: #A00000; font-weight: bold">Entrepôt absent</span>';
                    }
                    break;

                case Bimp_CommandeFourn::DELIV_SIEGE:
                    global $mysoc;
                    if (is_object($mysoc)) {
                        if ($mysoc->name) {
                            $html .= '<span style="font-weight: bold">' . $mysoc->name . '</span><br/>';
                        }
                        if ($mysoc->address) {
                            $html .= $mysoc->address . '<br/>';
                        }
                        if ($mysoc->zip) {
                            $html .= $mysoc->zip . ' ';
                        }
                        if ($mysoc->town) {
                            $html .= $mysoc->town;
                        }
                    } else {
                        $html .= '<span style="color: #A00000; font-weight: bold">Erreur: Siège social non configuré</span>';
                    }
                    break;

                case Bimp_CommandeFourn::DELIV_CUSTOM:
                    $address = $this->bimpCommObject->getData('custom_delivery');
                    if ($address) {
                        $address = str_replace("\n", '<br/>', $address);
                        $html .= $address;
                    } else {
                        $html .= '<span style="color: #A00000; font-weight: bold">Adresse non renseignée</span>';
                    }
                    break;

                case Bimp_CommandeFourn::DELIV_DIRECT:
                    $id_contact = (int) $this->bimpCommObject->getIdContactLivraison();
                    if ($id_contact) {
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                        if (BimpObject::objectLoaded($contact)) {
                            $html .= '<span style="font-weight: bold">' . $contact->getData('firstname') . ' ' . $contact->getData('lastname') . '</span><br/>';
                            $html .= $contact->getData('address') . '<br/>';
                            $html .= $contact->getData('zip') . ' ' . $contact->getData('town') . '<br/>';
                            $html .= $contact->displayCountry();
                        } else {
                            $html .= '<span style="color: #A00000; font-weight: bold">Le contact d\'ID ' . $id_contact . ' n\'existe pas</span>';
                        }
                    } else {
                        $html .= '<span style="color: #A00000; font-weight: bold">Contact livraison directe absent</span>';
                    }
                    break;
            }
        }

        $html .= '</div>';

        $html .= '<div style="border-top: solid 1px #' . $primary . ';"></div>';

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
