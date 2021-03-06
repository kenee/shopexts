<?php

/***************************************************************************
*                            Dolphin Smart Community Builder
*                              -----------------
*     begin                : Mon Mar 23 2006
*     copyright            : (C) 2006 BoonEx Group
*     website              : http://www.boonex.com/
* This file is part of Dolphin - Smart Community Builder
*
* Dolphin is free software. This work is licensed under a Creative Commons Attribution 3.0 License. 
* http://creativecommons.org/licenses/by/3.0/
*
* Dolphin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the Creative Commons Attribution 3.0 License for more details. 
* You should have received a copy of the Creative Commons Attribution 3.0 License along with Dolphin, 
* see license.txt file; if not, write to marketing@boonex.com
***************************************************************************/

define('BX_SECURITY_EXCEPTIONS', true);
$aBxSecurityExceptions = array(
    'POST.request',
    'GET.request',
    'REQUEST.request',
);

require_once( '../../../inc/header.inc.php' );
require_once( BX_DIRECTORY_PATH_INC . 'design.inc.php' );

//require_once( BX_DIRECTORY_PATH_MODULES . $aModule['path'] . '/classes/' . $aModule['class_prefix'] . 'Module.php');
require_once( BX_DIRECTORY_PATH_CLASSES . 'BxDolModuleDb.php');
require_once( BX_DIRECTORY_PATH_MODULES . 'boonex/open_social/classes/BxOSiModule.php');

check_logged();

$oModuleDb = new BxDolModuleDb();
$aModule = $oModuleDb->getModuleByUri('open_social');

$oBxOSiModule = new BxOSiModule($aModule);

$sAction = $_REQUEST['action'];
$sCodeResult = '';

switch ($sAction) {
	default:
		$sCodeResult = $oBxOSiModule->GenCustomOSiBlock((int)$_REQUEST['ID']);
		break;
}

echo $sCodeResult;

?>
