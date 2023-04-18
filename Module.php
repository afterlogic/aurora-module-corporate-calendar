<?php
/**
 * This code is licensed under Afterlogic Software License.
 * For full statements of the license see LICENSE file.
 */

namespace Aurora\Modules\CorporateCalendar;

/**
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractLicensedModule
{
    public $oManager = null;

    public function getManager()
    {
        if ($this->oManager === null) {
            $this->oManager = new \Aurora\Modules\Calendar\Manager($this);
        }

        return $this->oManager;
    }

    public function init()
    {
        $this->subscribeEvent('Calendar::GetCalendars::after', array($this, 'onAfterGetCalendars'));
    }

    /**
     *
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     *
     * @return Settings
     */
    protected function GetModuleSettings()
    {
        return $this->oModuleSettings;
    }

    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return array(
            'AllowShare' => $this->getConfig('AllowShare', true)
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
        if ($ShareToAll) {
            $aShares[] = array(
                'email' => $this->getManager()->getTenantUser(),
                'access' => $ShareToAllAccess
            );
        } else {
            $aShares[] = array(
                'email' => $this->getManager()->getTenantUser(),
                'access' => \Aurora\Modules\Calendar\Enums\Permission::RemovePermission
            );
        }

        // Public calendar
        if ($IsPublic) {
            $aShares[] = array(
                'email' => $this->getManager()->getPublicUser(),
                'access' => \Aurora\Modules\Calendar\Enums\Permission::Read
            );
        }
        return $this->getManager()->updateCalendarShares($sUserPublicId, $Id, $aShares);
    }

    public function onAfterGetCalendars($aData, &$oResult)
    {
        if (isset($aData['UserId']) && isset($oResult['Calendars'])) {
            $oUser = \Aurora\System\Api::getUserById($aData['UserId']);
            if ($oUser) {
                $mCalendars = $this->getManager()->getSharedCalendars($oUser->PublicId);
                if (is_array($mCalendars)) {
                    foreach ($mCalendars as $CalendarKey => $oCalendar) {
                        foreach ($oCalendar->Shares as $ShareKey => $aShare) {
                            if ($aShare['email'] === $this->getManager()->getTenantUser()) {
                                if (!$oCalendar->SharedToAll) {
                                    $mCalendars[$CalendarKey]->Shared = true;
                                    $mCalendars[$CalendarKey]->SharedToAll = true;
                                } elseif ($oUser->PublicId === $oCalendar->Owner) {
                                    unset($mCalendars[$CalendarKey]);
                                }
                                unset($oCalendar->Shares[$ShareKey]);
                            }
                        }
                    }
                    $oResult['Calendars'] = array_merge($oResult['Calendars'], $mCalendars);
                }
            }
        }
    }
}
