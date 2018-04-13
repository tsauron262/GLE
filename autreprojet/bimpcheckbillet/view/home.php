<?php

include_once 'header.php';
include_once 'footer.php';

$arrayofjs = array('../js/home.js', '../js/annexes.js');

printHeader('Accueil', $arrayofjs);


print '
    <body>
    <fieldset class="container_form">
    <legend><span>Evènements<span></legend>
    <div id="container_event" class="container">
  <div class="clearfix"></div>
  <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  </div>
  </div>
</div>
</body>';
