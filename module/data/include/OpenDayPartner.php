<?php
/**
 * @property $ID
 * @property $OpenDayID
 * @property $Type
 * @property $Title
 */
class OpenDayPartner extends LocalObject
{

    const TABLE_NAME = 'data_open_day_partner';
    const MODULE_NAME = 'data';
    const TYPE_MAIN = 'main';
    const TYPE_COMMON = 'common';
    const TYPES = [self::TYPE_MAIN, self::TYPE_COMMON];

    /**
     * @var array
     */
    protected static $params = [];

    /**
     * OpenDayPartner constructor.
     */
    public function __construct()
    {
        parent::LocalObject();
    }

    /**
     * @return array
     */
    public static function getParams(): array
    {
        if (empty(self::$params)){
            self::$params['Image'] = LoadImageConfig('PartnerImage', self::MODULE_NAME, DATA_OPEN_DAY_PARTNER_IMAGE);
        }

        return self::$params;
    }

    /**
     * @param int $id
     * @return OpenDayPartner|null
     */
    public static function load(int $id)
    {
        $query = QueryBuilder::init()
            ->select(['*'])
            ->from(self::TABLE_NAME)
            ->addWhere("ID = {$id}");

        $item = new static();
        $item->LoadFromSQL($query->getSQL());
        if ($item->GetIntProperty('ID') > 0){
            return $item;
        }

        return null;
    }

    /**
     * @param QueryBuilder $query
     * @param array $filter
     */
    public static function prepareFilter(QueryBuilder $query, array $filter)
    {
        if (!empty($filter)) {
            if (isset($filter['Type'])) {
                $types = is_array($filter['Type']) ? implode(',', $filter['Type']) : Connection::GetSQLString($filter['Type']);
                $query->addWhere("Type IN ({$types})");
            }

            if (!empty($filter['OpenDayID'])) {
                $query->addWhere("OpenDayID = {$filter['OpenDayID']}");
            }
        }
    }

    /**
     * @param array $filter
     * @return LocalObjectList
     */
    public static function getAll(array $filter = []): LocalObjectList
    {
        $query = QueryBuilder::init()
            ->select(['*'])
            ->from(self::TABLE_NAME);

        self::prepareFilter($query, $filter);

        $list = new LocalObjectList();
        $list->LoadFromSQL($query->getSQL());

        foreach ($list->_items as $key => $item) {
            if (!empty($item['Image'])) {
                foreach (self::getParams()['Image'] as $param) {
                    $list->_items[$key][$param['Name'].'Path'] = $param['Path'].'openday/'.$item['Image'];
                }
            }
        }

        return $list;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        //TODO validate
        return true;
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

        $query->setValue('OpenDayID', $this->GetIntProperty('OpenDayID'));
        $query->setValue('Type', $this->GetPropertyForSQL('Type'));
        $query->setValue('Title', $this->GetPropertyForSQL('Title'));
        $query->setValue('Image', $this->GetPropertyForSQL('Image'));

        $stmt = GetStatement();
        //echo $query->getSQL();exit();
        if ($stmt->Execute($query->getSQL())){
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
        $stmt = GetStatement();
        if ($stmt->Execute($query->getSQL())){
            if ($this->ValidateNotEmpty('Image')){
                ImageManager::RemoveImage(DATA_OPEN_DAY_IMAGE_DIR.$this->GetProperty('Image'));
                return true;
            }
        }

        return false;
    }

//service

    /**
     * @param string $oldImage
     * @param array $newImage
     * @return mixed|string
     */
    public static function replaceImage(string $oldImage, array $newImage)
    {
        if (!empty($newImage) and $newImage['error'] == 0) {
            $image = $newImage['FileName'];

            if (!empty($oldImage) and file_exists(DATA_OPEN_DAY_IMAGE_DIR.$oldImage)
                and is_file(DATA_OPEN_DAY_IMAGE_DIR.$oldImage)) {
                unlink(DATA_OPEN_DAY_IMAGE_DIR.$oldImage);
            }
        } else {
            $image = $oldImage;
        }

        return $image;
    }
}