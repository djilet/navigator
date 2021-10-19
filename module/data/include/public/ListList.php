<?php

class ListList extends LocalObjectList
{
	public function getInfo($listID)
	{
		$stmt = GetStatement();
		return $stmt->FetchRow("SELECT Type, Title, Description, MetaTitle, MetaDescription FROM `data_list` WHERE ListID=".intval($listID));
	}
	
	public function getFilterArray($listID)
	{
		$result = array();
		$stmt = GetStatement();
		$filterList = $stmt->FetchList("SELECT FilterName, FilterValue FROM `data_list_filter` WHERE ListID=".intval($listID));
		for($i=0; $i<count($filterList); $i++) {
			$result[$filterList[$i]["FilterName"]] = json_decode($filterList[$i]["FilterValue"]);
		}
		return $result;
	}
	
    public function loadForUniversityList($baseURL, $selectedListID)
    {
        $query = QueryBuilder::init()->select([
            "list.*",
        	"CASE WHEN list.StaticPath IS NOT NULL AND list.StaticPath<>'' THEN CONCAT(".Connection::GetSQLString($baseURL).", '/', list.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') ELSE CONCAT(".Connection::GetSQLString($baseURL.HTML_EXTENSION).", '?ListID=', list.ListID) END AS ListURL",
        	"IF(list.ListID = ".intval($selectedListID).", 1, 0) as Selected"
        ])
            ->from('data_list AS list')
            ->where(["list.Public='Y'"])
            ->order(['list.Title ASC']);

        $this->LoadFromSQL($query->getSQL());
    }
    
    public function getIDByStaticPath($staticPath)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT l.ListID FROM `data_list` l WHERE l.StaticPath=".Connection::GetSQLString($staticPath));
    }
    
    public static function getForSiteMap($baseUrl){
        $stmt = GetStatement();
        $query = "SELECT DISTINCT CONCAT(" . Connection::GetSQLString($baseUrl) . ", '/', l.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).",'/') AS URL
                  FROM `data_list` AS l
                  WHERE Public = 'Y'";
        if ($result = $stmt->FetchRows($query)){
            return $result;
        }
    }
}