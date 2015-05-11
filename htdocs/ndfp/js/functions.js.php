<?php

/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
 

/**
 *	\file       htdocs/ndfp/js/functions.php
 *	\ingroup    ndfp
 *	\brief      Javascript functions to create a note
 */

require('../../main.inc.php');

 
$langs->load('ndfp');
$langs->load('main');   


$fk_cat = GETPOST('fk_cat');

$indexes = array();
$rates = array();
$kmExpId = 0;
$coefs = array();
$offsets = array();
$ranges = array();

$sql  = " SELECT e.rowid, e.code, e.fk_tva, t.taux";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva t ON t.rowid = e.fk_tva";
$sql .= " WHERE e.active = 1";                        
$sql .= " ORDER BY e.rowid DESC";
  
$result = $db->query($sql);


if ($result)
{
    $num = $db->num_rows($result);
    
    if ($num)
    {
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $db->fetch_object($result);
            
            $indexes[$i] = $obj->fk_tva;
            //$rates[$i] = (1 - $obj->taux/100);
            
            if ($obj->code == 'EX_KME'){
                $kmExpId = $obj->rowid;
            }                           
        }
    }
    $db->free($result);    	
}

$sql  = " SELECT r.range, t.offset, t.coef";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_range r ON r.rowid = t.fk_range";
$sql .= " WHERE r.active = 1 AND t.fk_cat = ".$fk_cat;                        
$sql .= " ORDER BY r.range ASC";
  
$result = $db->query($sql);


if ($result)
{
    $num = $db->num_rows($result);
    
    if ($num)
    {
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $db->fetch_object($result);
            
            $coefs[$i] = $obj->coef;
            $offsets[$i] = $obj->offset;
            $ranges[$i] = $obj->range;                          
        }
    }
    $db->free($result);    	
}

// Load TVA, use id instead of value
$sql  = "SELECT DISTINCT t.taux, t.rowid, t.recuperableonly";
$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as p";
$sql.= " WHERE t.fk_pays = p.rowid";
$sql.= " AND t.active = 1";
$sql.= " AND p.code IN ('".$mysoc->pays_code."')";
$sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC"; 

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    if ($num)
    {
        for ($i = 0; $i < $num; $i++)
        {
            $obj = $db->fetch_object($result);
            
            $rates[$i] = price2num(1 + $obj->taux/100);
        }
    }
    
    $db->free($result); 
    
}

$db->close();  


?>


function changeStateTTC(){
   var kmExpId = <?php echo $kmExpId; ?>;
   var ttc = document.getElementById("total_ttc");
   var expList = document.getElementById("fk_exp");
   var qty = document.getElementById("qty");
   
   if (expList.options[expList.selectedIndex].value == kmExpId){
        ttc.disabled = true;       
   }else{
       ttc.disabled = false; 
   }
   
   ttc.value = 0;
   qty.value = 0;
   
   computeHT();    
}

function changeTVA(){
   var tvaIds = new Array (<?php echo implode(",", $indexes); ?>);
     
   var expList = document.getElementById("fk_exp");
   var tvaList = document.getElementById("fk_tva");
   var tvaListOptions = tvaList.options;
   var selTvaId = tvaIds[expList.selectedIndex];
        
   var i;

   for (i=0; i < tvaListOptions.length; i++){
        if (tvaListOptions[i].value == selTvaId){
            tvaList.selectedIndex = i; 
            computeHT(); 
        }    
    }        
           
}

function computeHT(){
   var tvaRates = new Array (<?php echo implode(",", $rates); ?>);
   var tvaList = document.getElementById("fk_tva");
   
   var expList = document.getElementById("fk_exp");
   var ht = document.getElementById("total_ht");
   var ttc = document.getElementById("total_ttc");
   
   var ttcvalue = new Number(ttc.value.toString().replace(/,/g, '.').replace(/\s/g, ''));
   
   
   var selTvaRate = tvaRates[tvaList.selectedIndex];

   ht.value = ttcvalue / selTvaRate;         
           
}

function computeTTC(){
   var tvaRates = new Array (<?php echo implode(",", $rates); ?>);
   var tvaList = document.getElementById("fk_tva");
   
   var expList = document.getElementById("fk_exp");
   var ht = document.getElementById("total_ht");
   var ttc = document.getElementById("total_ttc");
   
   
   var selTvaRate = tvaRates[tvaList.selectedIndex];

   ttc.value = ht.value * selTvaRate;         
           
}

function changeHT(e){
   
    var ttc = document.getElementById("total_ttc");
    var ht = document.getElementById("total_ht");
    var tvaRates = new Array (<?php echo implode(",", $rates); ?>);
    var tvaList = document.getElementById("fk_tva");

    var keynum;
    var numcheck = /\d/;
    var ttcvalue;
    
    if(window.event) // IE8 and earlier
   	{
    	keynum = e.keyCode;
   	}
    else if(e.which) // IE9/Firefox/Chrome/Opera/Safari
   	{
    	keynum = e.which;
   	}
    
    var keychar = String.fromCharCode(keynum);
    var keyvalue = ttc.value.toString().replace(/,/g, '.').replace(/\s/g, '');
    
    
    if (numcheck.test(keychar))
    {
        ttcvalue = keyvalue.concat(keychar);
    }
    else
    {
        ttcvalue = keyvalue;
    }
   
       
   var total_ttc = new Number(ttcvalue);
   
   
   var selTvaRate = tvaRates[tvaList.selectedIndex];

   ht.value = total_ttc / selTvaRate; 
            
}

function changeTTC(e){

   var kmExpId = <?php echo $kmExpId; ?>;
   var coefs = new Array (<?php echo implode(",", $coefs); ?>);
   var offsets = new Array (<?php echo implode(",", $offsets); ?>);
   var ranges = new Array (<?php echo implode(",", $ranges); ?>);
   
   var expList = document.getElementById("fk_exp");
   var ttc = document.getElementById("total_ttc");
   var ht = document.getElementById("total_ht");
   
   var qty = document.getElementById("qty");

    var keynum;
    var numcheck = /\d/;
    var qtyvalue;
    
    if(window.event) // IE8 and earlier
   	{
    	keynum = e.keyCode;
   	}
    else if(e.which) // IE9/Firefox/Chrome/Opera/Safari
   	{
    	keynum = e.which;
   	}
    
    var keychar = String.fromCharCode(keynum);
    var keyvalue = qty.value;
    
    
    if (numcheck.test(keychar))
    {
        qtyvalue = keyvalue.concat(keychar);
    }
    else
    {
        qtyvalue = keyvalue;
    }
   
       
   var kms = new Number(qtyvalue);
       
   var i;
   var coef = 0;
   var offset = 0;
   var total = 0;
   
   if (expList.options[expList.selectedIndex].value == kmExpId){
       for (i=0; i < ranges.length; i++){
                    
            if (i < (ranges.length-1)){
                if (kms > ranges[i] && kms < ranges[i+1]){
                    coef = coefs[i];
                    offset = offsets[i]; 
                }             
            }
            
            if (i == (ranges.length - 1)){
                if (kms > ranges[i]){
                    coef = coefs[i];
                    offset = offsets[i];
                }                
            }    
       }
       
       total =  offset + kms * coef;
       ht.value = total;
       
       computeTTC();
   }        
}

  