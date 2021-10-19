<?php


class ChatUser extends LocalObject
{
    const TABLE_NAME = 'chat_user';
    const CONNECTION_TYPE_USER = 'user';
    const CONNECTION_TYPE_SESSION = 'session';
    const CHAT_STATUS_SIMPLE = 'simple';

    public function __get($key)
    {
        return $this->GetProperty($key);
    }

    public function __set($key, $value)
    {
        return $this->SetProperty($key, $value);
    }


    /**
     * @param int $id
     * @return self|null
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
        return self::getByWhere([
            "ConnectionID = '{$userId}'",
            "ConnectionType = " . Connection::GetSQLString(self::CONNECTION_TYPE_USER),
        ]);
    }

    /**
     * @param string $sessionID
     * @return static|null
     */
    public static function getBySessionID(string $sessionID)
    {
        return self::getByWhere([
            "ConnectionID = '{$sessionID}'",
            "ConnectionType = " . Connection::GetSQLString(self::CONNECTION_TYPE_SESSION),
        ]);
    }

    /**
     * @param array $where
     * @return static|null
     */
    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                '*',
            ])
            ->from(self::TABLE_NAME)
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
    public function save(): bool
    {
        $query = QueryBuilder::init();
        if ($this->GetIntProperty('ID') > 0){
            $query->update(self::TABLE_NAME);
            $query->addWhere("ID = {$this->GetIntProperty('ID')}");
        }
        else{
            $query->insert(self::TABLE_NAME);
        }

        //prepare
        if (!$this->ValidateNotEmpty('ChatStatus')){
            $this->ChatStatus = self::CHAT_STATUS_SIMPLE;
        }

        $query->setValue('UserName', $this->GetPropertyForSQL('UserName'));
        $query->setValue('ChatStatus', $this->GetPropertyForSQL('ChatStatus'));
        $query->setValue('ChatLimitDate', $this->GetPropertyForSQL('ChatLimitDate'));
        $query->setValue('ConnectionType', $this->GetPropertyForSQL('ConnectionType'));
        $query->setValue('ConnectionID', $this->GetPropertyForSQL('ConnectionID'));

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
}