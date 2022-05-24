<?php

class adminController extends BimpController
{

    public function renderTestTab()
    {
        $html = "<div id='retourTestSpeed'></div>";
        $html .= "<br/><br/>Google : <span id='retourGoogle'></span> s";
        $html .= "<br/><br/>Php : <span id='retourPhp'></span> s";
        $html .= "<br/><br/>Mysql : <span id='retourMysql'></span> s";
        $html .= "<br/><br/>Fichier : <span id='retourFile'></span> s";
        $html .= "<br/><br/>Transfert : <span id='retourTransfert'></span> s";
        $html .= "<br/><br/>Total : <span id='retourTotal'></span> s";
        $html .= "<br/><br/>Info : <span id='retourInfo'></span>";
        $html .= "<br/><br/><button onClick='goTest();'>Re-Tester</button>";
        $html .= "<script>";
        $html .= "";

        $html .= "function displayResult(google, php, mysql, file, total, info){"
                . "$('#retourGoogle').html(google);"
                . "$('#retourPhp').html(php);"
                . "$('#retourMysql').html(mysql);"
                . "$('#retourFile').html(file);"
                . "$('#retourTransfert').html(total - mysql - php - google - file);"
                . "$('#retourTotal').html(total);"
                . "$('#retourInfo').html(info);"
                . "}"
                . "function goTest(){"
                . "timeDeb = new Date().getTime();"
                . "$('#retourGoogle').html('*');"
                . "$('#retourPhp').html('*');"
                . "$('#retourMysql').html('*');"
                . "$('#retourFile').html('*');"
                . "$('#retourTransfert').html('*');"
                . "$('#retourTotal').html('*');"
                . "$('#retourInfo').html('*');"
                . "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpTest'}, 'testSpeed', {}, null, null, function(){}, null, null, false);"
                . "}";

        $html .= "goTest();";
        $html .= "</script>";

        return $html;
    }
}
