<?php

namespace Module\Olympiad;

class Main extends \LocalObject
{
    public function __construct()
    {
        parent::LocalObject();
    }

    public function load($id){
        $query = "SELECT * FROM olympiad_main WHERE MainID = " . intval($id);
        $this->LoadFromSQL($query);
    }

    public static function getIDByStaticPath($path){
        $query = "SELECT MainID FROM olympiad_main WHERE StaticPath = " . \Connection::GetSQLString($path);
        return GetStatement()->FetchField($query);
    }
}