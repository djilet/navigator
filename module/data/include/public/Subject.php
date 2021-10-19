<?php
/**
 * Date:    09.11.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class Subject extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;


    public function load($orderBy = 'Title'){
        $query = 'SELECT * FROM `data_subject` ORDER BY `' . $orderBy . "`";
        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
    }

    public function getItems($selected = array(), $allKeys = array(), $values = array())
    {
        $result = array();
        foreach ($this->_items as $item) {
            $item['Selected'] = in_array($item['SubjectID'], $selected) ? 1 : 0;
            if (! $item['Selected']) {
                $item['Disabled'] = in_array($item['SubjectID'], $allKeys) ? 1 : 0;
            }
            if(isset($values[$item['SubjectID']]) && intval($values[$item['SubjectID']]) > 0){
                $item['Value'] = $values[$item['SubjectID']];
            }
            $result[] = $item;
        }

        return $result;
    }

    public function getListForTemplate()
    {
        $list = array();

        foreach ($this->_items as $index => $item) {
            $this->addItemInTemplateList($item['SubjectID'], $item['Title'], $list);
        }

        return $list;
    }

    public static function GetItemsOnSpeciality($speciality_id){
		$query = "SELECT SubjectID FROM `data_ege` WHERE SpecialityID IN (" . implode(',', Connection::GetSQLArray($speciality_id)) . ")";
		$stmt = GetStatement();
		if( $result = $stmt->FetchRows($query) ){
			return $result;
		}
	}

    public function getIndexedItems($indexField)
    {
        $result = array();
        foreach ($this->_items as $item) {
            $result[$item[$indexField]] = $item;
        }

        return $result;
    }
}