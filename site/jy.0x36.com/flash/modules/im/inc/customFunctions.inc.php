<?
/***************************************************************************
*
* IMPORTANT: This is a commercial product made by BoonEx Ltd. and cannot be modified for other than personal usage.
* This product cannot be redistributed for free or a fee without written permission from BoonEx Ltd.
* This notice may not be removed from the source code.
*
***************************************************************************/
require_once(BX_DIRECTORY_PATH_INC . "utils.inc.php");
require_once(BX_DIRECTORY_PATH_CLASSES . "BxDolInstallerUtils.php");
require_once(BX_DIRECTORY_PATH_CLASSES . "BxDolPermalinks.php");

function getUserVideoLink()
{
	global $sRootURL;
	if(BxDolInstallerUtils::isModuleInstalled("videos"))
	{
		$oDolPermalinks = new BxDolPermalinks();
		return $sRootURL . $oDolPermalinks->permalink("modules?r=videos/") . "browse/owner/#nick#";
	}
	return "";
}

function getUserMusicLink()
{
	global $sRootURL;
	if(BxDolInstallerUtils::isModuleInstalled("sounds"))
	{
		$oDolPermalinks = new BxDolPermalinks();
		return $sRootURL . $oDolPermalinks->permalink("modules?r=sounds/") . "browse/owner/#nick#";
	}
	return "";
}

function getBlockedUsers($sBlockerId)
{
	$aUsers = array();
	$rResult = getResult("SELECT `Profile` FROM `sys_block_list` WHERE `ID`='" . $sBlockerId . "'");
	while($aUser = mysql_fetch_assoc($rResult))
		$aUsers[] = $aUser['Profile'];
	return $aUsers;
}
?>