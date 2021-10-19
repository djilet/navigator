<?php
require_once(dirname(__FILE__)."/../../init.php"); 
require_once(dirname(__FILE__)."/author.php");
es_include("localobjectlist.php");
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class DataAuthorList extends LocalObjectList implements TemplateListInterface
{
    use TemplateListMethods;
	var $module;
	var $params;

	function __construct($module = 'data', $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "a.Title ASC",
			"title_desc" => "a.Title DESC",
			"sortorder_asc" => "a.SortOrder ASC",
			"sortorder_desc" => "a.SortOrder DESC",
		));

		$this->params = array("Author" => array());
		$this->params["Author"] = LoadImageConfig("AuthorImage", $this->module, DATA_AUTHOR_IMAGE);
		$this->SetOrderBy("sortorder_asc");
	}

	function LoadAuthorList()
	{
		$where = array();
	
		$query = "SELECT a.AuthorID, a.Title, a.Description, a.AuthorImage, a.AuthorImageConfig, a.SortOrder 				
					FROM `data_author` AS a			
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "");

		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
			foreach ($this->params as $k => $v)
			{
				PrepareImagePath($this->_items[$i], $k, $v, "author/");
			}
		}
	}

	public function getAssocItems(string $key)
    {
        $results = $this->_items;
        $indexedResult = array();
        foreach ($results as $result) {
            if (array_key_exists($key, $result)) {
                $indexedResult[$result[$key]] = $result;
            }
        }
        return $indexedResult;
    }

	function Remove($ids)
	{
		if (is_array($ids) && count($ids) > 0)
		{
			$stmt = GetStatement();
			$query = "SELECT * FROM `data_author` 
						WHERE AuthorID IN(".implode(", ", Connection::GetSQLArray($ids)).")";
			$result = $stmt->FetchList($query);
			for ($i = 0; $i < count($result); $i++)
			{
				foreach ($this->params as $k => $v)
				{
					if ($result[$i][$k.'Image'])
					{
						@unlink(DATA_IMAGE_DIR."author/".$result[$i][$k."Image"]);
					}
				}
			}
			
			$query = "DELETE FROM `data_author` WHERE AuthorID IN (".implode(", ", Connection::GetSQLArray($ids)).")";
			$stmt->Execute($query);
			
			if ($stmt->GetAffectedRows() > 0)
			{
				$this->AddMessage("author-removed", $this->module, array("Count" => $stmt->GetAffectedRows()));
			}
		}
	}

	public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName(DataAuthor::PRIMARY_KEY, 'Title', $selected, $items);
    }
}

?>