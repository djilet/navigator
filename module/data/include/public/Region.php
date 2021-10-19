<?php
/**
 * Date:    08.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
es_include('interfaces/static_list_interface.php');
es_include('interfaces/static_list_methods.php');

class Region extends LocalObjectList implements TemplateListInterface, StaticListInterface
{
    use StaticListMethods;
    use TemplateListMethods;

    const ID_KEY = 'RegionID';
    const NAME_KEY = 'Title';


    public function load()
    {
        $query = 'SELECT * FROM `data_region` ORDER BY `Title` ASC';
        $this->LoadFromSQL($query);
    }

    public function loadForCollegeList(){
		$query = 'SELECT DISTINCT reg.* FROM `data_region` AS reg
				  INNER JOIN college_college AS col ON reg.RegionID = col.RegionID';
		$this->LoadFromSQL($query);
	}

    public function getItems($selected = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
        	if (!empty($selected)){
				$item['Selected'] = in_array($item['RegionID'], $selected) ? 1 : 0;
			}
            $result[] = $item;
        }

        return $result;
    }

//Interfaces
    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName(self::ID_KEY, self::NAME_KEY, $selected, $items);
    }

    public static function createStaticList()
    {
        self::createFromLocalObjectList();
    }
}