<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

if (empty($extrafieldsobjectkey) && is_object($object)) $extrafieldsobjectkey=$object->table_element;

// Loop to complete the sql search criterias from extrafields
if (! empty($extrafieldsobjectkey) && ! empty($search_array_options) && is_array($search_array_options))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
    if (empty($extrafieldsobjectprefix)) $extrafieldsobjectprefix = 'ef.';
    if (empty($search_options_pattern)) $search_options_pattern='search_options_';

    foreach ($search_array_options as $key => $val)
	{
		$crit=$val;
		$tmpkey=preg_replace('/'.$search_options_pattern.'/', '', $key);
		$typ=$extrafields->attributes[$extrafieldsobjectkey]['type'][$tmpkey];

		if ($crit != '' && in_array($typ, array('date', 'datetime', 'timestamp')))
		{
                    /*mod drsi*/
                    if(is_array($crit))
                        $crit = implode (' ', $crit);
                    /*fmoddrsi*/
			$sql .= natural_search('ef.'.$tmpkey, $crit, $mode_search);
		}
	}
}
