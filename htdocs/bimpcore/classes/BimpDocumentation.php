<?php

class BimpDocumentation {

    var $lines = array();
    var $errors = array();
    var $pathFile = '';
    var $type = '';
    var $name = '';
    static $lang = 'fr_FR';
    var $menu = array();
    static $nbInstance = 0;
    var $admin = true;
    var $mode = 'link';
    var $idSection = '';
    var $initNiveau = 0;
    var $initPrefMenu = '';

    function __construct($type, $name, $mode = 'link', $idSection = 'princ', &$menu = array()) {
        if (!is_array($menu)){
            $this->initPrefMenu = $menu;
            $this->initialiseArrayMenu($menu);
        }
        $this->name = $name;
        $this->type = $type;
        $this->mode = $mode;
        $this->menu = &$menu;
        $this->idSection = $idSection;
        static::$nbInstance++;
        if (static::$nbInstance > 20) {
            BimpCore::addlog('Attention boucle dans DOC');
            die('Probléme technique, contacté l\'équipe dév');
        }
    }

    private function initialiseArrayMenu(&$menu) {
        $tabT = explode('.', $menu);
        $menu = array();
        for ($i = 1; $i <= 10; $i++) {
            if (isset($tabT[$i - 1])) {
                for ($y = 1; $y <= intval($tabT[$i - 1]); $y++) {
                    $menu[$i][] = '';
                }
                $this->initNiveau = $i - 1;
            }
        }
    }

    public static function renderBtn($name, $text = '') {
        $onClickInit = "docModal.loadAjaxContent($(this), 'loadDocumentation', {name: '" . $name . "'}, 'Doc : " . $name . "', 'Chargement', function (result, bimpAjax) {});";

        $onClickInit .= 'docModal.show();';

        if ($text == '')
            $text = 'Doc : ' . $name;

        $html .= '<span  class="bs-popover" ' . BimpRender::renderPopoverData($text, 'right', true) . '>' . '<button type="button" onclick="' . $onClickInit . '" class="btn btn-default">';
        $html .= BimpRender::renderIcon('fas_info-circle');
        $html .= '</button></span>';

        return $html;
    }

    static function getPathFile($type, $name, $update_file = false) {
        if ($update_file)
            return DOL_DATA_ROOT . '/bimpcore/docs/' . static::$lang . '/' . ($update_file ? 'upd_' : '') . $name . '_' . $type . '.txt';
        else
            return DOL_DOCUMENT_ROOT . '/bimpcore/docs/' . static::$lang . '/' . ($update_file ? 'upd_' : '') . $name . '_' . $type . '.txt';
    }

    function getThisPathFile() {
        $file = static::getPathFile($this->type, $this->name, true);
        if (is_file($file)) {
            if (is_file(static::getPathFile($this->type, $this->name)) && preg_replace('~\R~u', "\r\n", file_get_contents($file)) == preg_replace('~\R~u', "\r\n", file_get_contents(static::getPathFile($this->type, $this->name)))) {
                unlink($file);
            } else
                return $file;
        }
        return static::getPathFile($this->type, $this->name);
    }

    private function getPrefMenu($niveau) {
        $pref = '';
        for ($i = 1; $i <= 10; $i++) {//10 niveau de titres
            if ($i <= $niveau)
                $pref .= (isset($this->menu[$i]) ? count($this->menu[$i]) : 0) . ($i < $niveau ? '.' : '');
            else
                unset($this->menu[$i]);
        }
        return $pref;
    }

    function initLines($initNiveau = 0) {
        $data = $this->getFileData('array');
        $niveau = 0;
        foreach ($data as $idL => $ln) {
            $styleMenu = $styleCore = array();
            $type = '';
            $hash = '';
            if (preg_match('#\{code}([^\[]*) ?#', $ln, $matches)) {
                $ln = '<xmp>' . $matches[1] . '</xmp>';
            } elseif (preg_match('#([^\[]*)?{{([^\[]*)?}}#U', $ln, $matches) && isset($matches[2])) {
                $idSection = $this->idSection . '_' . count($this->lines);
                $this->lines[] = array('div' => array('id' => $idSection));
                if (is_file(static::getPathFile('doc', $matches[2])) || is_file(static::getPathFile('doc', $matches[2], true))) {
                    $child = new BimpDocumentation('doc', $matches[2], $this->mode, $idSection, $this->menu);
                    $pref = $this->getPrefMenu($niveau+1);
//                    echo '<pre>';print_r($this->menu);

                    $child->initLines($niveau);
                    if ($this->admin)
                        $this->lines[] = array('value' => static::btnEditer($matches[2], $idSection, $pref));
                    $this->lines = BimpTools::merge_array($this->lines, $child->lines);
                    $this->traiteIndent($niveau);
                    $pref = $this->getPrefMenu($niveau);
                    $this->errors = BimpTools::merge_array($this->errors, $child->errors);
                } elseif ($this->admin) {
                    $this->lines[] = array('value' => static::btnEditer($matches[2], $idSection, $pref));
                }
                $this->lines[] = array('div' => array('close' => true));
                continue;
            } elseif (preg_match('#([^\[]*)?{{([^\[]*)?}#U', $ln, $matches) && isset($matches[2])) {
                $datas = explode(':', $matches[2]);

                $html = '<img src="' . DOL_URL_ROOT . '/bimpcore/docs/img/' . $this->convertTag($datas[0]) . '"';
                if (isset($datas[1]))
                    $html .= ' width="' . $datas[1] . '"';
                if (isset($datas[2]))
                    $html .= ' height="' . $datas[2] . '"';
                $html .= '/>';
                $ln = str_replace('{{' . $matches[2] . '}', $html, $ln);
            } elseif (preg_match('#(={2,4}) ?([^\[]*) ?#', $ln, $matches)) {
                $ln = $this->traiteLn($matches[2]);
                $niveau = (strlen($matches[1]) - 1) + $initNiveau;
                $type = 'h' . $niveau;
                
                $this->traiteIndent($niveau);
                

                /* gestion menu */
                if ($this->type == 'doc') {
                    $this->menu[$niveau][] = '';
                    $pref = $this->getPrefMenu($niveau);
                    $ln = $pref . ' ' . $ln;
                    $hash = 'menu' . $pref;
                    if ($niveau > 1) {
                        $styleMenu[] = 'text-indent: ' . ($niveau * 10) . 'px;';
//                        $styleCore[] = 'padding-left: ' . ($niveau * 10) . 'px;';
                    }
                }
            } else {
                $ln = $this->traiteLn($ln);
            }

            $this->lines[] = array('balise' => $type, 'value' => $ln, 'styleMenu' => $styleMenu, 'styleCore' => $styleCore, 'hash' => $hash);
        }
    }
    
    function traiteIndent($niveau){
        if($niveau > count($this->menu))//On descend d'un niveau
            for($i=0; $i<($niveau-count($this->menu));$i++)
                $this->lines[] = array('div' => array('class' => 'indent ident'.($i+1+count($this->menu))));
//                $this->lines[] = array('value'=> '+++++' .count($this->menu).' '.$niveau);
        elseif($niveau < count($this->menu))
            for($i=0; $i<(count($this->menu) - $niveau);$i++)
                $this->lines[] = array('div' => array('close' => true));
//                $this->lines[] = array('value'=> '-------' .count($this->menu).' '.$niveau.' '.'indent ident'.$i+1);
    }

    static function convertTag($in) {
        $in = strtolower($in);
        $in = str_replace(' ', '_', $in);
        return $in;
    }

    function getFileData($type = 'text') {
        
        if ($type == 'text'){
            if(is_file($this->getThisPathFile()))
                return file_get_contents($this->getThisPathFile());
            return '';
        }
        elseif ($type == 'array'){
            if(is_file($this->getThisPathFile()))
                return file($this->getThisPathFile(), FILE_IGNORE_NEW_LINES);
            return array();
        }
    }

    function getEditFormDoc() {
        return '<form method="post"><textarea name="html" style="min-height: 501px;width: 100%;">' . $this->getFileData() . '</textarea>'
                . '<br/><input type="submit" value="Valider"/>'
                . '</form>';
    }

    function saveDoc($name, $value) {
        return $this->saveFile('doc', $name, $value);
    }

    function saveFile($type, $name, $value) {
        if (!file_put_contents($this->getPathFile($type, $name, true), $value))
            $this->errors[] = 'Enregistrement impossible : ' . $this->getPathFile($type, $name, true);
    }

    function btnEditer($name, $idSection, $serializedMenu = '[]') {
        if ($this->mode == 'modal') {
            $onClick = "editDocumentation('" . $idSection . "', '" . $name . "', '" . addslashes($serializedMenu) . "');";
        } else
            $onClick = 'window.location = \'' . static::getLink($name, array('action' => 'edit')) . '\'';

        return '<button onclick="' . $onClick . '">' . BimpRender::renderIcon('fas_edit') . $niveau . '</button>';
    }

    function getDoc() {
        return $this->getFileData();
    }

    function displayDoc($mode = 'text') {
        if (!is_file($this->getThisPathFile())) {
            $this->errors[] = 'Doc ' . $this->name . ' introuvable : ' . $this->getThisPathFile();
            return '';
        }

        $this->initLines($this->initNiveau);

        $sections = array();
        $sections['css'] = '<style>
            .indent{
               text-indent: -10px;
               padding-left: 20px;
            }
            h6,h7,h8,h9,h10,h11{
                display:block;
            }
            </style>';

        $sections['menu'] = $this->getMenu();
        
        
        
        $sections['core'] = $this->getCore(($mode == 'text'));


        //impression
        if ($mode == 'text') {
            $sections = array_filter($sections);
            return implode('<br/><br/>', $sections);
        } elseif ($mode == 'array') {
            return array('menu' => $sections['menu'], 'core' => $sections['core']);
        }


//        if ($htmlMenu != '')
//            return $htmlMenu . '<br/><br/>' . $htmlContent;
//        else
//            return $htmlContent;
    }

    static function getLink($name, $params = array()) {
        $params['name'] = $name;
        $newParams = array();
        foreach ($params as $clef => $val) {
            $newParams[] = $clef . '=' . $val;
        }
        return DOL_URL_ROOT . '/bimpcore/docs/test.php?' . implode('&', $newParams);
    }

    function displayInfoPop() {
        $this->initLines();
        return $this->getCore();
    }

    function getMenu() {
        $html = '<div id="' . $this->idSection . '_menu">';
        foreach ($this->lines as $info) {
            if (isset($info['div'])) {
                $html .= '<' . (isset($info['div']['close']) ? '/' : '') . 'div' . (isset($info['div']['id']) ? ' id="' . $info['div']['id'] . '_menu"' : '') . (isset($info['div']['class']) ? ' id="' . $info['div']['class'] . '"' : '') . '>';
            }

            if ($info['balise'] != '') {
                $htmlContent .= '<' . $info['balise'];
                if ($info['hash']) {
                    $html .= '<a href="#' . $info['hash'] . '"' . (count($info['styleMenu']) ? ' style="' . implode(' ', $info['styleMenu']) . '"' : '') . '><' . $info['balise'] . '>' . $info['value'] . '</' . $info['balise'] . '></a>';
                }
            }
        }
        $html .= '</div>';
        return $html;
    }

    function getCore($withDivSection = true) {
//        $this->pOpen = true;
        if($withDivSection)
            $html = '<div id="' . $this->idSection . '">';
        if ($this->admin)
            $html .= static::btnEditer($this->name, $this->idSection, $this->initPrefMenu) . '<br/>';
        foreach ($this->lines as $info) {
            if (isset($info['div'])) {
//                if ($this->pOpen) {
//                    $html .= '</div>';
//                    $this->pOpen = false;
//                }
                $html .= '<' . (isset($info['div']['close']) ? '/' : '') . 'div' . (isset($info['div']['id']) ? ' id="' . $info['div']['id'] . '"' : '') . (isset($info['div']['class']) ? ' class="' . $info['div']['class'] . '"' : '') . '>';
            }


            if ($info['balise'] != '') {
//                if ($this->pOpen) {
//                    $html .= '</div>';
//                }
//                $html .= "<div" . (isset($info['styleCore']) && count($info['styleCore']) ? ' style="' . implode(' ', $info['styleCore']) . '"' : '') . ">";
//                $this->pOpen = true;
                $html .= '<' . $info['balise'];
                if ($info['hash']) {
                    $html .= ' id="' . $info['hash'] . '"';
                }
                $html .= '>' . $info['value'] . '</' . $info['balise'] . '>';
            } elseif($info['value'] != '')
                $html .= $info['value'] . '<br/>';
        }
        if($withDivSection)
            $html .= '</div>';
//        $html .= '<pre>'.print_r($this->lines,1);
        return $html;
    }

    public function traiteLn($chaine) {
        $chaine = preg_replace("#'''([^\[]*) ?'''#U", "<b>\\1</b>", $chaine);
        $chaine = preg_replace("#''([^\[]*) ?''#U", "<i>\\1</i>", $chaine);
        if (preg_match_all('#([^\[]*)?{([^\[]*)?}#U', $chaine, $matches)) {//lien et popup
            if (isset($matches[2])) {
                foreach ($matches[2] as $replace) {
                    $replaceInit = $replace;
                    $replace = static::convertTag($replace);
                    $new = static::traitePopOver($replace, $replaceInit);
                    $new = static::traiteLink($replace, $new, $this->mode);
                    $chaine = str_replace('{' . $replaceInit . '}', $new, $chaine);
                }
            }
        }
        if (preg_match_all('#([^\[]*)?\#([^\[]*)?\#([^\[]*)?\##U', $chaine, $matches)) {//couleur
//            echo '<pre>';print_r($matches);
//            die('ttt');
            if (isset($matches[2])) {
                foreach ($matches[2] as $idT => $couleur) {
                    if (isset($matches[3][$idT])) {
                        $chaine = str_replace('#' . $matches[2][$idT] . '#' . $matches[3][$idT] . '#', '<span style="color:' . $matches[2][$idT] . '">' . $matches[3][$idT] . '</span>', $chaine);
                    }
                }
            }
        }
        $chaine = nl2br($chaine);


        return $chaine;
    }

    static function traitePopOver($str, $text) {
        if (file_exists(static::getPathFile('pop', $str))) {
            $bimpDocumentation = new BimpDocumentation('pop', $str);
            $text = '<span  class="bs-popover" ' . BimpRender::renderPopoverData($bimpDocumentation->displayInfoPop(), 'right', true) . '>' . $text . '</span>';
        }
        return $text;
    }

    static function traiteLink($str, $chaine, $mode = 'link') {
        $lienDoc = DOL_URL_ROOT . '/bimpcore/docs/test.php?name=';
        if (file_exists(static::getPathFile('doc', $str)))
            if ($mode == 'modal') {
                $onClick = "docModal.loadAjaxContent($(this), 'loadDocumentation', {name: '" . $str . "'}, 'Doc : " . $str . "', 'Chargement', function (result, bimpAjax) {});";
                $chaine = '<a onclick="' . $onClick . '">' . $chaine . '</a>';
            } else
                $chaine = '<a href="' . $lienDoc . $str . '">' . $chaine . '</a>';
        return $chaine;
    }

}

?>