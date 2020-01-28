<?php

class Bimp_Categorie extends BimpObject
{

    // Méthodes statiques: 

    public static function getCategoriesArrayByParent($id_parent, $with_full_path = true, $skiped_levels = array(), $max_levels = null, $level = 0, $path = '')
    {
        $level++;

        if (!is_null($max_levels) && $max_levels < $level) {
            return array();
        }

        $cats = array();

        $rows = self::getBdb()->getRows('categorie', '`fk_parent` = ' . (int) $id_parent, null, 'array', array('rowid', 'label'));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!in_array($level, $skiped_levels)) {
                    $label = '';
                    if ($with_full_path && $path) {
                        $label .= $path . ' >> ';
                    }
                    $label .= $r['label'];
                    $cats[(int) $r['rowid']] = $label;
                }

                if (is_null($max_levels) || $max_levels > $level) {
                    $children = self::getCategoriesArrayByParent((int) $r['rowid'], $with_full_path, $skiped_levels, $max_levels, $level, ($path ? $path . ' >> ' : '') . $label);
                    foreach ($children as $id_child => $child_label) {
                        $cats[(int) $id_child] = $child_label;
                    }
                }
            }
        }

        return $cats;
    }
    
    public static function getCategoriesListByParent($id_parent, $skiped_levels = array(), $max_levels = null, $level = 0)
    {
        $level++;

        if (!is_null($max_levels) && $max_levels < $level) {
            return array();
        }
        
        $cats = array();

        $rows = self::getBdb()->getRows('categorie', '`fk_parent` = ' . (int) $id_parent, null, 'array', array('rowid'));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (!in_array($level, $skiped_levels)) {
                    $cats[] = (int) $r['rowid'];
                }

                if (is_null($max_levels) || $max_levels > $level) {
                    $children = self::getCategoriesArrayByParent((int) $r['rowid'], $skiped_levels, $max_levels, $level);
                    foreach ($children as $id_child => $child_label) {
                        $cats[] = (int) $id_child;
                    }
                }
            }
        }

        return $cats;
    }
    
}
