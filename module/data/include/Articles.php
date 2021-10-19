<?php
require_once(dirname(__FILE__) . "/../init.php");
setlocale(LC_ALL, 'ru_RU.UTF8');
require_once (__DIR__ . '/../../share/include/share.php');
require_once (__DIR__ . '/ArticleTagList.php');

class Articles extends LocalObjectList
{
    private $module;
    private $now;
    private $params;

    public function __construct($module = 'data')
    {
    	parent::LocalObjectList();
        $this->module = $module;
        $this->now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
        $this->params['ArticleImage'] = LoadImageConfig('ArticleImage', $this->module, DATA_ARTICLE_IMAGE);
        $this->params['ArticleMainImage'] = LoadImageConfig('ArticleMainImage', $this->module, DATA_ARTICLEMAIN_IMAGE);
    }

    public function load(LocalObject $request, $countOnPage = 6, $notHidden = false)
    {
    	$where = array();
    	if ($notHidden == false){
    	    $where[] = "a.Active = 'Y'";
        }

    	if($request->GetProperty("TagID"))
    	{
    		$where[] = "at.TagID=".$request->GetIntProperty("TagID");
    	}

    	if ($request->IsPropertySet('ArticleFilter')){
			$filter = $request->GetProperty('ArticleFilter');
            $articleSearch = trim($filter['ArticleSearch']);

			if (!empty($articleSearch)){
				$where[] = "(a.Title LIKE " . Connection::GetSQLString('%' . $articleSearch . '%') . " OR " .
				    "a.Keywords LIKE " . Connection::GetSQLString('%' . $articleSearch . '%') . ")";
			}

			if (!empty($filter['Best'])){
				$where[] = "a.Best = 'Y'";
			}

			if (!empty($filter['Hidden']) && $notHidden == true){
				$where[] = "a.Active = 'N'";
			}

			if (!empty($filter['NoIndex'])){
				$where[] = "a.ToIndex = 'N'";
			}

            if (!empty($filter['OnMain'])){
                $where[] = "a.OnMain = 'Y'";
            }

            if (!empty($filter['AuthorID'])){
                $where[] = "a.AuthorID = {$filter['AuthorID']}";
            }
		}

		if ($notHidden == false){
			$where[] = "a.DateTime <= " . Connection::GetSQLString($this->now->format('Y-m-d H:i:s'));
		}

        $query = "SELECT a.*, author.Title AS Author, author.Description AS AuthorDescription,
        		CASE WHEN a.StaticPath IS NOT NULL AND a.StaticPath<>'' THEN CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', a.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') ELSE CONCAT(".$request->GetPropertyForSQL('BaseURL').", ".Connection::GetSQLString(HTML_EXTENSION).", '?ArticleID=', a.ArticleID) END AS ArticleURL,
        		GROUP_CONCAT(t.Title SEPARATOR ',') as Tags,
        		GROUP_CONCAT(t.TagID SEPARATOR ',') as TagsIDs
				FROM `data_article` AS a 
				LEFT JOIN `data_article2tag` at ON a.ArticleID=at.ArticleID 
				LEFT JOIN `data_article_tag` t ON at.TagID=t.TagID
				LEFT JOIN `data_author` AS author ON a.AuthorID=author.AuthorID
        		".((count($where) > 0)?" WHERE ".implode(" AND ", $where):"")."
        		GROUP BY a.ArticleID
        		ORDER BY a.DateTime DESC";

		$this->SetPageParam('ArticlePager');
        $this->SetItemsOnPage($countOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
        
        for($i=0; $i<count($this->_items); $i++) {
            $this->prepareImage($this->_items[$i], 'ArticleImage');
            $this->prepareImage($this->_items[$i], 'ArticleMainImage');
            $this->prepareTagList($this->_items[$i]);
        	$this->_items[$i]['__ROWNUM__'] = ($i+1);
        	$this->_items[$i]['PreviewDate'] = strftime("%d %b", strtotime($this->_items[$i]['DateTime']));
        	$this->_items[$i]['PreviewTitle'] = trimString($this->_items[$i]['Title'], 45);

            $this->_items[$i]['ShareCountAll'] = 0;

            foreach (Share::getItemsCountByItem($this->_items[$i]['ArticleID'], 'Article') as $shareItem => $count) {
                $this->_items[$i]['ShareItem' . $shareItem] = $count;
                $this->_items[$i]['ShareCountAll'] += $count;
            }
        }

        //print_r($this->_items);
    }

    public function prepareTagList(&$item){
        if (!empty($item['TagsIDs'])){
            $item['TagList'] = ArticleTagList::getListByIDs(explode(',', $item['TagsIDs']));
        }
    }

    public function LoadForSuggest(LocalObject $request){
        $this->_items = array();
        
        $query = "SELECT ArticleID AS value, Title AS label FROM `data_article`";
        if($request->IsPropertySet('term')){
            $term = $request->GetPropertyForSQL('term');
            if (empty($term)) {
                return;
            }
            $query .= " WHERE INSTR(Title, $term)";
            $itemIDs = $request->GetProperty('ItemIDs');
            if($itemIDs) {
                $query .= " AND ArticleID NOT IN (".implode(',', Connection::GetSQLArray($itemIDs)).")";
            }
        }
        $query .= " ORDER BY DateTime DESC";
			
        $this->SetItemsOnPage(0);
        $this->LoadFromSQL($query);
    }
    
    public function getTagList($tagID)
    {
    	$stmt = GetStatement();
    	$query = "SELECT *, IF(TagID = ".Connection::GetSQLString($tagID).", 1, 0) as Selected FROM data_article_tag ORDER BY Title";
    	return array_merge(array(array('TagID' => '', 'Title' => 'Все рубрики', 'Selected' => ($tagID?0:1))), $stmt->FetchList($query));
    }

    public function getItemInfo($id)
    {
        if (intval($id) < 1){
            return false;
        }

    	$stmt = GetStatement();
    	$query = "
            SELECT a.*, GROUP_CONCAT(t.Title SEPARATOR ',') as Tags,
            author.Title as Author,
            author.Description as AuthorDescription
    		FROM data_article a 
    		LEFT JOIN `data_article2tag` at ON a.ArticleID=at.ArticleID 
			LEFT JOIN `data_article_tag` t ON at.TagID=t.TagID
			LEFT JOIN `data_author` AS author ON a.AuthorID=author.AuthorID
    		WHERE a.ArticleID=".intval($id)."
    		GROUP BY a.ArticleID";
    	$item = $stmt->FetchRow($query);
        $item['CommentCount'] = self::getCommentCount($id);

    	$this->prepareImage($item, 'ArticleMainImage');
    	
    	$date = new DateTime($item['DateTime'], new DateTimeZone('Europe/Moscow'));
    	$item['DateTimeUTC'] = $date->format("Y-m-d\TH:i:s+0300");
        $item['PreviewDate'] = strftime("%d %b", strtotime($item['DateTime']));

        $item['ShareCountAll'] = 0;

        foreach (Share::getItemsCountByItem($item['ArticleID'], 'Article') as $shareItem => $count) {
            $item['ShareItem' . $shareItem] = $count;
            $item['ShareCountAll'] += $count;
        }
    	
    	return $item;
    }

    public static function getBestItemID(){
        $stmt = GetStatement();
        $query = "SELECT ArticleID FROM data_article WHERE Best = 'Y'";
        if ($id = $stmt->FetchField($query)){
            return $id;
        }

        return false;
    }

    protected static function getCommentCount($articleID){
        $stmt = GetStatement();
        $query = "SELECT COUNT(MessageID) FROM `question_message`
                  WHERE AttachID = " . intval($articleID) . "
                  AND Type = 'article'
                  AND Status = 'public'";

        if ($count = $stmt->FetchField($query)){
            return $count;
        }

        return false;
    }
    
    public function GetPopularList($request)
    {
    	$stmt = GetStatement();
    	$query = "SELECT a.*,
    		CASE WHEN a.StaticPath IS NOT NULL AND a.StaticPath<>'' THEN CONCAT(".$request->GetPropertyForSQL('BaseURL').", '/', a.StaticPath, ".Connection::GetSQLString(HTML_EXTENSION).", '/') ELSE CONCAT(".$request->GetPropertyForSQL('BaseURL').", ".Connection::GetSQLString(HTML_EXTENSION).", '?ArticleID=', a.ArticleID) END AS ArticleURL
        	FROM `data_article` a 
    		WHERE a.Popular='Y' AND a.DateTime <= " .Connection::GetSQLString($this->now->format('Y-m-d H:i:s')). "
    		AND a.Active = 'Y'
    		ORDER BY a.DateTime DESC";

        if ($result = $stmt->FetchList($query)){
            foreach ($result as $index => $item) {
                $this->prepareImage($result[$index], 'ArticleMainImage');
            }
            return $result;
        }

        return null;
    }

    public function getSimilarList($articleID = 99999){
        $stmt = GetStatement();
        $query = "SELECT ArticleID, SimilarArticleID FROM data_article_similar WHERE ArticleID = " . intval($articleID);
        if ($result = $stmt->FetchList($query)){
            $list = array();
            foreach ($result as $index => $item) {
                $list[] = $this->getItemInfo($item['SimilarArticleID']);
            }

            return $list;
        }
    }
    
    public function getIDByStaticPath($staticPath)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT a.ArticleID FROM `data_article` a WHERE a.StaticPath=".Connection::GetSQLString($staticPath));
    }
    
    public function getStaticPathByID($articleID)
    {
    	$stmt = GetStatement();
    	return $stmt->FetchField("SELECT a.StaticPath FROM `data_article` a WHERE a.ArticleID=".intval($articleID));
    }

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();

			$query = "DELETE FROM `data_article` WHERE ArticleID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);

			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("article-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}

        //remove unused tags
        ArticleTagList::removeUnused();
	}

	public static function addView($articleID){
        self::changeCounterField('ViewCount', $articleID, 1);
    }

    public static function addLike($articleID){
        self::changeCounterField('LikeCount', $articleID, 1);
    }

    /*public static function addLike($articleID){
        self::changeCounterField('LikeCount', $articleID, 1);
    }

    public static function removeLike($articleID){
        self::changeCounterField('LikeCount', $articleID, -1);
    }*/

    protected static function changeCounterField($field, $articleID, $count){
        if ($count > 0){
            $count = ' + ' . $count;
        }
        else{
            $count = ' - ' . abs($count);
        }

        $stmt = GetStatement();
        $query = "UPDATE data_article SET " . $field . " = " . $field . $count . " WHERE ArticleID = " . intval($articleID);
        if ($stmt->Execute($query)){
            return true;
        }

        return false;
    }

    protected function prepareImage(&$item, $name){
        if ($item && !empty($item[$name])) {
            foreach ($this->params[$name] as $param) {
                $item[$param['Name'].'Path'] = $param['Path'].'article/'.$item[$name];
            }
        }
    }

    public function getReadLaterTemplate($articleID){
        $info = $this->getItemInfo($articleID);
        $template = new Page();
        $template->LoadByStaticPath('email-read-later');
        $imageUrl = ImageManager::getImageUrl('data', $info['ArticleMainImage'], '600x200', 8, 'article/');
        $content = $template->GetProperty('Content');
        $content = str_replace('[ArticleLink]', GetUrlPrefix() . 'article/' . $info['StaticPath'], $content);
        $content = str_replace('[Title]', $info['Title'], $content);
        $content = str_replace('[ImageUrl]', $imageUrl, $content);
        //$content = str_replace('src="/', 'src="' . GetCurrentProtocol() . $_SERVER["HTTP_HOST"] . '/', $content);
        //$content = str_replace('src="/', 'src="https://propostuplenie.ru/', $content);

        //get tableOfContents;
        $articleDom = new DomDocument();
        $articleDom->loadHTML($info['Content']);
        $xpath = new DOMXpath($articleDom);
        $tableOfContents = $xpath->query("//div[@name='tableOfContents']");

        //create tableOfContents in letter
        $letterDom = new DomDocument();
        $letterDom->loadHTML($content);
        $tocBlock = $letterDom->getElementById('table-of-contents-block');
        $tocUl = $letterDom->getElementById('table-of-contents-list');
        $tocLi = $tocUl->firstChild;

        if ($tableOfContents->length > 0){
            foreach($tableOfContents as $item) {
                $tocItem = $tocLi->cloneNode();
                $tocItem->textContent = utf8_decode($item->textContent);
                $tocUl->appendChild($tocItem);
            }
            $tocLi->parentNode->removeChild($tocLi);
        }
        else{
            $tocBlock->parentNode->removeChild($tocBlock);
        }

        return $letterDom->saveHTML();
    }

}