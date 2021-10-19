<?php

class Share extends LocalObject
{
    const SHARE_ITEMS = [
        'facebook',
        'telegram',
        'vk',
        'whatsapp',
    ];

    public static function changeCount($itemID, $itemType, $shareItem, $value){
        $count = self::getCount($itemID, $itemType, $shareItem);
        $value = intval($value);
        if ($count > 0){
            $count += $value;
            $query = "UPDATE share_count SET Count = {$count} WHERE ItemID = {$itemID} AND ItemType = '{$itemType}' AND ShareItem = '{$shareItem}'";
        }
        else{
            $query = "INSERT INTO share_count SET Count = {$value}, ItemID = {$itemID}, ItemType = '{$itemType}', ShareItem = '{$shareItem}'";
        }

        return GetStatement()->Execute($query);
    }

    public static function getCount($itemID, $itemType, $shareItem){
        $itemID = intval($itemID);
        $query = "SELECT Count FROM share_count WHERE ItemID = {$itemID} AND ItemType = '{$itemType}' AND ShareItem = '{$shareItem}'";
        return GetStatement()->FetchField($query);
    }

    public static function getItemsCountByItem($itemID, $itemType){
        $result = [];
        $query = "SELECT ShareItem, Count FROM share_count WHERE ItemID = {$itemID} AND ItemType = '{$itemType}'";
        foreach (GetStatement()->FetchList($query) as $index => $item) {
            $result[$item['ShareItem']] = $item['Count'];
        };

        return $result;
    }
}