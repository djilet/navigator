<?php

class UserItemList extends LocalObjectList
{
    private $module;
    private $config;
    private $baseURL;

    public function __construct($module, $config = array(), $baseURL = '', $data = array())
    {
        parent::__construct($data);

        $this->module = $module;
        $this->config = is_array($config) ? $config : array();
        $this->baseURL = $baseURL;
    }

	/**
	 * Загрузка всех пользователей
	 * @param int $onPage
	 * @param LocalObject|null $request
	 */
    public function load($onPage = 40, LocalObject $request = null)
    {
		$where = array();
		$join = array();//'LEFT JOIN users_item AS ui ON mu.UserID = ui.UserID'

		if ($request !== null && $request->IsPropertySet('Filter')){
			$filter = $request->GetProperty('Filter');

			if (!empty($filter['email'])){
				$where[] = "UserEmail LIKE " . Connection::GetSQLString("%" . $filter['email'] . "%");
			}
		}

        $query = "SELECT `UserID`,`UserEmail`,`UserName`,`UserPhone`,`UserWho`,`ClassNumber`,`City`,`Created` 
            	  FROM `users_item`
					" .(!empty($join) ? implode(' ', $join) : ''). " 
			  		".((count($where) > 0)?"WHERE ".implode(" AND ", $where):"") . " ORDER BY Created DESC";
        
        $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        
        $this->LoadFromSQL($query);
    }
    
    function LoadForSelection($userID)
    {
    	$query = "SELECT u.UserID, u.UserEmail, u.UserName, (CASE u.UserID WHEN ".intval($userID)." THEN 1 ELSE 0 END) as Selected
    			FROM `users_item` AS u
    			WHERE u.QuestionModerator = 'Y'
    			ORDER BY u.UserName";
    	$this->LoadFromSQL($query);
    }

    /**
     * Удаление пользователей. Вместе с ним удаляются его заказы и истории заказов.
     *
     * @param $id
     *
     * @return bool
     */
    public function removeByItemIDs($id)
    {
        if (empty($id) or !is_array($id)) {
            return false;
        }
        $list = implode(',', $id);

        $stmt = GetStatement();

        // удаляем пользователей
        $query = 'DELETE FROM users_item WHERE UserID IN(' . $list . ')';
        if ($stmt->Execute($query)) {
            $stmt->Execute('UPDATE social_auth SET UserItemID=NULL WHERE UserItemID IN (' . $list . ')');
            $stmt->Execute('DELETE FROM social_token WHERE UserItemID IN (' . $list . ')');
            
            return true;
        }
        
        return false;
    }
    
    public function exportToCSV()
    {
        $this->load(0);
        
        ob_start();
        $f = fopen("php://output", "w");
        
        $row = array("Дата регистрации","ФИО","Телефон","E-mail","Статус","Класс","Город");
        fputcsv($f, $row, ";");
        
        foreach($this->getItems() as $item)
        {
            $row = array(
                $item["Created"],
                $item["UserName"],
                $item["UserPhone"],
                $item["UserEmail"],
                $item["UserWho"],
                $item["ClassNumber"],
                $item["City"],
            );
            fputcsv($f, $row, ";");
        }
        
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="users.csv"');
        header("Content-Transfer-Encoding: binary");
        
        echo(ob_get_clean());
        exit();
    }
}