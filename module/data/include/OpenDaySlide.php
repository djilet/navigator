<?php
/**
 * @property $ID
 * @property $OpenDayID
 * @property $Type
 * @property $Title
 * @property $Image
 */
class OpenDaySlide extends LocalObject
{
    const TABLE_NAME = 'data_open_day_slide';
    const MODULE_NAME = 'data';

    /**
     * @var array
     */
    protected static $params;

    /**
     * @param int $id
     * @return OpenDaySlide|null
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
            self::prepare($item);
            return $item;
        }

        return null;
    }

    /**
     * @param int $openDayID
     * @return LocalObjectList
     */
    public static function getAll(int $openDayID): LocalObjectList
    {
        $query = QueryBuilder::init()
            ->select(['*'])
            ->from(self::TABLE_NAME)
            ->where(["OpenDayID = {$openDayID}"]);

        $list = new LocalObjectList();
        $list->LoadFromSQL($query->getSQL());
        self::prepareList($list);
        return $list;
    }

    /**
     * @param OpenDaySlide $openDay
     */
    public static function prepare(OpenDaySlide $openDay)
    {

    }

    /**
     * @return array
     */
    public static function getParams(): array
    {
        if (empty(self::$params)){
            self::$params['Image'] = LoadImageConfig('Image', self::MODULE_NAME, DATA_OPEN_DAY_SLIDER_IMAGE);
        }

        return self::$params;
    }

    /**
     * @return bool|mixed|null
     */
    public static function getMaxSortOrder()
    {
        return GetStatement()->FetchField(QueryBuilder::init()->select(['MAX(SortOrder)'])->from(self::TABLE_NAME)->getSQL());
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (!$this->ValidateNotEmpty('OpenDayID')) {
            $this->AddError('open-day-slide-save-open-day-id-empty', self::MODULE_NAME);
        }
        if (!$this->ValidateNotEmpty('Title')) {
            $this->AddError('open-day-slide-save-title-from-empty', self::MODULE_NAME);
        }

        return !$this->HasErrors();
    }

    public static function prepareList(LocalObjectList $list)
    {
        foreach ($list->_items as $key => $item) {
            if (!empty($item['Image'])) {
                foreach (self::getParams()['Image'] as $param) {
                    $list->_items[$key][$param['Name'].'Path'] = $param['Path'].'openday/slider/'.$item['Image'];
                }
            }
        }
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
        $query->setValue('Title', $this->GetPropertyForSQL('Title'));
        $query->setValue('Image', $this->GetPropertyForSQL('Image'));
        $query->setValue('SortOrder', intval(self::getMaxSortOrder()) + 1);

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
        $stmt = GetStatement();
        if ($stmt->Execute($query->getSQL())){
            if ($this->ValidateNotEmpty('Image')){
                ImageManager::RemoveImage(DATA_OPEN_DAY_SLIDER_IMAGE_DIR.$this->GetProperty('Image'));
                return true;
            }
        }

        return false;
    }

//Service
    /**
     * @param string $oldImage
     * @param array $newImage
     * @return mixed|string
     */
    public static function replaceImage(string $oldImage, array $newImage)
    {
        if (!empty($newImage) and $newImage['error'] == 0) {
            $image = $newImage['FileName'];

            if (!empty($oldImage) and file_exists(DATA_OPEN_DAY_SLIDER_IMAGE_DIR.$oldImage)
                and is_file(DATA_OPEN_DAY_SLIDER_IMAGE_DIR.$oldImage)) {
                unlink(DATA_OPEN_DAY_SLIDER_IMAGE_DIR.$oldImage);
            }
        } else {
            $image = $oldImage;
        }

        return $image;
    }

}