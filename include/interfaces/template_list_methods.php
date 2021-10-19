<?php

trait TemplateListMethods
{
	public function addItemInTemplateList($id, $name, array &$list, array $fields = array()){
		$item = array_merge(['Id' => $id, 'Name' => $name,], $fields);
		$list[] = $item;
	}


//Prepare implementations
	public function prepareDataForTemplateList($dataTable, $selected = array(), $position = array()){
		$list = array();
		foreach ($this->_items as $key => $item) {
			//other fields
			if (!empty($selected)){
				$fields['Selected'] = (in_array($item[$dataTable . 'ID'], $selected) ? 1 : 0);
			}
			if (!empty($position)){
				if (isset($position[$item[$dataTable . 'ID']])){
					$fields['Position'] = $position[$item[$dataTable . 'ID']];
				}
			}
			$fields['Description'] = $item['Description'];

			$this->addItemInTemplateList($item[$dataTable . 'ID'], $item[$dataTable . 'Title'], $list, $fields);
		};

		return $list;
	}

	public function prepareFromKeysName($idKey, $nameKey, $selected, $items){
        $list = array();
        $fields = array();

        if (empty($items)){
            $items = $this->_items;
        }

        foreach ($items as $index => $item) {
            if (!empty($selected)){
                $fields['Selected'] = (in_array($item[$idKey], $selected) ? 1 : 0);
            }

            $this->addItemInTemplateList($item[$idKey], $item[$nameKey], $list, $fields);
        }

        return $list;
    }
}