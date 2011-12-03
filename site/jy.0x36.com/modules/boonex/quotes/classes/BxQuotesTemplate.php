<?php
/***************************************************************************
*                            Dolphin Smart Community Builder
*                              -------------------
*     begin                : Mon Mar 23 2006
*     copyright            : (C) 2007 BoonEx Group
*     website              : http://www.boonex.com
* This file is part of Dolphin - Smart Community Builder
*
* Dolphin is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License as published by the
* Free Software Foundation; either version 2 of the
* License, or  any later version.
*
* Dolphin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
* You should have received a copy of the GNU General Public License along with Dolphin,
* see license.txt file; if not, write to marketing@boonex.com
***************************************************************************/

bx_import('BxDolModuleTemplate');

/*
 * Quotes module View
 */
class BxQuotesTemplate extends BxDolModuleTemplate {
	/**
	* Constructor
	*/
	function BxQuotesTemplate(&$oConfig, &$oDb) {
		parent::BxDolModuleTemplate($oConfig, $oDb);

		$this->_aTemplates = array('unit', 'adm_unit');
	}

	function loadTemplates() {
	    parent::loadTemplates();
	}

	function parseHtmlByName ($sName, &$aVars) {        
		return parent::parseHtmlByName ($sName.'.html', $aVars);
	}
}

?>