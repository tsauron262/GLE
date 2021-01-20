<?php

$value = 567;



ini_set('max_execution_time', $value);
echo ini_get('max_execution_time').' devrait être '.$value;
