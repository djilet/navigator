<?php

class BaseTestUserList extends LocalObjectList
{
	public function __construct(){
		parent::LocalObjectList();
	}

	public function load(LocalObject $request = null, $onPage = 10){
		$onPage = intval($onPage);
		$where = array();
		$join = array('LEFT JOIN users_item AS userItem ON testUser.UserID = userItem.UserID');
		$orderBy = 'testUser.Created ASC';

		if ($request !== null){
            if ($request->IsPropertySet('PageID')){
                $where[] = "testUser.PageID = " . $request->GetIntProperty('PageID');
            }

			if ($request->GetProperty('Completed') == 'Y'){
				$where[] = "testUser.CompleteDate IS NOT NULL";
			}

			if ($request->GetProperty('Feedback') == 'Y'){
				$where[] = "testUser.FeedbackRating IS NOT NULL";
			}

			if (!empty($request->GetProperty('FeedbackRatingOrder'))){
                $orderBy = 'testUser.FeedbackRating ASC';
			    if ($request->GetProperty('FeedbackRatingOrder') == 'DESC'){
                    $orderBy = 'testUser.FeedbackRating DESC';
                }
			}

			if ($dateFrom = $request->GetProperty('DateFrom')){
			    $where[] = "testUser.CompleteDate >= " . Connection::GetSQLDate($dateFrom);
            }

			if ($dateTo = $request->GetProperty('DateTo')){
                $where[] = "testUser.CompleteDate <= " . Connection::GetSQLDateTime(date('d.m.Y', strtotime($dateTo) + 24 * 3600));
            }
		}

		$query = "SELECT * FROM basetest_user AS testUser
				" .(!empty($join) ? implode(' ', $join) : '') . "
        		" . ((count($where) > 0)?"WHERE ".implode("\n AND ", $where):"") .
				(isset($group) ? " GROUP BY " . $group : '') . "
        		ORDER BY " . $orderBy;

		//echo $query;

		if ($onPage > 0){
			$this->SetItemsOnPage($onPage);
			$this->SetCurrentPage();
		}

		$this->LoadFromSQL($query);
	}

    public function exportToCSV(){
        ob_start();
        $f = fopen("php://output", "w");

        $row = array("ID","ФИО","E-mail","Оценка","Сообщение","Дата прохождения","Статус теста", "Ссылка на результат");
        fputcsv($f, $row, ";");

        foreach ($this->_items as $index => $item) {

            //Short link
            if (!empty($item["CompleteDate"]) && empty($item['ShortLink'])){
                $testUser = new BaseTestUser();
                $testUser->load($item['BaseTestUserID']);
                if ($link = $testUser->getShortLink()){
                    $item["ShortLink"] = $link;
                    $this->_items[$index] = $item;
                }
            }

            $row = array(
                $item["UserID"],
                $item["UserName"],
                $item["UserEmail"],
                $item["FeedbackRating"],
                $item["FeedbackMessage"],
                $item["CompleteDate"],
                ($item["Status"] == 'active' ? 'Активный' : 'Сброшен'),
                $item["ShortLink"],
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
        header('Content-Disposition: attachment;filename="basetest_users.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }
}