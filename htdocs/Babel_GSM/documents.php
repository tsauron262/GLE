<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

if ($user->rights->BabelGSM->BabelGSM->AfficheDocuments !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
$gsm = new gsm($db,$user);

llxHeaderDocuments("", "Dolibarr Documents","",array(0 => "/Babel_GSM/js/dtree.js"),array(0 => "/Babel_GSM/css/Babel_GSM.css", 1 => "/Babel_GSM/js/dtree.css"));
print $gsm->MainMenu();

print "<SCRIPT type='text/javascript'>";
print<<<EOF
var MenuDisplay = "false";
function MenuDisplayCSS()
{
    if (MenuDisplay=="false")
    {
        document.getElementById('menuDiv').style.display="block";
        MenuDisplay="true";
    } else {
        document.getElementById('menuDiv').style.display="none" ;
        MenuDisplay="false";
    }
}

function DisplayDet(inpt)
{
    location.href=inpt+".php";

}

EOF;
print "\n</SCRIPT>";




 //lie a l'ecm

//liste toutes les catégories
$cat = array(0=> "propale",
             1=> "facture",
             2=> "commande",
             3=> "banque",
             4=> "compta",
             5=> "expedition",
             6=> "facture",
             7=> "ficheinter",
             8=> "fournisseur",
             9=> "logo",
             10=> "rapport",
             11=> "societe",
             12 => "taxes",
             13 => "users");

//Categorie Manuel
$cat=array();

$catMan = array();
$dir = DOL_DATA_ROOT . "/ecm/";
//parseDir($dir,&$catMan,1);

//require('Var_Dump.php'); // make sure the pear package path is set in php.ini
//Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));

$catMan = scan_directory_recursively($dir,FALSE,array(0=>"meta",1=>"tmp"));

//Var_Dump::display($catMan);
//dtree
print "<SCRIPT type='text/javascript'>";
print <<<EOF

a = new dTree('a');
a.add(0,-1,'Racine','javascript: void(0);');
EOF;
$iter = 1;
print "\n";
foreach ($catMan as $key=>$val)
{
    if ($val['kind'] == "directory")
    {
        print addtodtree($iter,0,$val['name'],$val['path']);
        $parent = $iter;
        $iter++;
        foreach ($val["content"] as $key1 => $val1)
        {
            print addtodtree($iter,$parent,$val1['name'],$val1['path']);
            $iter++;
        }
    } else {
        print addtodtree($iter,0,$val['name'],$val['path']);
        $iter++;
    }
}
$parent = 0;
//ajoute les autos
foreach ($cat as $key1=>$val1)
{
    $dir = DOL_DATA_ROOT ."/". $val1;
    $catAuto=array();
    $catAuto = scan_directory_recursively($dir,FALSE,array("meta"=>"meta","tmp"=>"tmp"));
    print addtodtree($iter,0,$val1,$dir);
    $parentmain=$iter;
    $iter++;
    foreach ($catAuto as $key=>$val)
    {
        $name = $val['name'];
        if ($val1 == "societe" && preg_match('/^[0-9]*$/',$val['name']))
        {
            $soc = new Societe($db);
//            var_dump($soc);
            $name = $soc->get_nom($val['name']);
        }
        if ($val['kind'] == "directory")
        {
            print addtodtree($iter,$parentmain,$name,$val['path'],true);
            $parent = $iter;
            $iter++;
            foreach ($val["content"] as $key1 => $val1)
            {
                print addtodtree($iter,$parent,$val1['name'],$val1['path']);
                $iter++;
            }
        } else {
            print addtodtree($iter,$parentmain,$val['name'],$val['path']);
            $iter++;
        }
    }
}

$gsm->jsCorrectSize(true);



function addtodtree($iter,$parent,$name,$path,$isDir=false)
{
    if ($isDir)
    {
        return( "a.add(".$iter.",$parent,'".$name."',\"javascript:alert('".$path."')\",'','','img/folder.gif');\n");
    } else {
        return( "a.add(".$iter.",$parent,'".$name."',\"javascript:alert('".$path."')\");\n");
    }

}
print <<<EOF
document.write(a);

EOF;
print "</SCRIPT>";
//
//print "<TABLE>";
//foreach($catMan as $dist=>$arrValue)
//{
//    print "<TR><TD colspan=2>".$dist;
//    foreach ($arrValue as $key=>$val)
//    {
//        print "<TR><TD>&nbsp;<TD>".$val["dir"]."<TD>".$val["parent"];
//    }
//}

 //1 liste tous les fichiers par categories
 //2 les proposes au telechargement
//Liste de tous les documents generables

function scan_directory_recursively($directory, $filter=FALSE, $exclude=FALSE)
 {
     // if the path has a slash at the end we remove it here
     if(substr($directory,-1) == '/')
     {
         $directory = substr($directory,0,-1);
     }

     // if the path is not valid or is not a directory ...
     if(!file_exists($directory) || !is_dir($directory))
     {
         // ... we return false and exit the function
         return FALSE;

     // ... else if the path is readable
     }elseif(is_readable($directory))
     {
         // we open the directory
         $directory_list = opendir($directory);

         // and scan through the items inside
         while (FALSE !== ($file = readdir($directory_list)))
         {
             // if the filepointer is not the current directory
             // or the parent directory
             if($file != '.' && $file != '..')
             {
                 // we build the new path to scan
                 $path = $directory.'/'.$file;

                 // if the path is readable
                 if(is_readable($path))
                 {
                     // we split the new path by directories
                     $subdirectories = explode('/',$path);

                     // if the new path is a directory
                     if(is_dir($path))
                     {
                         // add the directory details to the file list
                         $directory_tree[] = array(
                             'path'    => $path,
                             'name'    => end($subdirectories),
                             'kind'    => 'directory',

                             // we scan the new path by calling this function
                             'content' => scan_directory_recursively($path, $filter,$exclude));

                     // if the new path is a file
                     }elseif(is_file($path))
                     {
                         // get the file extension by taking everything after the last dot
                         $extension = end(explode('.',end($subdirectories)));

                         // if there is no filter set or the filter is set and matches
                         if($filter === FALSE || $filter == $extension)
                         {
                            if ( !in_array($extension,$exclude) )
                             // add the file details to the file list
                             $directory_tree[] = array(
                                 'path'      => $path,
                                 'name'      => end($subdirectories),
                                 'extension' => $extension,
                                 'size'      => filesize($path),
                                 'kind'      => 'file');
                         }
                     }
                 }
             }
         }
         // close the directory
         closedir($directory_list);

         // return file list
         return $directory_tree;

     // if the path is not readable ...
     }else{
         // ... we return false
         return FALSE;
     }
 }
 // ------------------------------------------------------------


?>
