<?php
es_include("image_manager.php");

class UniversityImageList extends LocalObjectList
{
    const ITEM_IMAGE_CONFIG = '296x152|8|Thumb,136x80|8|Preview,500x257|8|Admin';
    protected $module = 'data';

    public static function removeItemById($Id){
        $stmt = GetStatement();
        $query = "SELECT * FROM data_university_image WHERE ImageID = " . intval($Id);
        $image = $stmt->FetchRow($query);
        $imagePath = DATA_UNIVERSITY_IMAGE_DIR . $image['ItemImage'];
        if (ImageManager::RemoveImage($imagePath)){
            $stmt->Execute("DELETE FROM data_university_image WHERE ImageID = " . intval($Id));
        }
    }

    public static function getMaxSortOrder($universityID){
        $query = "SELECT MAX(SortOrder)
				FROM `data_university_image`
                WHERE UniversityID".($universityID > 0 ? "=".$universityID : " IS NULL");
        return GetStatement()->FetchField($query);
    }

    public function load($universityID){
        $this->LoadFromSQL('SELECT * FROM `data_university_image` 
                WHERE UniversityID='.intval($universityID).' ORDER BY `SortOrder` ASC');
    }

    public function getItemsByParams($params){
        $imagesList = [];
        foreach ($this->_items as $image) {
            $image = array(
                'ImageID' => $image['ImageID'],
                'ItemImage' => $image['ItemImage']
            );
            foreach ($params as $param) {
                $image[$param['Name'].'Path'] = $param['Path'].'univer/'.$image['ItemImage'];
            }
            $imagesList[] = $image;
        }

        return $imagesList;
    }

    public function save($universityID){
        $stmt = GetStatement();
        $fileSys = new FileSys();
        if (!$fileList = $fileSys->Upload("MediaFile", DATA_UNIVERSITY_IMAGE_DIR, false)){
            $this->LoadErrorsFromObject($fileSys);
            return false;
        }

        $sortOrder = self::getMaxSortOrder($universityID) + 1;
        foreach ($fileList as $index => $item) {
            if (isset($item["ErrorInfo"])){
                continue;
            }

            $file = $item["FileName"];

            if ($info = @getimagesize(DATA_UNIVERSITY_IMAGE_DIR.$file)) {
                $saved = array("Type" => "image", "MediaFile" => $file, "MediaFileConfig" => array("Width" => $info[0], "Height" => $info[1]));
            }
            else {
                $item["ErrorInfo"] = GetTranslation("filesys-getimagesize-error");
                @unlink(DATA_UNIVERSITY_IMAGE_DIR."media/".$file);
            }


            $query = "INSERT INTO data_university_image SET UniversityID = " . $universityID . ", "
            . "ItemImage = " . Connection::GetSQLString($file) . ", "
            . "SortOrder = " . $sortOrder;

            if ($stmt->Execute($query)) {
                $sortOrder++;
            }
            else {
                $fileList[$index]["ErrorInfo"] = GetTranslation("sql-error");
                @unlink(DATA_UNIVERSITY_IMAGE_DIR.$file);
            }
        }

        // Prepare message info
        $failed = 0;
        $saved = 0;
        for ($i = 0; $i < count($fileList); $i++)
        {
            if (isset($fileList[$i]["ErrorInfo"]) && $fileList[$i]["error"] != 4)
            {
                $this->AddError($fileList[$i]["ErrorInfo"], $this->module);
                $failed++;
            }
        }
        $saved = count($fileList) - $failed;
        if ($saved == 0)
        {
            $this->AddError("media-save-failed", $this->module, array("Saved" => $saved, "Failed" => $failed));
            return false;
        }
        else if ($failed > 0)
        {
            $this->AddMessage("media-save-partial", $this->module, array("Saved" => $saved, "Failed" => $failed));
            return true;
        }
        else
        {
            $this->AddMessage("media-save-complete", $this->module, array("Saved" => $saved));
            return true;
        }
    }

    public static function SetSortOrder($imageID, $diff)
    {
        /*@var stmt Statement */
        $stmt = GetStatement();

        $query = "SELECT SortOrder FROM `data_university_image` WHERE ImageID=".Connection::GetSQLString($imageID);

        $sortOrder = $stmt->FetchField($query);
        $sortOrder = $sortOrder + $diff;

        if ($sortOrder < 1) $sortOrder = 1;

        $imageID = intval($imageID);

        $itemID = intval($stmt->FetchField("SELECT UniversityID FROM `data_university_image` WHERE ImageID=".$imageID));

        $query = "SELECT COUNT(SortOrder) FROM `data_university_image`
			WHERE UniversityID=".$itemID;

        if ($maxSortOrder = $stmt->FetchField($query))
        {
            if ($sortOrder > $maxSortOrder) $sortOrder = $maxSortOrder;

            $query = "SELECT SortOrder FROM `data_university_image`
				WHERE ImageID=".$imageID;
            if ($currentSortOrder = $stmt->FetchField($query))
            {
                if ($sortOrder == $currentSortOrder)
                    return true;

                $query = "UPDATE `data_university_image`
					SET SortOrder=".$sortOrder."
					WHERE ImageID=".$imageID;
                $stmt->Execute($query);

                if ($sortOrder > $currentSortOrder)
                {
                    $query = "UPDATE `data_university_image` SET SortOrder=SortOrder-1
						WHERE SortOrder<=".$sortOrder." AND SortOrder>".$currentSortOrder."
							AND ImageID<>".$imageID;
                    $query .= " AND UniversityID=".$itemID;

                }
                else if ($sortOrder < $currentSortOrder)
                {
                    $query = "UPDATE `data_university_image` SET SortOrder=SortOrder+1
						WHERE SortOrder>=".$sortOrder." AND SortOrder<".$currentSortOrder."
							 AND ImageID<>".$imageID." AND UniversityID=".$itemID;
                }
                $stmt->Execute($query);

                return true;
            }
        }

        return false;
    }
}