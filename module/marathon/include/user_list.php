<?php

class MarathonUserList extends LocalObjectList {
	public function __construct(){
		parent::LocalObjectList();
		$this->SetItemsOnPage(10);
	}

	public function load(LocalObject $request){
		$join = array('LEFT JOIN users_item AS ui ON mu.UserID = ui.UserID');
		$join[] = "LEFT JOIN (
						SELECT MarathonUserID, COUNT(PartID) AS CompletedParts, MAX(PartID) AS LastPartID FROM marathon_stage_part2user
						WHERE Status = 'complete'
						GROUP BY MarathonUserID
                  )AS part2user ON part2user.MarathonUserID = mu.MarathonUserID
                  LEFT JOIN (
                      SELECT MarathonUserID, PartID FROM marathon_stage_part2user WHERE Status = 'available'
                  ) AS avail_part ON avail_part.MarathonUserID = mu.MarathonUserID
                  LEFT JOIN marathon_stage_part AS part ON avail_part.PartID = part.PartID
                  LEFT JOIN marathon_stage AS stage ON part.StageID = stage.StageID
                  LEFT JOIN marathon_stage_part AS lastpart ON part2user.LastPartID = lastpart.PartID";
		$join[] = "LEFT JOIN (
						SELECT MarathonUserID, COUNT(Item) AS CompleteItemsCount FROM marathon_user_info GROUP BY MarathonUserID
					) AS onboard ON mu.MarathonUserID = onboard.MarathonUserID";
		$join[] = "LEFT JOIN (
						SELECT MarathonUserID, COUNT(StepID) AS CompletedCount FROM `marathon_map2user` WHERE Status = 'completed' GROUP BY MarathonUserID
					) AS map ON mu.MarathonUserID = map.MarathonUserID";
		$where = array();
		$count_stages = MarathonStage::getCountStages();

		if ($request->IsPropertySet('Filter')){
			$filter = $request->GetProperty('Filter');

			if (!empty($filter['Onboarding'])){
				$where[] = "info.Item = 'Subject'";
				$join[] = "LEFT JOIN marathon_user_info AS info ON mu.MarathonUserID = info.MarathonUserID";
			}
			if (!empty($filter['Stage'])){
				if (is_array($filter['Stage'])){
					$sortOrder = max($filter['Stage']);
				}
				else{
					$sortOrder = $filter['Stage'];
				}

				$where[] = "part2user.CompletedParts >= " . intval(MarathonStage::getCountPartsToComplete($sortOrder));
			}
			if (!empty($filter['Map'])){
				$join[] = "LEFT JOIN marathon_map2user AS map2user ON mu.MarathonUserID = map2user.MarathonUserID";
				$where[] = "map2user.Status = 'completed' AND map2user.StepID = " . intval($filter['Map']);
			}
		}
		
		//DateFrom and DateTo
		$where = array_merge($where, self::getDateWhere($request));
		
		$query = "SELECT DISTINCT mu.MarathonUserID, mu.UserID, ui.UserEmail, ui.UserName, ui.UserPhone, ui.UserWho, ui.ClassNumber, ui.City,
								  mu.Created,
								  CONCAT('source=', mu.utm_source,
								  	CONCAT_WS('',', medium=', mu.utm_medium, ', campaign=', mu.utm_campaign, ', term=', mu.utm_term, ', content=', mu.utm_content)
								  ) as UTM,
								  stage.Title AS StageTitle,
								  part2user.CompletedParts,
								  onboard.CompleteItemsCount AS OnboardingCount,
								  map.CompletedCount AS MapCount,
                                  lastpart.Title AS LastPartTitle
				  FROM `marathon_user` mu
				  " .(!empty($join) ? implode(' ', $join) : ''). " 
				  ".((count($where) > 0)?"WHERE ".implode(" AND ", $where):"") . " ORDER BY mu.MarathonUserID DESC";

		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
	}

	public function exportToCSV($request){
	    $this->SetItemsOnPage(0);
	    $this->load($request);
	    
	    ob_start();
	    $f = fopen("php://output", "w");
	    
	    $row = array("ФИО","Телефон","E-mail","Кто я?","Класс","Город","Дата регистрации","UTM","Онбординг: сферы деятельности","Онбординг: предметы","Онбординг: регионы","Онбординг: вузы","Этап обучения","Последнее выполненное задание");
	    
	    $mapList = new MarathonMapList();
	    $mapList->load();
	    foreach($mapList->GetItems() as $map){
	        $row[] = "Карта: ".$map["Name"];
	    }
	    
	    $taskList = new MarathonTask(null);
	    $answerList = array(
	        array("TaskID" => 10101, "Title" => "Поставь цель Марафона"),
	        array("TaskID" => 20201, "Title" => "Методика Йовайши"),
	        array("TaskID" => 20202, "Title" => "Методика Л. Йовайши в модификации Г. Резапкиной"),
	        array("TaskID" => 20203, "Title" => "Тест Дж.Голланда (Дж.Холланда)"),
	    );
	    foreach($answerList as $answer){
	        $row[] = $answer["Title"];
	    }
	    
	    fputcsv($f, $row, ";");
	    
	    $marathonUserIDs = array();
	    foreach($this->_items as $item){
	        $marathonUserIDs[] = $item["MarathonUserID"];
	    }
	    $infoIndustries = (new UserInfoItem("Industry", 0))->loadForList($marathonUserIDs);
	    $infoSubjects = (new UserInfoItem("Subject", 0))->loadForList($marathonUserIDs);
	    $infoRegions = (new UserInfoItem("Region", 0))->loadForList($marathonUserIDs);
	    $infoUniversities = (new UserInfoItem("University", 0))->loadForList($marathonUserIDs);
	    $mapValues = array();
	    foreach($mapList->GetItems() as $map){
	        $mapValues[$map["StepID"]] = $mapList->loadUserAnswers($map["StepID"], $map["DataTable"], $marathonUserIDs);
	    }
	    $answerValues = array();
	    foreach($answerList as $answer){
	        $answerValues[$answer["TaskID"]] = $taskList->loadUserAnswers($answer["TaskID"], $marathonUserIDs);
	    }
	    
	    foreach($this->_items as $item){
	        $row = array(
	            $item["UserName"],
	            $item["UserPhone"],
	            $item["UserEmail"],
	            $item["UserWho"],
	            $item["ClassNumber"],
	            $item["City"],
	            $item["Created"],
	            $item["UTM"],
				!empty($infoIndustries[$item["MarathonUserID"]])?$infoIndustries[$item["MarathonUserID"]]["Value"]:"",
				!empty($infoSubjects[$item["MarathonUserID"]])?$infoSubjects[$item["MarathonUserID"]]["Value"]:"",
				!empty($infoRegions[$item["MarathonUserID"]])?$infoRegions[$item["MarathonUserID"]]["Value"]:"",
				!empty($infoUniversities[$item["MarathonUserID"]])?$infoUniversities[$item["MarathonUserID"]]["Value"]:"",
				!empty($item["StageTitle"])?$item["StageTitle"]:($item["CompletedParts"]>1?"Все этапы пройдены":"-"),
	            $item["LastPartTitle"]
	        );
	        foreach($mapList->GetItems() as $map){
	        	if (isset($mapValues[$map["StepID"]][$item["MarathonUserID"]])){
					$val = $mapValues[$map["StepID"]][$item["MarathonUserID"]];
				}
	            $row[] = (!empty($val["Value"]) ? $val["Value"] : "");
	        }
	        foreach($answerList as $answer){
	        	if (isset($answerValues[$answer["TaskID"]][$item["MarathonUserID"]])){
					$val = $answerValues[$answer["TaskID"]][$item["MarathonUserID"]];
				}

				$row[] = (!empty($val["Value"]) ? $val["Value"] : "");
	        }
	        fputcsv($f, $row, ";");
	    }
	    
	    $now = gmdate("D, d M Y H:i:s");
	    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	    header("Last-Modified: {$now} GMT");
	    header("Content-Type: application/force-download");
	    header("Content-Type: application/octet-stream");
	    header("Content-Type: application/download");
	    header('Content-Disposition: attachment;filename="marathon.csv"');
	    header("Content-Transfer-Encoding: binary");
	    
	    echo(ob_get_clean());
	    exit();
	}
	
	public function exportToCSV2($request){
	    $this->SetItemsOnPage(0);
	    $this->load($request);
	    
	    ob_start();
	    $f = fopen("php://output", "w");
	    
	    $row = array("ФИО","Телефон","E-mail","Кто я?","Класс","Город","Дата регистрации","UTM","Что заполнял","Что выбрал");
	    
	    $mapList = new MarathonMapList();
	    $mapList->load();
	    
	    fputcsv($f, $row, ";");
	    
	    $marathonUserIDs = array();
	    foreach($this->_items as $item){
	        $marathonUserIDs[] = $item["MarathonUserID"];
	    }
	    $infoIndustries = (new UserInfoItem("Industry", 0))->loadForList($marathonUserIDs);
	    $infoSubjects = (new UserInfoItem("Subject", 0))->loadForList($marathonUserIDs);
	    $infoRegions = (new UserInfoItem("Region", 0))->loadForList($marathonUserIDs);
	    $infoUniversities = (new UserInfoItem("University", 0))->loadForList($marathonUserIDs);
	    $mapValues = array();
	    foreach($mapList->GetItems() as $map){
	        $mapValues[$map["StepID"]] = $mapList->loadUserAnswers($map["StepID"], $map["DataTable"], $marathonUserIDs);
	    }
	    
	    foreach($this->_items as $item){
	        $fillList = array();
	        if($infoIndustries[$item["MarathonUserID"]]){
	            foreach(explode(', ', $infoIndustries[$item["MarathonUserID"]]["Value"]) as $value){
	                $fillList[] = array("Title" => "Онбординг: сферы деятельности", "Value" => $value);
	            }
	        }
	        if($infoSubjects[$item["MarathonUserID"]]){
	            foreach(explode(', ', $infoSubjects[$item["MarathonUserID"]]["Value"]) as $value){
	                $fillList[] = array("Title" => "Онбординг: предметы", "Value" => $value);
	            }
	        }
	        if($infoRegions[$item["MarathonUserID"]]){
	            foreach(explode(', ', $infoRegions[$item["MarathonUserID"]]["Value"]) as $value){
	                $fillList[] = array("Title" => "Онбординг: регионы", "Value" => $value);
	            }
	        }
	        if($infoUniversities[$item["MarathonUserID"]]){
	            foreach(explode(', ', $infoUniversities[$item["MarathonUserID"]]["Value"]) as $value){
	                $fillList[] = array("Title" => "Онбординг: вузы", "Value" => $value);
	            }
	        }
	        foreach($mapList->GetItems() as $map){
	        	if (isset($mapValues[$map["StepID"]][$item["MarathonUserID"]])){
					$val = $mapValues[$map["StepID"]][$item["MarathonUserID"]];
				}

	            if(!empty($val["Value"])){
	                foreach(explode(', ', $val["Value"]) as $value){
	                    $fillList[] = array("Title" => "Карта: ".$map["Name"], "Value" => $value);
	                }
	            }
	        }
	        foreach($fillList as $fillInfo){
	            $row = array(
	                $item["UserName"],
	                $item["UserPhone"],
	                $item["UserEmail"],
	                $item["UserWho"],
	                $item["ClassNumber"],
	                $item["City"],
	                $item["Created"],
	                $item["UTM"],
	                $fillInfo["Title"],
	                $fillInfo["Value"]
	            );
	            fputcsv($f, $row, ";");
	        }
	    }
	    
	    $now = gmdate("D, d M Y H:i:s");
	    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	    header("Last-Modified: {$now} GMT");
	    header("Content-Type: application/force-download");
	    header("Content-Type: application/octet-stream");
	    header("Content-Type: application/download");
	    header('Content-Disposition: attachment;filename="marathon.csv"');
	    header("Content-Transfer-Encoding: binary");
	    
	    echo(ob_get_clean());
	    exit();
	}

	public static function getCommonStat(LocalObject $request){
		$stmt = GetStatement();
		$result = array();
		$where = self::getDateWhere($request);

		//Count Users
		$query = "SELECT COUNT(mu.UserID) AS CountUsers 
            FROM marathon_user AS mu
            ".((count($where) > 0)?"WHERE ".implode(" AND ", $where):"");
		$result['CountUsers'] = $stmt->FetchField($query);

		//Onboarding Users
		$query = "SELECT COUNT(DISTINCT i.MarathonUserID) AS CountOnboarding 
            FROM marathon_user_info i 
            LEFT JOIN marathon_user mu ON i.MarathonUserID=mu.MarathonUserID
            WHERE i.Item = 'Subject'".((count($where) > 0)?" AND ".implode(" AND ", $where):"");
		$result['CountOnboarding'] = $stmt->FetchField($query);

		return $result;
	}


	public static function getStagesStat($selected = array(), LocalObject $request){
		$stmt = GetStatement();
		$result = array();
		$where = self::getDateWhere($request);

		$query = "SELECT * FROM `marathon_stage` ORDER BY SortOrder";
		foreach ($stmt->FetchList($query) as $index => $item) {
			$item['CountToComplete'] = MarathonStage::getCountPartsToComplete($item['SortOrder']);

			$query = "
			SELECT COUNT(DISTINCT(mu.MarathonUserID)) FROM marathon_user AS mu
			LEFT JOIN (
				SELECT MarathonUserID, COUNT(PartID) AS CompletedParts 
                FROM marathon_stage_part2user
				WHERE Status = 'complete'
				GROUP BY MarathonUserID
				) AS part2user ON part2user.MarathonUserID = mu.MarathonUserID
     		WHERE part2user.CompletedParts >= " . intval($item['CountToComplete']).((count($where) > 0)?" AND ".implode(" AND ", $where):"");

			$item['CountUsersComplete'] = $stmt->FetchField($query);

			if ( !empty($selected) ){
				if (in_array($item['SortOrder'], $selected)){
					$item['Selected'] = 1;
				}
			}

			$result[] = $item;
		}
		return $result;
	}

	public static function getMapStat($selected = array(), LocalObject $request){
		$stmt = GetStatement();
		$where = self::getDateWhere($request);

		$query = "SELECT map.StepID, map.Name, COUNT(map2user.MarathonUserID) AS CountUsers 
                    FROM `marathon_map2user` AS map2user
					LEFT JOIN marathon_map AS map ON map2user.StepID = map.StepID
                    LEFT JOIN marathon_user AS mu ON map2user.MarathonUserID = mu.MarathonUserID
					WHERE Status = 'completed'".((count($where) > 0)?" AND ".implode(" AND ", $where):"")."
					GROUP BY map.StepID
					ORDER BY map.SortOrder ASC";
		$result = $stmt->FetchList($query);

		if ( !empty($selected) ){
			foreach ($result as $index => $item) {
				if (in_array($item['StepID'], $selected)){
					$result[$index]['Selected'] = 1;
				}
			}
		}

		return $result;
	}
	
	private static function getDateWhere($request){
	    $where = array();
	    if($request->GetProperty('DateFrom')){
	        $where[] = "mu.Created>".Connection::GetSQLDateTime($request->GetProperty('DateFrom'));
	    }
	    if($request->GetProperty('DateTo')){
	        $modifiedTo = new DateTime($request->GetProperty('DateTo'));
	        $modifiedTo->modify('+1 day');
	        $where[] = "mu.Created<".Connection::GetSQLDateTime($modifiedTo->format('Y-m-d H:i:s'));
	    }
	    return $where;
	}
}