<?php

class Bimp_SocBankAccount extends BimpObject
{

    public function displayRib()
    {
        $html = '';

        $err = array();
        $this->isValid($err);
        $class = 'objectSubList';

        $htmlSup = '';
        if (count($err)) {
            $class = 'danger';
            $htmlSup .= '<span class="objectIcon bs-popover"';
            $htmlSup .= BimpRender::renderPopoverData(implode(' - ', $err));
            $htmlSup .= '>';
            $htmlSup .= BimpRender::renderIcon('fas_exclamation-triangle', 'danger');
            $htmlSup .= '</span>';
        }
        $html .= $htmlSup;

        $html .= '<table class="' . $class . '">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Prefix</th>';
        $html .= '<th>Banque</th>';
        $html .= '<th>Guichet</th>';
        $html .= '<th>N°</th>';
        $html .= '<th>Clé</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>' . $this->getData('iban_prefix') . '</td>';
        $html .= '<td>' . $this->getData('code_banque') . '</td>';
        $html .= '<td>' . $this->getData('code_guichet') . '</td>';
        $html .= '<td>' . $this->getData('number') . '</td>';
        $html .= '<td>' . $this->getData('cle_rib') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';

        $html .= $htmlSup;

        return $html;
    }
    
    
    public function renderExportedField()
    {
        $html = $this->displayData('exported');
        $html .= ' <i class="far fa5-eye rowButton bs-popover" onClick="' . $this->getJsLoadModalCustomContent('renderEcritureTra', 'Ecriture TRA de ' . $this->getRef()) . '" ></i>';
        return $html;
    }
    
    public function renderEcritureTra()
    {
        $html = '';

            viewEcriture::setCurrentObject($this);
            $html .= viewEcriture::display();
        

        return $html;
    }


    public function displayRum()
    {
        if ($this->isLoaded()) {
            $rum = $this->getData('rum');

            if (!$rum) {
                $errors = array();
                $rum = $this->getNumSepa($errors);

                if (!$rum && !count($errors)) {
                    $errors[] = 'Erreur inconnue';
                }

                if (count($errors)) {
                    return BimpRender::renderAlerts($errors, 'Echec de la création du code RUM');
                }

                $this->updateField('rum', $rum);
            }

            return $rum;
        }

        return '';
    }

    // Rights

    public function isEditable($force_edit = false, &$errors = array())
    {
        return !$this->getData('exported') || !$this->isValid();
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        return !$this->getData('exported');
    }

    public function getCodePays()
    {
        $code = substr(str_replace(" ", "", $this->getData('iban_prefix')), 0, 2);
        if ($code == 'FR')
            return 'FRA';
        elseif ($code == 'BE')
            return 'BEL';
    }

    public function getDevise()
    {
        $codeP = $this->getCodePays();
        $zone_euro = array('FRA', 'BEL');
        if (in_array($codeP, $zone_euro))
            return 'EUR';
        else
            return '';
    }

    // return boolean:

    public function getIban($withEspace = true)
    {
        if ($withEspace)
            $sep = ' ';
        else
            $sep = '';
        $return = substr(str_replace(" ", "", $this->getInitData('iban_prefix')), 0, 40) . $sep;
        $return .= str_replace(" ", "", $this->getInitData('code_banque')) . $sep;
        $return .= str_replace(" ", "", $this->getInitData('code_guichet')) . $sep;
        $return .= str_replace(" ", "", $this->getInitData('number')) . $sep;
        $return .= str_replace(" ", "", $this->getInitData('cle_rib')) . $sep;

        return $return;
    }

    static function verif_rib($code_banque, $code_guichet, $num_compte, $cle)
    {
        $coef = array(62, 34, 3);
        //concatenation des differents codes. 
        $rib = $code_banque . $code_guichet . $num_compte . $cle;
        $rib = strtolower($rib);
        //on remplca les eventuelles lettres par des chiffres. 
        $rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz",
                     "12345678912345678923456789");

        // séparation du rib en 3 groupes de 7 + 1 groupe de 2. 
        // multiplication de chaque groupe par les coef du tableau 
        for ($i = 0, $s = 0; $i < 3; $i++) {
            $code = substr($rib, 7 * $i, 7);
            $s += (0 + (int) $code) * $coef[$i];
        }

        // Soustraction du modulo 97 de $s à  97 pour obtenir la clé RIB
        $cle_rib = 97 - ($s % 97);
//        die($cle_rib.'pp'.$rib);
        if ($cle_rib == $cle) {
            return 1;
        } else {
            return 0;
        }
    }

    public function isValid(Array &$errors = Array()): bool
    {

//        if(!static::isValidIban($this->getIban(false)))
//            $errors[] = 'Iban invalide '.$this->getIban(false);
        $iban = $this->getIban(false);
        if (strlen($iban) < 27 || strlen($iban) > 34) {
            $errors[] = "Longueur IBAN invalide";
        } elseif (!static::verif_rib($this->getInitData('code_banque'), $this->getInitData('code_guichet'), $this->getInitData('number'), $this->getInitData('cle_rib')))
            $errors[] = "Le RIB sélectionné n'est pas valide, veuillez vérifier qu'il ne comporte pas d'erreurs";

        if ($this->getDevise() == '')
            $errors[] = 'Devise inconnue';


        if ($this->getInitData('rum') == '') {
            $errors[] = "Le RIB n'est pas valide (RUM absent)";
        }

        $this->haveAllParamsForCompta($errors);

        if (!count($errors))
            return (bool) 1;
        return false;
    }

    public function haveAllParamsForCompta(&$errors): void
    {
        if ($this->isLoaded()) {
            if (!$this->getInitData('label'))
                $errors[] = "Le RIB doit contenir un label";
            if (!$this->getInitData('bank'))
                $errors[] = "Le RIB doit contenir un nom de banque";
            if (!$this->getInitData('code_banque'))
                $errors[] = "Le RIB doit contenir un code banque";
            if (!$this->getInitData('code_guichet'))
                $errors[] = "Le RIB doit contenir un code guichet";
            if (!$this->getInitData('number'))
                $errors[] = "Le RIB doit contenir un numéro de compte";
            if (!$this->getInitData('bic'))
                $errors[] = "Le RIB doit contenir un code BIC/SWIFT";
            if (!$this->getInitData('iban_prefix'))
                $errors[] = "Le RIB doit contenir un prefix";
            if (!$this->getInitData('domiciliation'))
                $errors[] = "Le RIB doit contenir une domiciliation";
        } else {
            $errors[] = "ID du RIB absent";
        }
    }

    static function isValidIban($iban)
    {
        /* Régles de validation par pays */
        static $rules = array(
            'AL' => '[0-9]{8}[0-9A-Z]{16}',
            'AD' => '[0-9]{8}[0-9A-Z]{12}',
            'AT' => '[0-9]{16}',
            'BE' => '[0-9]{12}',
            'BA' => '[0-9]{16}',
            'BG' => '[A-Z]{4}[0-9]{6}[0-9A-Z]{8}',
            'HR' => '[0-9]{17}',
            'CY' => '[0-9]{8}[0-9A-Z]{16}',
            'CZ' => '[0-9]{20}',
            'DK' => '[0-9]{14}',
            'EE' => '[0-9]{16}',
            'FO' => '[0-9]{14}',
            'FI' => '[0-9]{14}',
            'FR' => '[0-9]{10}[0-9A-Z]{11}[0-9]{2}',
            'GE' => '[0-9A-Z]{2}[0-9]{16}',
            'DE' => '[0-9]{18}',
            'GI' => '[A-Z]{4}[0-9A-Z]{15}',
            'GR' => '[0-9]{7}[0-9A-Z]{16}',
            'GL' => '[0-9]{14}',
            'HU' => '[0-9]{24}',
            'IS' => '[0-9]{22}',
            'IE' => '[0-9A-Z]{4}[0-9]{14}',
            'IL' => '[0-9]{19}',
            'IT' => '[A-Z][0-9]{10}[0-9A-Z]{12}',
            'KZ' => '[0-9]{3}[0-9A-Z]{3}[0-9]{10}',
            'KW' => '[A-Z]{4}[0-9]{22}',
            'LV' => '[A-Z]{4}[0-9A-Z]{13}',
            'LB' => '[0-9]{4}[0-9A-Z]{20}',
            'LI' => '[0-9]{5}[0-9A-Z]{12}',
            'LT' => '[0-9]{16}',
            'LU' => '[0-9]{3}[0-9A-Z]{13}',
            'MK' => '[0-9]{3}[0-9A-Z]{10}[0-9]{2}',
            'MT' => '[A-Z]{4}[0-9]{5}[0-9A-Z]{18}',
            'MR' => '[0-9]{23}',
            'MU' => '[A-Z]{4}[0-9]{19}[A-Z]{3}',
            'MC' => '[0-9]{10}[0-9A-Z]{11}[0-9]{2}',
            'ME' => '[0-9]{18}',
            'NL' => '[A-Z]{4}[0-9]{10}',
            'NO' => '[0-9]{11}',
            'PL' => '[0-9]{24}',
            'PT' => '[0-9]{21}',
            'RO' => '[A-Z]{4}[0-9A-Z]{16}',
            'SM' => '[A-Z][0-9]{10}[0-9A-Z]{12}',
            'SA' => '[0-9]{2}[0-9A-Z]{18}',
            'RS' => '[0-9]{18}',
            'SK' => '[0-9]{20}',
            'SI' => '[0-9]{15}',
            'ES' => '[0-9]{20}',
            'SE' => '[0-9]{20}',
            'CH' => '[0-9]{5}[0-9A-Z]{12}',
            'TN' => '[0-9]{20}',
            'TR' => '[0-9]{5}[0-9A-Z]{17}',
            'AE' => '[0-9]{19}',
            'GB' => '[A-Z]{4}[0-9]{14}'
        );
        /* On vérifie la longueur minimale */
        if (mb_strlen($iban) < 18) {
            return false;
        }
        /* On récupère le code ISO du pays */
        $ctr = substr($iban, 0, 2);
        if (isset($rules[$ctr]) === false) {
            return false;
        }
        /* On récupère la règle de validation en fonction du pays */
        $check = substr($iban, 4);
        /* Si la règle n'est pas bonne l'IBAN n'est pas valide */
        if (preg_match('~' . $rules[$ctr] . '~', $check) !== 1) {
            return false;
        }
        /* On récupère la chaine qui permet de calculer la validation */
        $check = $check . substr($iban, 0, 4);
        /* On remplace les caractères alpha par leurs valeurs décimales */
        $check = str_replace(
                array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'),
                array('10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35'),
                $check
        );
        /* On effectue la vérification finale */
        return fmod((float) $check, 97) === '1';
    }

    // overrides: 


    public function getNumSepa(&$errors = array())
    {
        if ($this->getData('rum') == "") {
            $new = BimpTools::getNextRef('societe_rib', 'rum', BimpCore::getConf('code_ics').'-', 7, $errors);
            return $new;
        }
        return $this->getData('rum');
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->set('rum', $this->getNumSepa());
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            // Le create du dol_object n'insert pas les valeurs...
            $errors = $this->update($warnings, $force_create);
        }
        return $errors;
    }

    public function getFileName($signe = false, $ext = '.pdf')
    {
        return $this->getData('fk_soc') . '_' . $this->id . '_sepa' . ($signe ? '_signe' : '') . $ext;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        global $user;
        if ($field == 'rum' && $user->id != 7 && !$user->admin)
            return 0;
        if ($field == 'exported')
            return 0;

        return parent::isFieldEditable($field, $force_edit);
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();
        $this->set('rum', $this->getNumSepa());
        $def = (int) $this->getData('default_rib');

        if (isset($_FILES['file']) && $_FILES['file']['name'] != '') {
            $soc = $this->getChildObject('societe');
            $file_dir = $soc->getFilesDir();

            $oldName = $_FILES['file']['name'];
            $name = $this->getFileName(true, '.' . pathinfo($oldName, PATHINFO_EXTENSION));
            $_FILES['file']['name'] = $name;
            if (file_exists($file_dir . $_FILES['file']['name']))
                $errors[] = 'Fichier ' . $_FILES['file']['name'] . ' existe déja';



            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            dol_add_file_process($file_dir, 0, 0, 'file');
        }

        $errors = BimpTools::merge_array($errors, parent::update($warnings, $force_update));

        if (!count($errors)) {
            if ($def) {
                $this->db->update($this->getTable(), array(
                    'default_rib' => 0
                        ), 'fk_soc = ' . (int) $this->getData('fk_soc') . ' AND type = \'' . $this->getData('type') . '\' AND rowid != ' . (int) $this->id);
            }
        }

        return $errors;
    }
}
