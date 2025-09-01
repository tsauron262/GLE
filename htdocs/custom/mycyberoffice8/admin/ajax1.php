<?php
if (!defined('NOTOKENRENEWAL'))
    define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))
    define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))
    define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))
    define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))
    define('NOCSRFCHECK', '1');
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML'))
    define('NOREQUIREHTML', '1');

require '../../main.inc.php';

$id = GETPOST('id','int');
dol_include_once('categories/class/categorie.class.php');
$objs = [];
$category = new Categorie($db);
$category->id = $id;
$objs = $category->getObjectsInCateg('product');
print count($objs);
exit;
?>