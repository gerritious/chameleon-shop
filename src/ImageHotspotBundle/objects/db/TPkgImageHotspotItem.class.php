<?php

/*
 * This file is part of the Chameleon System (https://www.chameleonsystem.com).
 *
 * (c) ESONO AG (https://www.esono.de)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ChameleonSystem\CoreBundle\Service\ActivePageServiceInterface;
use Symfony\Component\HttpFoundation\Request;

class TPkgImageHotspotItem extends TAdbPkgImageHotspotItem
{
    const VIEW_PATH = 'pkgImageHotspot/views/db/TPkgImageHotspotItem';
    /**
     * the URL parameter to fetch an item.
     */
    const URL_NAME_ITEM_ID = 'pkgImageHotspotItemId';

    /**
     * render the hotspot image.
     *
     * @param string $sViewName     - name of the view
     * @param string $sViewType     - where to look for the view
     * @param array  $aCallTimeVars - optional parameters to pass to render method
     *
     * @return string
     */
    public function Render($sViewName = 'standard', $sViewType = 'Customer', $aCallTimeVars = array())
    {
        $oView = new TViewParser();

        $oView->AddVar('oItem', $this);
        $oView->AddVar('aCallTimeVars', $aCallTimeVars);
        $aOtherParameters = $this->GetAdditionalViewVariables($sViewName, $sViewType);
        $oView->AddVarArray($aOtherParameters);

        return $oView->RenderObjectPackageView($sViewName, TdbPkgImageHotspotItem::VIEW_PATH, $sViewType);
    }

    /**
     * use this method to add any variables to the render method that you may
     * require for some view.
     *
     * @param string $sViewName - the view being requested
     * @param string $sViewType - the location of the view (Core, Custom-Core, Customer)
     *
     * @return array
     */
    protected function GetAdditionalViewVariables($sViewName, $sViewType)
    {
        return array();
    }

    /**
     * Add view based clear cache triggers for the Render method here.
     *
     * @param array  $aClearTriggers - clear trigger array (with current contents)
     * @param string $sViewName      - view being requested
     * @param string $sViewType      - location of the view (Core, Custom-Core, Customer)
     */
    protected function AddClearCacheTriggers(&$aClearTriggers, $sViewName, $sViewType)
    {
        $aClearTriggers[] = array('table' => $this->table, 'id' => $this->id);
        $aClearTriggers[] = array('table' => 'shop_article', 'id' => ''); // for now we keep it simple and react to any article changes. this should later be changed to react only to relevant items
        $aClearTriggers[] = array('table' => 'cms_tree', 'id' => ''); // for the connected markers that may hold tree links
        $aClearTriggers[] = array('table' => 'cms_media', 'id' => ''); // for the connected markers that may hold tree links
        $aClearTriggers[] = array('table' => 'pkg_image_hotspot_item_spot', 'id' => ''); // for the connected markers that may hold tree links
    }

    /**
     * returns the item next in line relative to this item
     * if the current item is the last in line, the method will return the first item. returns false if
     * no next item exists.
     *
     * @return TdbPkgImageHotspotItem
     */
    public function GetNextItem()
    {
        $oNextItem = &$this->GetFromInternalCache('oNextItem');
        if (is_null($oNextItem)) {
            $oItemList = TdbPkgImageHotspotItemList::GetListForPkgImageHotspotId($this->fieldPkgImageHotspotId);
            $oItemList->bAllowItemCache = true;
            if ($oItemList->Length() > 1) {
                $oNextItem = null;
                $oFirst = $oItemList->Current();
                while (is_null($oNextItem) && ($oTmpItem = $oItemList->Next())) {
                    if ($oTmpItem->IsSameAs($this)) {
                        $oNextItem = $oItemList->Next();
                    }
                }
                if (false === $oNextItem) {
                    $oNextItem = $oFirst;
                    if ($oNextItem->IsSameAs($this)) {
                        $oNextItem = false;
                    }
                }
            }
            $this->SetInternalCache('oNextItem', $oNextItem);
        }

        return $oNextItem;
    }

    /**
     * returns the item before this item
     * if the current item is the first in line, the method will return the last item. returns false if
     * no previous item exists.
     *
     * @return TdbPkgImageHotspotItem
     */
    public function GetPreviousItem()
    {
        $oPreviousItem = &$this->GetFromInternalCache('oPreviousItem');
        if (is_null($oPreviousItem)) {
            $oItemList = TdbPkgImageHotspotItemList::GetListForPkgImageHotspotId($this->fieldPkgImageHotspotId);
            $oItemList->bAllowItemCache = true;
            if ($oItemList->Length() > 1) {
                $oPreviousItem = null;
                $oItemList->GoToEnd();
                while (is_null($oPreviousItem) && ($oTmpItem = $oItemList->Previous())) {
                    if ($oTmpItem->IsSameAs($this)) {
                        $oPreviousItem = $oItemList->Previous();
                    }
                }
                if (!$oPreviousItem) {
                    $oItemList->GoToEnd();
                    $oPreviousItem = $oItemList->Previous();
                    if (!$oPreviousItem || $oPreviousItem->IsSameAs($this)) {
                        $oPreviousItem = false;
                    }
                }
            }
            $this->SetInternalCache('oPreviousItem', $oPreviousItem);
        }

        return $oPreviousItem;
    }

    /**
     * returns the url parameter base name for the currently active module spot.
     *
     * @return string
     */
    public static function GetURLParameterBaseForActiveSpot()
    {
        $oGlobal = TGlobal::instance();
        $oRunningModule = &$oGlobal->GetExecutingModulePointer();
        $sModuleSpotName = $oRunningModule->sModuleSpotName;

        return TdbPkgImageHotspotItem::URL_NAME_ITEM_ID.$sModuleSpotName;
    }

    /**
     * return the link to this item in the current module spot.
     *
     * @return string
     */
    public function GetLink()
    {
        return $this->getActivePageService()->getLinkToActivePageRelative(array(
            TdbPkgImageHotspotItem::GetURLParameterBaseForActiveSpot() => array('id' => $this->id),
        ));
    }

    /**
     * return the url request needed to fetch the spot using ajax.
     */
    public function GetAjaxLink($sViewName = 'standard', $sType = 'Core', $aParameter = array())
    {
        $oGlobal = TGlobal::instance();
        $oRunningModule = &$oGlobal->GetExecutingModulePointer();
        $aParameter['id'] = $this->id;
        $aParameter['sViewName'] = $sViewName;
        $aParameter['sType'] = $sType;
        $aData = array(
            TdbPkgImageHotspotItem::GetURLParameterBaseForActiveSpot() => $aParameter,
            'module_fnc' => array($oRunningModule->sModuleSpotName => 'ExecuteAjaxCall'),
            '_fnc' => 'AjaxRenderHotspotImage',
        );

        return $this->getActivePageService()->getLinkToActivePageRelative($aData);
    }

    /**
     * @return ActivePageServiceInterface
     */
    private function getActivePageService()
    {
        return \ChameleonSystem\CoreBundle\ServiceLocator::get('chameleon_system_core.active_page_service');
    }
}
