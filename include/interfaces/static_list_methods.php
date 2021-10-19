<?php

/**
 * Trait StaticListMethods
 * @deprecated unnecessarily
 */
trait StaticListMethods
{
    protected static $staticList;
    protected static $assocStaticList = [];

    public static function getStaticList(){
        if (empty(self::$staticList)){
            self::createStaticList();
        }

        return self::$staticList;
    }

    public static function getAssocStaticList($key){
        if (!isset(self::$assocStaticList[$key])){
            foreach (self::getStaticList() as $index => $item) {
                self::$assocStaticList[$key][$item[$key]] = $item;
            }
        }
        return self::$assocStaticList[$key];
    }

//Prepare implementations
    protected static function createFromLocalObjectList(){
        $list = new self();
        $list->load();
        self::$staticList = $list->getItems();
        unset($list);
    }
}