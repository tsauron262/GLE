<?php

function llx_header()
{
    
}

function llx_footer()
{
    
}

$file = $_POST['file'];
$params = isset($_POST['params']) ? $_POST['params'] : array();

$_POST = $params;
$_GET = $params;
$_REQUEST = $params;

define('NOREQUIREMENU', 1);

if (file_exists('../' . $file)) {
    require '../' . $file; 
} else {
    echo '<p class="alert alert-danger">Erreur: le fichier "'.$file.'" n\'existe pas</p>';
}