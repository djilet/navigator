<?php

namespace Module\Tracker\AnalyticSystem;

require_once(dirname(__FILE__) . "/base_system.php");

class DataBase extends BaseSystem
{
    const SYSTEM_NAME = 'db';

    public function sendEvent($name, $properties = null)
    {
        try{
            $query = \QueryBuilder::init()
                ->insert('user_event')
                ->setValue('Type', \Connection::GetSQLString($name))
                ->setValue('Created', \Connection::GetSQLDateTime('now'))
                ->setValue('Properties', \Connection::GetSQLString(json_encode($properties)));
            $stmt = GetStatement();
            if (!$stmt->Execute($query->getSQL())){
                throw new BaseSystemException('SQL error: ' . $stmt->_dbLink->error);
            }
        }
        catch (BaseSystemException $e){
            throw $e;
        }
    }
}