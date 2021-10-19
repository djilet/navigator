<?php


/**
 * Class City
 */
class City extends LocalObject
{
    const TABLE_NAME = 'data_city';
    const MODULE_NAME = 'data';

    /**
     * @param int $id
     * @return City|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["ID = {$id}"]);
    }

    public static function getByStaticPath(string $staticPath)
    {
        return self::getByWhere(["StaticPath = '{$staticPath}'"]);
    }

    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'city.*',
                'region.Title AS RegionTitle'
            ])
            ->from(self::TABLE_NAME . " AS city")
            ->addJoin("LEFT JOIN data_region AS region ON city.RegionID = region.RegionID")
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
            $this->AddError('city-save-title-empty', self::MODULE_NAME);
        }

        if ($this->GetIntProperty('RegionID') < 1) {
            $this->AddError('city-save-region-empty', self::MODULE_NAME);
        }

        if (!$this->ValidateNotEmpty('StaticPath')) {
            $this->AddError('city-save-static-path-empty', self::MODULE_NAME);
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
        $query->setValue('RegionID', $this->GetIntProperty('RegionID'));
        $query->setValue('StaticPath', $this->GetPropertyForSQL('StaticPath'));

        $stmt = GetStatement();
        //echo $query->getSQL();exit();
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