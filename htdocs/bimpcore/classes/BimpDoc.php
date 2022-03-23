<?php
class BimpDoc {

    var $lines = array();
    var $errors = array();
    var $pathFile = '';
    var $type = '';
    var $name = '';
    static $lang = 'fr_FR';
    var $menu = array();
    static $nbInstance = 0;
    
    function __construct($type, $name, &$menu = array()) {
        $this->name = $name;
        $this->type = $type;
        $this->menu = &$menu;
        static::$nbInstance++;
        if(static::$nbInstance > 20){
            BimpCore::addlog('Attention boucle dans DOC');
            die('Probléme technique, contacté l\'équipe dév');
        }
    }

    static function getPathFile($type, $name) {
        return DOL_DOCUMENT_ROOT . '/bimpcore/docs/'.static::$lang.'/' . $name . '_' . $type . '.txt';
    }

    function getThisPathFile() {
        return static::getPathFile($this->type, $this->name);
    }

    function initLines($initNiveau = 0) {
        $data = file($this->getThisPathFile($file), FILE_IGNORE_NEW_LINES);
        $niveau = 0;
        foreach ($data as $idL => $ln) {
            $styleMenu = array();
            $type = '';
            $hash = '';
            if (preg_match('#\{code}([^\[]*) ?#', $ln, $matches)) {
                $ln = '<xmp>' . $matches[1] . '</xmp>';
            } elseif (preg_match('#([^\[]*)?{{([^\[]*)?}}#U', $ln, $matches) && isset($matches[2])) {
                $child = new BimpDoc('doc', $matches[2], $this->menu);
                $child->initLines($niveau);
                $this->lines = BimpTools::merge_array($this->lines, $child->lines);
                $this->errors = BimpTools::merge_array($this->errors, $child->errors);
                continue;
            } elseif (preg_match('#([^\[]*)?{{([^\[]*)?}#U', $ln, $matches) && isset($matches[2])) {
                $datas = explode(':', $matches[2]);
                
                $html = '<img src="'.DOL_URL_ROOT.'/bimpcore/docs/img/'.$this->convertTag($datas[0]).'"';
                if(isset($datas[1]))
                    $html .= ' width="'.$datas[1].'"';
                if(isset($datas[2]))
                    $html .= ' height="'.$datas[2].'"';
                $html .= '/>';
                $ln = str_replace('{{'.$matches[2].'}', $html, $ln);
            } elseif (preg_match('#(={2,4}) ?([^\[]*) ?#', $ln, $matches)) {
                $ln = static::traiteLn($matches[2]);
                $niveau = (strlen($matches[1]) - 1) + $initNiveau;
                $type = 'h' . $niveau;
                
                /* gestion menu */
                if($this->type == 'doc'){
                    $this->menu[$niveau][] = $ln;
                    $pref = '';
                    for($i=1;$i<=10;$i++){//10 niveau de titres
                        if($i<=$niveau)
                        $pref .= count($this->menu[$i]).($i<$niveau ? '.' : '');
                        else
                            unset($this->menu[$i]);
                    }
                    $ln = $pref . ' '.$ln;
                    $hash = 'menu'.$pref;
                    if ($niveau > 1)
                        $styleMenu[] = 'text-indent: ' . ($niveau * 10) . 'px;';
                }
                
            } else {
                $ln = static::traiteLn($ln);
            }

            $this->lines[] = array('balise' => $type, 'value' => $ln, 'styleMenu' => $styleMenu, 'hash' => $hash);
        }
    }
    
    static function convertTag($in){
        $in = strtolower($in);
        $in = str_replace(' ', '_', $in);
        return $in;
    }

    function getEditFormDoc() {
        return '<form method="post"><textarea name="html" style="min-height: 501px;width: 100%;">' . file_get_contents($this->getThisPathFile()) . '</textarea>'
                . '<br/><input type="submit" value="Valider"/>'
                . '</form>';
    }

    function saveDoc($name, $value) {
        return $this->saveFile($name . '_doc', $value);
    }

    function saveFile($name, $value) {
//        die('fff'.DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $name . '.txt');
        if(!file_put_contents(DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $name . '.txt', $value))
                $this->errors[] = 'Enregistrement impossible';
    }

    function displayDoc() {
        if (!is_file($this->getThisPathFile())) {
            $this->errors[] = 'Doc ' . $this->name . ' introuvable : ' . $this->getThisPathFile();
            return '';
        }

        $this->initLines();
        
        $sections = array();
        $sections[] = '';
        $sections[] = '<button onclick="window.location = \''.$this->getLink(array('action'=>'edit')).'\'">Editer</button>';
        
        $sections[] = $this->getMenu();
        $sections[] = $this->getCore();
        

        //impression
        $sections = array_filter($sections);
        return implode('<br/><br/>', $sections);


//        if ($htmlMenu != '')
//            return $htmlMenu . '<br/><br/>' . $htmlContent;
//        else
//            return $htmlContent;
    }
    
    function getLink($params = array()){
        $params['name'] = $this->name;
        $newParams = array();
        foreach($params as $clef => $val){
            $newParams[] = $clef.'='.$val;
        }
        return DOL_URL_ROOT.'/bimpcore/docs/test.php?'.implode('&', $newParams);
    }

    function displayInfoPop() {
        $this->initLines();
        return $this->getCore();
    }
    
    

    function getMenu() {
        $html = '';
        foreach ($this->lines as $info) {
            if ($info['balise'] != '') {
                $htmlContent .= '<' . $info['balise'];
                if ($info['hash']) {
                    $html .= '<a href="#' . $info['hash'] . '"' . (count($info['styleMenu']) ? ' style="' . implode(' ', $info['styleMenu']) . '"' : '') . '><' . $info['balise'] . '>' . $info['value'] . '</' . $info['balise'] . '></a>';
                }
            }
        }
        return $html;
    }

    function getCore() {
        $html = '';
        foreach ($this->lines as $info) {
            if ($info['balise'] != '') {
                $html .= '<' . $info['balise'];
                if ($info['hash']) {
                    $html .= ' id="' . $info['hash'] . '"';
                }
                $html .= '>' . $info['value'] . '</' . $info['balise'] . '>';
            } else
                $html .= $info['value'] . '<br/>';
        }
        return $html;
    }

    static function traiteLn($chaine) {
        $chaine = preg_replace("#'''([^\[]*) ?'''#U", "<b>\\1</b>", $chaine);
        $chaine = preg_replace("#''([^\[]*) ?''#U", "<i>\\1</i>", $chaine);
        if (preg_match_all('#([^\[]*)?{([^\[]*)?}#U', $chaine, $matches)) {
            if (isset($matches[2])) {
                foreach ($matches[2] as $replace) {
                    $replaceInit = $replace;
                    $replace = static::convertTag($replace);
                    $new = static::traitePopOver($replace, $replaceInit);
                    $new = static::traiteLink($replace, $new);
                    $chaine = str_replace('{' . $replaceInit . '}', $new, $chaine);
                }
            }
        }
        $chaine = nl2br($chaine);


        return $chaine;
    }

    static function traitePopOver($str, $text) {
        if (file_exists(static::getPathFile('pop', $str))) {
            $bimpDoc = new BimpDoc('pop', $str);
            $text = '<span  class="bs-popover" ' . BimpRender::renderPopoverData($bimpDoc->displayInfoPop(), 'right', true) . '>' . $text . '</span>';
        }
        return $text;
    }

    static function traiteLink($str, $chaine) {
        $lienDoc = DOL_URL_ROOT . '/bimpcore/docs/test.php?name=';
        if (file_exists(static::getPathFile('doc', $str)))
            $chaine = '<a href="' . $lienDoc . $str . '">' . $chaine . '</a>';
        return $chaine;
    }

}

?>