<?php
/**
 * Date:    09.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

class Area extends LocalObjectList
{
    public function load($withRegion = true)
    {
        $this->_items = array();
        $stmt = GetStatement();
        
        $query = 'SELECT *, 0 AS `Childrens` FROM `data_area` ORDER BY `Title`';
        $areas = $stmt->FetchIndexedList($query, 'AreaID');
        
        if (! empty($areas)) {
            $regionList = $stmt->FetchList('SELECT * FROM `data_region` ORDER BY  `Title`');
            if (! empty($regionList)) {
                foreach ($regionList as $region) {
                    if (isset($areas[$region['AreaID']])) {
                        if (! is_array($areas[$region['AreaID']]['Childrens'])) {
                            $areas[$region['AreaID']]['Childrens'] = array();
                        }
                        $areas[$region['AreaID']]['Childrens'][] = $region;
                    }
                }
            }
        }
        
        $this->_items = array_values($areas);
    }

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
            $item['Selected'] = 0;
            if (isset($item['Childrens'])) {
                foreach ($item['Childrens'] as $key => $children) {
                    $item['Childrens'][$key]['Selected'] = in_array($children['RegionID'], $selected) ? 1 : 0;
                    if ($item['Childrens'][$key]['Selected'] == 1) {
                        $item['Selected'] = 1;
                    }
                }
            }
            
            $result[] = $item;
        }

        return $result;
    }
}