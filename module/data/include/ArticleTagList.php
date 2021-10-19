<?php
require_once (__DIR__ . '/ArticleTag.php');
es_include('interfaces/static_list_interface.php');
es_include('interfaces/static_list_methods.php');
es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');

class ArticleTagList extends LocalObjectList implements StaticListInterface, TemplateListInterface
{
    use StaticListMethods;
    use TemplateListMethods;

    public static function createStaticList()
    {
        self::createFromLocalObjectList();
    }

    public static function getListByIDs(array $ids){
        $result = [];
        $list = self::getAssocStaticList('TagID');
        foreach ($ids as $index => $id) {
            if (isset($list[$id])){
                $result[] = $list[$id];
            }
        }

        return (!empty($result) ? $result : null);
    }

    public static function remove($ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return false;
        }

        $tag = new ArticleTag();
        foreach ($ids as $id){
            $tag->LoadFromArray(['TagID' => $id]);
            $tag->remove();
        }

        return true;
    }

    public static function removeUnused(){
        $stmt = GetStatement();
        $query= "SELECT tag.TagID FROM `data_article_tag` AS tag
                LEFT JOIN data_article2tag AS art2tag ON tag.TagID = art2tag.TagID
                LEFT JOIN data_article AS article ON art2tag.ArticleID = article.ArticleID
                GROUP BY tag.TagID
                HAVING COUNT(article.ArticleID) < 1";

        $allTags = $stmt->FetchList($query);
        for($i=0; $i<count($allTags); $i++)
        {
            if($allTags[$i]["TagCount"] == 0)
            {
                $query= "DELETE FROM `data_article_tag` WHERE TagID=".$allTags[$i]["TagID"];
                $stmt->Execute($query);
            }
        }
    }

    public function load(){
        $query = "SELECT * FROM data_article_tag";
        $this->LoadFromSQL($query);
    }

    /**
     * @inheritDoc
     */
    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName('TagID', 'Title', $selected, $items);
    }
}