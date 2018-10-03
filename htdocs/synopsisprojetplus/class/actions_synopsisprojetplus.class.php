<?php

class ActionsSynopsisprojetplus 
{ 
	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
    
    
	function printSearchForm($parameters, &$object, &$action, $hookmanager)
	{
            $this->resprints = '<script type="text/javascript" src="'.DOL_URL_ROOT.'/synopsisprojetplus/js/projetPlus.js"/>';
            return 0;
	}
}