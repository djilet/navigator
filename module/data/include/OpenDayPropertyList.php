<?php

/**
 * Class OpenDayPropertyList
 */
class OpenDayPropertyList extends LocalObjectList
{
    /**
     * @param int $id
     */
    public function loadByOpenDay(int $id){
        $query = "SELECT * FROM data_open_day_property WHERE OpenDayID = " . intval($id);
        $this->LoadFromSQL($query);
    }

    /**
     * @param $id
     * @return bool
     * @todo normal save (other properties)
     */
    public function saveForOpenDay($id){
        $stmt = GetStatement();

        $this->deleteForOpenDay($id);
        if (empty($this->_items)){
            return true;
        }
        foreach ($this->GetItems() as $index => $item) {
            $query = "INSERT INTO data_open_day_property
                  SET OpenDayID = " . intval($id) . ",
                  Property = " . Connection::GetSQLString($item['Property']) . ",
                  VALUE = " . Connection::GetSQLString($item['Value']);
            $stmt->Execute($query);
        }
    }

    /**
     * @param $id
     */
    public function deleteForOpenDay($id){
        $query = "DELETE FROM data_open_day_property WHERE OpenDayID = " . intval($id);
        GetStatement()->Execute($query);
    }
}