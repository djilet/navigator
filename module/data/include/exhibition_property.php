<?php

class ExhibitionPropertyList extends LocalObjectList
{
    public function loadByExhibition($id){
        $query = "SELECT * FROM data_exhibition_property WHERE ExhibitionID = " . intval($id);
        $this->LoadFromSQL($query);
    }

    //TODO normal save (other properties)
    public function saveForExhibition($id){
        $stmt = GetStatement();

        $this->deleteForExhibition($id);
        if (empty($this->_items)){
            return true;
        }
        foreach ($this->GetItems() as $index => $item) {
            $query = "INSERT INTO data_exhibition_property
                  SET ExhibitionID = " . intval($id) . ",
                  Property = " . Connection::GetSQLString($item['Property']) . ",
                  VALUE = " . Connection::GetSQLString($item['Value']);
            $stmt->Execute($query);
        }
    }

    public function deleteForExhibition($id){
        $query = "DELETE FROM data_exhibition_property WHERE ExhibitionID = " . intval($id);
        GetStatement()->Execute($query);
    }
}