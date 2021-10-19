<?php
require_once(__DIR__ . "/message.php");

class QuestionMessageList extends LocalObjectList
{
    private $module;

    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;
    }

    public function getItemsWithAuthorInfo(DataAuthorList $authorList)
    {
        $result = [];
        $authors = $authorList->getAssocItems(DataAuthor::PRIMARY_KEY);

        foreach ($this->_items as $index => $item){
            if ($item['Type'] == QuestionMessage::TYPE_ARTICLE && isset($authors[$item['AuthorID']])){
                $item = array_merge($item, appendPrefixForArrayKeys($authors[$item['AuthorID']], 'Author'));
            }

            if (!empty($item['ChildList'])){
                foreach ($item['ChildList'] as $childIndex => $child){
                    if (isset($authors[$child['AuthorID']])){
                        $item['ChildList'][$childIndex] = array_merge($child, appendPrefixForArrayKeys($authors[$child['AuthorID']], 'Author'));
                    }
                }
            }

            $result[] = $item;
        }

        return $result;
    }

    public function load(LocalObject $request, $itemsOnPage = 10)
    {
        $where = array();
        $where[] = "m.ParentID IS NULL";

        if ($request->IsPropertySet('MultipleFilter')){
            $condition = [];
            foreach ($request->GetProperty('MultipleFilter') as $filter){
                if (empty($filter['AttachIds']) || empty($filter['Type'])){
                    continue;
                }
                $attachRow = implode(",", Connection::GetSQLArray($filter['AttachIds']));
                $condition[] = "(m.Type = '{$filter['Type']}' AND m.AttachID IN({$attachRow}))";
            }

            $where[] = "(" . implode(" OR ", $condition) . ")";
        }

        if($request->IsPropertySet('Type') && $request->IsPropertySet('AttachID'))
        {
            $where[] = "m.Type=".$request->GetPropertyForSQL('Type');
            $where[] = "m.AttachID=".$request->GetPropertyForSQL('AttachID');
        }
        if($request->IsPropertySet('Status'))
        {
            $where[] = "m.Status=".$request->GetPropertyForSQL('Status');
        }

        if ($dateFrom = $request->GetProperty('DateFrom')){
            $where[] = "m.Created >= " . Connection::GetSQLDateTime($dateFrom);
        }

        if ($dateTo = $request->GetProperty('DateTo')){
            $where[] = "m.Created <= " . Connection::GetSQLDateTime(date('d.m.Y', strtotime($dateTo) + 24 * 3600));
        }

        if ($request->IsPropertySet('NoneAnswer')){
            $where[] = "m.MessageID IN (SELECT q.MessageID FROM `question_message` AS q
            LEFT JOIN question_message AS q_child ON q.MessageID = q_child.ParentID " . ($request->IsPropertySet('Colored') ? " AND q_child.Colored = 'Y'" : "")  . "
            WHERE q.ParentID IS NULL
            GROUP BY q.MessageID
            HAVING COUNT(q_child.MessageID) * 1 < 1)";
        }

        $query = "SELECT m.*, u.UserID, u.UserName, u.CommentsStatus
			FROM `question_message` AS m
			LEFT JOIN `users_item` u ON u.UserID=m.UserID
			LEFT JOIN `data_article` AS d ON d.ArticleID=m.AttachID
			".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
			ORDER BY m.Created DESC";

        $this->SetItemsOnPage($itemsOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
        $this->prepare($request);
    }

    protected function prepare($request)
    {
        if(count($this->_items) > 0)
        {
            $idMap = array();
            for($i=0; $i<count($this->_items); $i++)
            {
                $idMap[$this->_items[$i]["MessageID"]] = $i;
                $this->_items[$i]["ChildList"] = array();
            }

            $stmt = GetStatement();
            $where = array();
            $where[] = "m.ParentID IN (".implode(",", array_keys($idMap)).")";
            if($request->IsPropertySet('Status'))
            {
                $where[] = "m.Status=".$request->GetPropertyForSQL('Status');
            }
            $query = "SELECT m.*, u.UserName, author.Title as AuthorTitle
				FROM `question_message` m
				LEFT JOIN `users_item` u ON u.UserID=m.UserID
				LEFT JOIN `data_author` author ON m.AuthorID=author.AuthorID
				".(count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "")."
				ORDER BY m.Created";
            $answers = $stmt->FetchList($query);
            for($i=0; $i<count($answers); $i++)
            {
                $this->_items[$idMap[$answers[$i]["ParentID"]]]["ChildList"][] = $answers[$i];
            }

            $fmt = new IntlDateFormatter(
                'ru_RU',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Europe/Moscow',
                IntlDateFormatter::GREGORIAN
            );
            $fmt->setPattern("d MMMM");
            for($i=0; $i<count($this->_items); $i++)
            {
                $this->_items[$i]["CreatedText"] = $fmt->format(new DateTime($this->_items[$i]["Created"], new DateTimeZone('Europe/Moscow')));
                for($j=0; $j<count($this->_items[$i]["ChildList"]); $j++)
                {
                    $this->_items[$i]["ChildList"][$j]["CreatedText"] = $fmt->format(new DateTime($this->_items[$i]["ChildList"][$j]["Created"], new DateTimeZone('Europe/Moscow')));
                }
                $this->_items[$i]["ChildCount"] = count($this->_items[$i]["ChildList"]);
            }
        }
    }

    public function prepareAttachInfo()
    {
        $universityIDs = array();
        $articleIDs = array();
        $specialityIDs = array();
        $collegeIDs = array();
        $collegeSpecialityIDs = array();
        for($i=0; $i<count($this->_items); $i++)
        {
            if($this->_items[$i]["Type"] == "university")
            {
                $universityIDs[] = $this->_items[$i]["AttachID"];
            }
            elseif($this->_items[$i]["Type"] == "college")
            {
                $collegeIDs[] = $this->_items[$i]["AttachID"];
            }
            else if($this->_items[$i]["Type"] == "article")
            {
                $articleIDs[] = $this->_items[$i]["AttachID"];
            }
            else if($this->_items[$i]["Type"] == "speciality")
            {
                $specialityIDs[] = $this->_items[$i]["AttachID"];
            }
            else if($this->_items[$i]["Type"] == "collegeSpeciality")
            {
                $collegeSpecialityIDs[] = $this->_items[$i]["AttachID"];
            }
        }
        $stmt = GetStatement();
        $query = "SELECT u.UniversityID, u.ShortTitle FROM `data_university` u WHERE u.UniversityID IN (".implode(",", $universityIDs).")";
        $universityList = $stmt->FetchIndexedList($query, 'UniversityID');

        $query = "SELECT col.CollegeID, col.Title, col.ShortTitle FROM `college_college` AS col WHERE col.CollegeID IN (".implode(",", $collegeIDs).")";
        $collegeList = $stmt->FetchIndexedList($query, 'CollegeID');

        $query = "SELECT a.ArticleID, a.Title FROM `data_article` a WHERE a.ArticleID IN (".implode(",", $articleIDs).")";
        $articleList = $stmt->FetchIndexedList($query, 'ArticleID');

        $query = "SELECT s.SpecialityID, s.Title, u.ShortTitle FROM `data_speciality` s LEFT JOIN `data_university` u ON s.UniversityID=u.UniversityID WHERE s.SpecialityID IN (".implode(",", $specialityIDs).")";
        $specialityList = $stmt->FetchIndexedList($query, 'SpecialityID');

        $query = "SELECT spec.CollegeSpecialityID, spec.Title, col.Title AS CollegeTitle, col.ShortTitle FROM `college_speciality` AS spec
				  LEFT JOIN `college_college` AS col ON spec.CollegeID=col.CollegeID
				  WHERE spec.CollegeSpecialityID IN (".implode(",", $collegeSpecialityIDs).")";
        $collegeSpecialityList = $stmt->FetchIndexedList($query, 'CollegeSpecialityID');

        for($i=0; $i<count($this->_items); $i++)
        {
            if($this->_items[$i]["Type"] == "university")
            {
                $this->_items[$i]["AttachTitle"] = $universityList[$this->_items[$i]["AttachID"]]["ShortTitle"];
                $this->_items[$i]["AttachID"] = $universityList[$this->_items[$i]["AttachID"]]["UniversityID"];
            }
            if($this->_items[$i]["Type"] == "college")
            {
                if (!empty($collegeList[$this->_items[$i]["AttachID"]]["ShortTitle"])){
                    $this->_items[$i]["AttachTitle"] = $collegeList[$this->_items[$i]["AttachID"]]["ShortTitle"];
                }
                else{
                    $this->_items[$i]["AttachTitle"] = $collegeList[$this->_items[$i]["AttachID"]]["Title"];
                }
                $this->_items[$i]["AttachID"] = $collegeList[$this->_items[$i]["AttachID"]]["CollegeID"];
            }
            if($this->_items[$i]["Type"] == "article")
            {
                $this->_items[$i]["AttachTitle"] = $articleList[$this->_items[$i]["AttachID"]]["Title"];
                $this->_items[$i]["AttachID"] = $articleList[$this->_items[$i]["AttachID"]]["ArticleID"];
            }
            if($this->_items[$i]["Type"] == "speciality")
            {
                $info = $specialityList[$this->_items[$i]["AttachID"]];
                $this->_items[$i]["AttachTitle"] = $info["ShortTitle"].': '.$info["Title"];
                $this->_items[$i]["AttachID"] = $info["SpecialityID"];
            }
            if($this->_items[$i]["Type"] == "collegeSpeciality")
            {
                $info = $collegeSpecialityList[$this->_items[$i]["AttachID"]];
                $this->_items[$i]["AttachTitle"] = (!empty($info["ShortTitle"]) ? $info["ShortTitle"] : $info["CollegeTitle"]) .': '.$info["Title"];
                $this->_items[$i]["AttachID"] = $info["CollegeSpecialityID"];
            }

            $this->_items[$i]["AttachUrl"] = getItemUrl($this->_items[$i]["Type"], $this->_items[$i]["AttachID"]);
        }
    }

    public function removeByIDs($ids)
    {
        if (empty($ids) or !is_array($ids)) {
            return false;
        }
        $list = implode(',', $ids);

        $stmt = GetStatement();
        $query = 'DELETE FROM `question_message` WHERE MessageID IN(' . $list . ')';
        if ($stmt->Execute($query)) {
            //remove child mesages
            $query = 'DELETE FROM `question_message` WHERE ParentID IN(' . $list . ')';
            $stmt->Execute($query);

            return true;
        }

        return false;
    }

    public function remove(array $filter)
    {
        $query = queryBuilder::init();
        $query->addSelect('MessageID')
            ->from('question_message');

        //Prepare common filter
        if (!empty($filter)){
            if (!empty($filter['MultipleFilter'])){
                $condition = [];
                foreach ($filter['MultipleFilter'] as $filterItem){
                    if (empty($filterItem['AttachIds']) || empty($filterItem['UserIds']) || empty($filterItem['Type'])){
                        continue;
                    }

                    $attachRow = implode(",", Connection::GetSQLArray($filterItem['AttachIds']));
                    $userRow = implode(",", Connection::GetSQLArray($filterItem['UserIds']));
                    $condition[] = "(Type = '{$filterItem['Type']}' AND AttachID IN({$attachRow}) AND UserID IN ({$userRow}))";
                }

                $query->addWhere(implode(" OR ", $condition));
            }

            if (!empty($filter['CreatedGt'])){
                $query->addWhere("Created > '{$filter['CreatedGt']}'");
            }
        }

        if ($ids = GetStatement()->FetchRows($query->getSQL())){
            return $this->removeByIDs($ids);
        }

        return false;
    }

    //Todo common remove
    public function removeByUserID(array $userIDs, int $period = 7)
    {
        $date = new DateTime();
        $date->modify("-{$period} day");
        $dateQuery = Connection::GetSQLString($date->format('Y-m-d'));
        $stmt = GetStatement();
        $query = QueryBuilder::init();
        $query->delete()
            ->from('question_message')
            ->where([
                "UserID IN (" . implode(',', $userIDs) . ") AND Created > {$dateQuery}",
            ]);
        if ($stmt->Execute($query->getSQL())) {
            return true;
        }

        return false;
    }
}