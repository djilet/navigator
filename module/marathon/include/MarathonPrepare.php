<?php

trait MarathonPrepare{
	abstract public function getDataTable();

	//Init prepare
	public function prepareContentByType($type){
		switch ($type) {
			case 'Checkbox':
				$content = $this->prepareToList();
				break;

			case 'DropList':
				$content = $this->prepareToList();
				break;

			case 'SearchList':
				$content = $this->prepareToList();
				break;

			case 'MultipleSearch':
				$content = $this->prepareToMultipleSearch();
				break;

			//special types for classes
			case 'UniversityList':
				$content = $this->prepareToUniversityList();
				if ($this->IsPropertySet('MaxSelectCount')){
					$loop_count = array();
					for ($i=0; $i < $this->GetProperty('MaxSelectCount'); $i++) {
						$loop_count[$i]['UniversityID'] = (isset($this->user_answers[$i]) ? $this->user_answers[$i] : '');
					}
					$this->SetProperty('MaxCountLoop', $loop_count);
				}
				break;
		}
		if ( !empty($content) ){
			return $content;
		}
	}

	//Service
	public function prepareToMultipleSearch(){
		$result = array();
		$i = 0;
		foreach ($this->data as $key => $value){
			$result[$i]['ListTitle'] = (isset($value['Title']) ? $value['Title'] : '');
			$result[$i]['ListData'] = (isset($value['Data']) ? $value['Data'] : '');
			$result[$i]['List'] = $this->prepareToList($value['Values'], (isset($value['Data']) ? $value['Data'] : ''));
			$i++;
		}
		return $result;
	}
	public function prepareToList($data = null, $data_table = null){
		$result = array();
		if (empty($data)){
			$data = $this->data;
		}

		if (is_array($data)){
			foreach ($data as $key => $value){
				//id
				if ( isset($value[$this->getDataTable() . 'ID']) ){
					$result[$key]['ID'] = $value[$this->getDataTable() . 'ID'];
				}
				elseif ( isset($value[$data_table . 'ID']) ){
					$result[$key]['ID'] = $value[$data_table . 'ID'];
				}
				else{
					$result[$key]['ID'] = $value['ID'];
				}

				//title
				if ( isset($value[$this->getDataTable() . 'Title']) ){
					$result[$key]['Title'] = $value[$this->getDataTable() . 'Title'];
				}
				elseif ( isset($value['Title']) ){
					$result[$key]['Title'] = $value['Title'];
				}

				//selected
				$result[$key]['Selected'] = (isset($value['Selected']) ? $value['Selected'] : '');
			}
		}

		return $result;
	}
}