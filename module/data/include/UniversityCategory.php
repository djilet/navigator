<?php


/**
 * Class UniversityCategory
 */
class UniversityCategory extends LocalObject
{
    const TABLE_NAME = 'data_university_category';
    const MODULE_NAME = 'data';

    public function __get($name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @param int $id
     * @return UniversityCategory|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["ID = {$id}"]);
    }

    /**
     * @param array $where
     * @return static|null
     */
    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'category.*',
            ])
            ->from(self::TABLE_NAME . " AS category")
            ->where($where);

        $item = new static();
        $item->LoadFromSQL($query->getSQL());
        if ($item->GetIntProperty('ID') > 0){
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
            $this->AddError('validate-error-empty-field', null, ['Field' => 'Название']);
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
        if ($this->GetIntProperty('ID') > 0){
            $query->update(self::TABLE_NAME);
            $query->addWhere("ID = {$this->GetIntProperty('ID')}");
        }
        else{
            $query->insert(self::TABLE_NAME);
        }

        $query->setValue('Title', $this->GetPropertyForSQL('Title'));

        $stmt = GetStatement();
        // dd($query->getSQL());
        if ($stmt->Execute($query->getSQL())){
            if ($this->GetIntProperty('ID') < 1){
                $this->SetProperty('ID', $stmt->GetLastInsertID());
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
        $id = $this->GetProperty('ID');
        $query = QueryBuilder::init()->delete()->from(self::TABLE_NAME)->where(["ID = {$id}"]);
        if (GetStatement()->Execute($query->getSQL())){
            return true;
        }

        return false;
    }
}