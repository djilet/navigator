<?php


/**
 * Class OnlineExhibitionParticipant
 */
class OnlineExhibitionParticipant extends LocalObject
{
    const TABLE_NAME = 'data_online_exhibition_participant';
    const MODULE_NAME = 'data';

    protected static $params;

    public function __get($name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @param int $id
     * @return OnlineExhibitionParticipant|null
     */
    public static function get(int $id)
    {
        return self::getByWhere(["ID = {$id}"]);
    }

    public static function prepareRow(array $row)
    {
        //Image
        foreach (self::getParams()['Image'] as $param){
            if (!empty($row['MainImage'])){
                $row[$param['Name'].'Path'] = $param['Path'].'online_exhibition/'.$row['MainImage'];
            }
        }

        if (!empty($row['YouTubeUrl'])){
            $row['VideoID'] = GetVideoIdFromYouTube($row['YouTubeUrl']);
        }

        //$row['OnlineEventIDs'] = json_decode($row['OnlineEventIDs']);

        return $row;
    }

    public static function getParams(): array
    {
        if (empty(self::$params)){
            self::$params['Image'] = LoadImageConfig('Image', self::MODULE_NAME, DATA_ONLINE_EXHIBITION_PARTICIPANT_IMAGE);
        }

        return self::$params;
    }

    /**
     * @param array $where
     * @return static|null
     */
    protected static function getByWhere(array $where)
    {
        $query = QueryBuilder::init()
            ->select([
                'o_exhibition_part.*',
            ])
            ->from(self::TABLE_NAME . " AS o_exhibition_part")
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

        if ($this->GetIntProperty('UniversityID') < 1) {
            $this->AddError('common-University-empty', self::MODULE_NAME);
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

        $onlineEventIDs = null;
        if (!empty($this->GetProperty('OnlineEventIDs'))){
            $onlineEventIDs = $this->GetProperty('OnlineEventIDs');
            if (is_array($onlineEventIDs)){
                $onlineEventIDs = json_encode($onlineEventIDs);
            }
        }

        $query->setValue('Title', $this->GetPropertyForSQL('Title'));
        $query->setValue('MainImage', $this->GetPropertyForSQL('MainImage'));
        $query->setValue('UniversityID', $this->GetIntProperty('UniversityID'));
        $query->setValue('YouTubeUrl', $this->GetPropertyForSQL('YouTubeUrl'));
        $query->setValue('OnlineEventIDs', Connection::GetSQLString($onlineEventIDs));
        $query->setValue('Description', $this->GetPropertyForSQL('Description'));
        $query->setValue('AboutTitle', $this->GetPropertyForSQL('AboutTitle'));
        $query->setValue('QuestionTitle', $this->GetPropertyForSQL('QuestionTitle'));
        $query->setValue('AttachmentUrl', $this->GetPropertyForSQL('AttachmentUrl'));
        $query->setValue('UniversityWebsiteUrl', $this->GetPropertyForSQL('UniversityWebsiteUrl'));
        $query->setValue('OnlineExhibitionID', $this->GetIntProperty('OnlineExhibitionID'));
        $query->setValue('SortOrder', $this->GetIntProperty('SortOrder'));

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

    public function prepareForTemplate()
    {
        $this->LoadFromArray(self::prepareRow($this->GetProperties()));
    }

    public function getOnlineEventIDs(): array
    {
        $result = json_decode($this->OnlineEventIDs);
        if (is_array($result)){
            return $result;
        }

        return [];
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