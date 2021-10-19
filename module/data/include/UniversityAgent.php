<?php


/**
 * Class UniversityAgent
 */
class UniversityAgent extends LocalObject
{
    const TABLE_NAME = 'data_university_agent';
    const MODULE_NAME = 'data';

    public function __get($name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @param int $id
     * @return UniversityAgent|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["ID = {$id}"]);
    }

    /**
     * @param int $userId
     * @return static|null
     */
    public static function getByUserID(int $userId)
    {
        return self::getByWhere(["UserID = '{$userId}'"]);
    }

    /**
     * @param array $where
     * @return static|null
     */
    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'agent.*',
            ])
            ->from(self::TABLE_NAME . " AS agent")
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
        if (($this->GetIntProperty('UserID') < 1)) {
            $this->AddError('city-user-agent-UserID-empty', self::MODULE_NAME);
        }

        if ($this->GetIntProperty('UniversityID') < 1) {
            $this->AddError('city-user-agent-UniversityID-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('ExpireDate')) {
            $this->AddError('city-user-agent-ExpireDate-empty', self::MODULE_NAME);
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

        $query->setValue('UserID', $this->GetIntProperty('UserID'));
        $query->setValue('UniversityID', $this->GetIntProperty('UniversityID'));
        $query->setValue('AuthorID', $this->GetIntProperty('AuthorID'));
        $query->setValue('ExpireDate', Connection::GetSQLDate($this->GetProperty('ExpireDate')));

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

    public function isActive(): bool
    {
        return $this->GetProperty('ExpireDate') > GetCurrentDate();
    }
}