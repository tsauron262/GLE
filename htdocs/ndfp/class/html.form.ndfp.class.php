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
 *	\file       htdocs/ndfp/class/html.form.ndfp.class.php
 *  \ingroup    ndfp
 *	\brief      File of class with all html predefined components
 */


/**
 *	\class      NdfpForm
 *	\brief      Class to manage generation of HTML components
 *	\remarks	Only common components must be here.
 */
class NdfpForm
{
    var $db;
    var $error;




    /**
     * Constructor
     * @param      $DB      Database handler
     */
    function NdfpForm($DB)
    {
        $this->db = $DB;
    }

    /**
     *    Return combo list of vehicules category
     *    @param     selected         Id preselected category
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_cat($selected='',$htmlname='fk_cat', $htmloption='')
    {
        global $conf, $langs;

         // Get categories
        $parentCats = array();
        $catsGroupByParent = array();
        $formconfirm = '';
        
        
        $sql  = " SELECT c.rowid, c.label, c.fk_parent";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat c";
        $sql .= " WHERE c.active = 1";
        $sql .= " ORDER BY c.rowid ASC";
        
        dol_syslog("NdfpForm::select_cat sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        

               
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    

                    if ($obj->fk_parent == 0)
                    {
                        $parentCats[$obj->rowid] = $obj;
                    }
                    else
                    {
                        $catsGroupByParent[$obj->fk_parent][] = $obj;
                    } 
                         
                    $i++;
                }
            }
        }
        

        //Build select
        $select = '<select name = "'.$htmlname.'" '.$htmloption.'>';
        $select .= '<option value="0"></option>'; 
        foreach ($parentCats as $catid => $parent)
        {
            if (isset($catsGroupByParent[$parent->rowid]))
            {
                $childCats = $catsGroupByParent[$parent->rowid];
                
                $select .= '<optgroup label="'.$langs->trans($parent->label).'">';
                foreach ($childCats as $childCat)
                {
                   $select .= '<option value="'.$childCat->rowid.'" '.($childCat->rowid == $selected ? 'selected="selected"' : '').'>'.$langs->trans($childCat->label).'</option>';
                }
                
                $select .= '</optgroup>';                
            }
            else
            {
                $select .= '<option value="'.$catid.'" '.($catid == $selected ? 'selected="selected"' : '').'>'.$langs->trans($parent->label).'</option>';
            }
        }
        
        $select .= '</select>';

        return $select;
    }

    /**
     *    Return combo list of VAT rates
     *    @param     selected         Id preselected VAT
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_tva($selected='',$htmlname = 'fk_tva', $htmloption='')
    {
        global $conf, $langs, $mysoc;

         // Get rates
        
        $rates = array();
        
        // Load TVA, use id instead of value
        $sql  = "SELECT DISTINCT t.taux, t.rowid, t.recuperableonly";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as p";
        $sql.= " WHERE t.fk_pays = p.rowid";
        $sql.= " AND t.active = 1";
        $sql.= " AND p.code IN ('".$mysoc->pays_code."')";
        $sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC"; 
    
        dol_syslog("NdfpForm::select_tva sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $this->db->fetch_object($result);
                    
                    $rates[$i]  = $obj;
                }
            }
            
            $this->db->free($result); 
            
        }
        

        //Build select
        $select = '<select id = "'.$htmlname.'" name="'.$htmlname.'" '.$htmloption.'>';
        foreach ($rates as $rate)
        {
            $select .= '<option value="'.$rate->rowid.'" '.($rate->rowid == $selected ? 'selected="selected"' : '').'>'.$rate->taux.'%</option>';           
        }
        $select .= '</select>';

        return $select;
    }
    
    /**
     *    Return combo list of expenses
     *    @param     selected         Id preselected expense
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_expense($selected='',$htmlname = 'fk_exp', $htmloption='')
    {
        global $conf, $langs;

         // Get expenses
        
        $expenses = array();
        
        $sql  = " SELECT e.rowid, e.code, e.fk_tva, e.label, t.taux";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp e";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva t";
        $sql .= " ON e.fk_tva = t.rowid WHERE e.active = 1";                        
        $sql .= " ORDER BY e.rowid DESC";
        
        dol_syslog("NdfpForm::select_expense sql=".$sql, LOG_DEBUG);
    
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $this->db->fetch_object($result);
                    
                    $expenses[$i]  = $obj;
                }
            }
            
            $this->db->free($result); 
            
        }
        

        //Build select
        $select = '<select id="'.$htmlname.'" name="'.$htmlname.'" '.$htmloption.'>';
        foreach ($expenses as $expense)
        {
            $select .= '<option value="'.$expense->rowid.'" '.($expense->rowid == $selected ? 'selected="selected"' : '').'>'.$langs->trans($expense->label).'</option>';           
        }
        $select .= '</select>';

        return $select;
    }
    
    /**
     *  \brief Return all categories name indexed by id
     *  @return     array      labels
     */
    function get_cats_name()
    {
        global $langs;
        
        $cats = array();
        
        $sql  = " SELECT c.rowid, p.label AS plabel, c.label AS clabel";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat AS c";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_cat AS p ON p.rowid = c.fk_parent";
        
        dol_syslog("NdfpForm::get_cats_name sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);        
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            
            if ($num)
            {
                while($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    
                    $cats[$obj->rowid] = $langs->trans($obj->plabel) .' - '. $langs->trans($obj->clabel);
                    $i++;                    
                }
            }
        }
        
        return $cats;
        
    }        
}

?>
