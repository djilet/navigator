<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");
require_once(dirname(__FILE__) . "/Import/Tools/Direction.php");

$list = GetStatement()->FetchList("SELECT * FROM data_direction WHERE StaticPath IS NULL");
foreach ($list as $item){
    $staticPath = RuToStaticPath($item['Title']);
    $query = "UPDATE data_direction SET StaticPath = '{$staticPath}' WHERE DirectionID = {$item['DirectionID']}";
    if (GetStatement()->Execute($query)){
        echo "nice {$item['DirectionID']} \n";
    }
    else{
        echo "error {$item['DirectionID']} \n {$query} \n";
    }
}

$direction = new \Import\Tools\Direction(GetStatement());
$direction->uniqStaticPath();

echo 'nice';