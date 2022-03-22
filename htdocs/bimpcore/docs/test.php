<?php

require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

llxHeader();

$file = $_GET['name'];

$path = DOL_DOCUMENT_ROOT.'/bimpcore/docs/files/'.$file.'_doc.txt';
$errors = array();


echo '<a href="https://fr.wikipedia.org/wiki/BBCode#Listes_de_balises">Doc BBCode</a><br/><br/>';



if($file == '')
    $errors[] = 'Pas de doc spécifié';
elseif(!is_file($path))
    $errors[] = 'Doc '.$file.' introuvable';
else{
    echo traiteHtml($file.'_doc');
}
//BimpTools::printBackTrace(null);


if(count($errors))
    echo BimpRender::renderAlerts($errors);




function traiteHtml($file){
    $data = file(DOL_DOCUMENT_ROOT.'/bimpcore/docs/files/'.$file.'.txt', FILE_IGNORE_NEW_LINES);
    $content = array();
    $menu = array();
    foreach($data as $ln){
        $isMenu = false;
        $type = '';
        if(preg_match('#\{code}([^\[]*) ?#', $ln, $matches)){
            $ln = $matches[1];
        }
        elseif(preg_match('#(={1,4}) ?([^\[]*) ?#', $ln, $matches)){
                $ln = $matches[2];
                $type = 'h'.(strlen($matches[1])-1);
                $isMenu = true;
        }
        else{
            $ln = BimpCode($ln);
        }
        
        $content[] = array('balise'=>$type, 'value'=>$ln, 'isMenu' => $isMenu);
    }
    
    
    
    
    //impression
    $htmlMenu = $htmlContent = '';
    foreach($content as $idM => $info){
        if($info['balise'] != ''){
            $htmlContent .= '<'.$info['balise'];
            if($info['isMenu']){
                $htmlMenu .= '<a href="#menu'.$idM.'"><'.$info['balise'].'>'.$info['value'].'</'.$info['balise'].'></a>';
                $htmlContent .= ' id="menu'.$idM.'"';
            }
            $htmlContent .= '>'.$info['value'].'</'.$info['balise'].'>';
        }
        else
            $htmlContent .= $info['value'].'<br/>';
    }

    
    if($htmlMenu != '')
        return $htmlMenu.'<br/><br/>'.$htmlContent;
    else
        return $htmlContent;
}




function BimpCode($chaine){
    
    $lienDoc = DOL_URL_ROOT.'/bimpcore/docs/test.php?name=';
    
    
    $chaine = preg_replace("#'''([^\[]*) ?'''#U", "<b>\\1</b>", $chaine);
    $chaine = preg_replace("#''([^\[]*) ?''#U", "<i>\\1</i>", $chaine);
    if(preg_match_all('#([^\[]*)?{([^\[]*)?}#U', $chaine, $matches)){
        if(isset($matches[2])){
            foreach($matches[2] as $replace){
                $new = $replace;
                
                if(file_exists(DOL_DOCUMENT_ROOT.'/bimpcore/docs/files/'.$replace.'_pop.txt'))
                    $new = '<span  class="bs-popover" '.BimpRender::renderPopoverData(traiteHtml($replace.'_pop'), 'right', true).'>'.$new.'</span>';
                if(file_exists(DOL_DOCUMENT_ROOT.'/bimpcore/docs/files/'.$replace.'_doc.txt'))
                    $chaine = str_replace('{'.$replace.'}', '<a href="'.$lienDoc.$replace.'">'.$new.'</a>', $chaine);
                else
                    $chaine = str_replace('{'.$replace.'}', $new, $chaine);
                    
            }
        }
    }
    
//    $chaine = preg_replace("#([^\[]*)?{([^\[]*)?}#U", "<i>\\1<a href='".$lienDoc."\\2'>\\2</a></i>", $chaine);
    $chaine = nl2br($chaine);
    
    
    return $chaine;
}


?>
