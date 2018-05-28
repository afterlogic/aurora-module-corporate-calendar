<?php
/**
 * This code is licensed under AfterLogic Software License.
 * For full statements of the license see LICENSE file.
 */

namespace Aurora\Modules\CorporateCalendar;

/**
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	public $oApiCalendarManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = new \Aurora\Modules\Calendar\Manager($this);
	}	
	
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		return array(
			'AllowShare' => $this->getConfig('AllowShare', false)
		);
	}	
	
	/**
	 * 
	 * @param int $UserId
	 * @param string $Id
	 * @param boolean $IsPublic
	 * @param array $Shares
	 * @param boolean $ShareToAll
	 * @param int $ShareToAllAccess
	 * @return array|boolean
	 */
	public function UpdateCalendarShare($UserId, $Id, $IsPublic, $Shares, $ShareToAll = false, $ShareToAllAccess = \Aurora\Modules\Calendar\Enums\Permission::Read)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		$sUserPublicId = \Aurora\System\Api::getUserPublicIdById($UserId);
		$aShares = json_decode($Shares, true) ;
		
		// Share calendar to all users
		if ($ShareToAll)
		{
			$aShares[] = array(
				'email' => $this->oApiCalendarManager->getTenantUser(),
				'access' => $ShareToAllAccess
			);
		}
		else
		{
			$aShares[] = array(
				'email' => $this->oApiCalendarManager->getTenantUser(),
				'access' => \Aurora\Modules\Calendar\Enums\Permission::RemovePermission
			);
		}

		// Public calendar
		if ($IsPublic)
		{
			$aShares[] = array(
				'email' => $this->oApiCalendarManager->getPublicUser(),
				'access' => \Aurora\Modules\Calendar\Enums\Permission::Read
			);
		}
		return $this->oApiCalendarManager->updateCalendarShares($sUserPublicId, $Id, $aShares);
	}		
	
	
}