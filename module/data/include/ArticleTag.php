<?php
es_include('interfaces/static_list_interface.php');
es_include('interfaces/static_list_methods.php');

class ArticleTag extends LocalObject
{
    const TABLE_NAME = 'data_article_tag';
    const MODULE_NAME = 'data';

    /**
     * @param int $id
     * @return ArticleTag|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["TagID = {$id}"]);
    }

    public static function getByStaticPath(string $staticPath)
    {
        return self::getByWhere(["StaticPath = '{$staticPath}'"]);
    }

    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select(['*'])
            ->from(self::TABLE_NAME)
            ->where($where);

        $item = new static();
        $item->LoadFromSQL($query->getSQL());
        if ($item->GetIntProperty('TagID') > 0){
            return $item;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (!$this->ValidateNotEmpty('Title')) {
            $this->AddError('article-tag-save-title-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('StaticPath')) {
            $this->AddError('article-tag-save-static-path-empty', self::MODULE_NAME);
        }

        return !$this->HasErrors();
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->validate()){
            return false;
        }

        $query = QueryBuilder::init();
        if ($this->GetIntProperty('TagID') > 0){
            $query->update(self::TABLE_NAME);
            $query->addWhere("TagID = {$this->GetIntProperty('TagID')}");
        }
        else{
            $query->insert(self::TABLE_NAME);
        }

        $query->setValue('Title', $this->GetPropertyForSQL('Title'));
        $query->setValue('StaticPath', $this->GetPropertyForSQL('StaticPath'));

        $stmt = GetStatement();
        //echo $query->getSQL();exit();
        if ($stmt->Execute($query->getSQL())){
            if ($this->GetIntProperty('TagID') < 1){
                $this->SetProperty('TagID', $stmt->GetLastInsertID());
            }
            return true;
        }

        $this->AddError('sql-error');
        return false;
    }

    /**
     * @return bool
     */
    public function remove(): bool
    {
        $id = $this->GetProperty('TagID');
        $query = QueryBuilder::init()->delete()->from(self::TABLE_NAME)->where(["TagID = {$id}"]);
        if (GetStatement()->Execute($query->getSQL())){
            $query = QueryBuilder::init()->delete()->from("data_article2tag")->where(["TagID = {$id}"]);
            GetStatement()->Execute($query->getSQL());
            return true;
        }

        return false;
    }
}