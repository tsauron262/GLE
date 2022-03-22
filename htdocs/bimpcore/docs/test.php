<?php

require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

llxHeader();

$file = $_GET['name'];

$path = DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $file . '_doc.txt';
$errors = array();


echo '<a href="https://fr.wikipedia.org/wiki/BBCode#Listes_de_balises">Doc BBCode</a><br/><br/>';


$BimpDoc = new BimpDoc();

if ($file == '')
    $errors[] = 'Pas de doc spécifié';
elseif (!is_file($path))
    $errors[] = 'Doc ' . $file . ' introuvable';
else {
    echo $BimpDoc->displayDoc($file);
}
//BimpTools::printBackTrace(null);


if (count($errors))
    echo BimpRender::renderAlerts($errors);

class BimpDoc {
    var $lines = array();
    function initLines($file){
        $data = file(DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $file . '.txt', FILE_IGNORE_NEW_LINES);
        foreach ($data as $ln) {
            $styleMenu = array();
            $isMenu = false;
            $type = '';
            if (preg_match('#\{code}([^\[]*) ?#', $ln, $matches)) {
                $ln = '<xmp>'.$matches[1].'</xmp>';
            } elseif (preg_match('#(={2,4}) ?([^\[]*) ?#', $ln, $matches)) {
                $ln = static::traiteLn($matches[2]);
                $niveau = (strlen($matches[1]) - 1);
                $type = 'h' . $niveau;
                $isMenu = true;
                if($niveau > 1)
                    $styleMenu[] = 'text-indent: '.($niveau*10).'px;';
            } else {
                $ln = static::traiteLn($ln);
            }

            $this->lines[] = array('balise' => $type, 'value' => $ln, 'isMenu' => $isMenu, 'styleMenu' => $styleMenu);
        }
    }
    
    function displayDoc($file){
        $this->initLines($file.'_doc');
        $htmlMenu = $this->getMenu();
        $htmlContent = $this->getCore();
        
        //impression


        if ($htmlMenu != '')
            return $htmlMenu . '<br/><br/>' . $htmlContent;
        else
            return $htmlContent;
    }
    
    function displayInfoPop($name){
        $this->initLines($name . '_pop');
        return $this->getCore();
    }
    
    function getMenu(){
        $html = '';
        foreach ($this->lines as $idM => $info) {
            if ($info['balise'] != '') {
                $htmlContent .= '<' . $info['balise'];
                if ($info['isMenu']) {
                    $html .= '<a href="#menu' . $idM . '"'.(count($info['styleMenu'])? ' style="'.implode(' ', $info['styleMenu']).'"' : '').'><' . $info['balise'] . '>' . $info['value'] . '</' . $info['balise'] . '></a>';
                }
            }
        }
        return $html;
    }
    
    function getCore(){
        $html = '';
        foreach ($this->lines as $idM => $info) {
            if ($info['balise'] != '') {
                $html .= '<' . $info['balise'];
                if ($info['isMenu']) {
                    $html .= ' id="menu' . $idM . '"';
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
                    $new = static::traitePopOver($replace);
                    $new = static::traiteLink($replace, $new);
                    $chaine = str_replace('{' . $replace . '}', $new, $chaine);

                }
            }
        }
        $chaine = nl2br($chaine);


        return $chaine;
    }
    
    static function traitePopOver($str){
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $str . '_pop.txt')){
            $bimpDoc = new BimpDoc();
            $str = '<span  class="bs-popover" ' . BimpRender::renderPopoverData($bimpDoc->displayInfoPop($str), 'right', true) . '>' . $str . '</span>';
        }
        return $str;
    }
    
    static function traiteLink($str, $chaine){   
        $lienDoc = DOL_URL_ROOT . '/bimpcore/docs/test.php?name=';
        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/docs/files/' . $str . '_doc.txt'))
            $chaine = '<a href="' . $lienDoc . $str . '">' . $chaine . '</a>';
        return $chaine;
    }

}

?>
