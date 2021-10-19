<?php


/**
 * Class OnlineExhibition
 */
class OnlineExhibition extends LocalObject
{
    const TABLE_NAME = 'data_online_exhibition';
    const MODULE_NAME = 'data';

    public function __get($name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @param int $id
     * @return OnlineExhibition|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["ID = {$id}"]);
    }

    public static function getByStaticPath(string $staticPath)
    {
        return self::getByWhere(["StaticPath = '{$staticPath}'"]);
    }

    public static function prepareForTemplate(self $exhibition)
    {
        $exhibition->SetProperty('DateFromText', strftime('%d %B', strtotime($exhibition->DateFrom)));
        $exhibition->SetProperty('DateToText', strftime('%d %B', strtotime($exhibition->DateTo)));
    }

    /**
     * @param array $where
     * @return static|null
     */
    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'o_exhibition.*',
            ])
            ->from(self::TABLE_NAME . " AS o_exhibition")
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
            $this->AddError('common-Title-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('StaticPath')) {
            $this->AddError('common-StaticPath-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('DateFrom')) {
            $this->AddError('common-DateFrom-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('DateTo')) {
            $this->AddError('common-DateFrom-empty', self::MODULE_NAME);
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
        $query->setValue('StaticPath', $this->GetPropertyForSQL('StaticPath'));
        $query->setValue('DateFrom', Connection::GetSQLDate($this->GetProperty('DateFrom')));
        $query->setValue('DateTo', Connection::GetSQLDate($this->GetProperty('DateTo')));
        $query->setValue('Description', $this->GetPropertyForSQL('Description'));

        $stmt = GetStatement();
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