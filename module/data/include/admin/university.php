<?php
require_once(dirname(__FILE__) . "/../../init.php");
require_once(dirname(__FILE__) . "/../university_image_list.php");
es_include("filesys.php");
es_include("localobject.php");

class DataUniversity extends LocalObject
{
    private $module;
    private $params;
    public $itemImageParams;
    public $categories;

    public function __construct($module = 'data', $data = array())
    {
        parent::LocalObject($data);
        $this->module = $module;
        $this->params = LoadImageConfig('UniversityLogo', $this->module, DATA_UNIVERSITY_IMAGE);
        $this->itemImageParams = LoadImageConfig('ItemImage', $module, UniversityImageList::ITEM_IMAGE_CONFIG);
    }

    public static function convertFamousGraduatesToString(array $items){
        $str = '<ul>';
        foreach ($items as $item){
            $str .= "<li>{$item['Name']}<span class='position'>{$item['Description']}</span></li>";
        }

        return $str . '</ul>';
    }

    public function LoadByID($id)
    {
        $query = "SELECT u.*
            FROM `data_university` AS u                        
            WHERE u.UniversityID=" . Connection::GetSQLString($id);
        $this->LoadFromSQL($query);

        if ($this->GetProperty("UniversityID")) {
            
            $image = $this->GetProperty('UniversityLogo');
            if (! empty($image)) {
                foreach ($this->params as $param) {
                    $this->SetProperty($param['Name'].'Path', $param['Path'].'univer/'.$image);
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function getCategoryIds(): array
    {
        if (!$this->categories){
            $query = QueryBuilder::init()
                ->select(['UniversityCategoryID'])
                ->from('data_university2university_category');
            
	        if($this->GetProperty('UniversityID'))
	        {
	            $query->where(['UniversityID' => $this->GetProperty('UniversityID')]);
	        }

            $result = GetStatement()->FetchRows($query->getSQL());

            $this->categories = $result;
        }

        return $this->categories;
    }

    public function Save()
    {
        $result1 = $this->Validate();
        if (!$result1) {
            return false;
        }

        $this->saveItemImage($this->GetProperty("SavedUniversityLogo"));
        $this->SetProperty("UniversityLogoConfig", json_encode($this->GetProperty("UniversityLogoConfig")));

        $stmt = GetStatement();

        $staticPath = $this->GetProperty('StaticPath');
        if (empty($staticPath)){
            $staticPath = RuToStaticPath($this->GetProperty("ShortTitle"));
        }

        $famousGraduates = $this->GetProperty('FamousGraduates');
        if (is_array($famousGraduates)){
            $famousGraduates = self::convertFamousGraduatesToString($famousGraduates);
        }

        if ($this->GetIntProperty('CityID') < 1){
            $this->SetProperty('CityID', null);
        }

        if ($this->GetIntProperty("UniversityID") > 0) {
            $query = "UPDATE `data_university` SET
                        ShortTitle=" . $this->GetPropertyForSQL("ShortTitle") . ", 
                        Title=" . $this->GetPropertyForSQL("Title") . ", 
                        TitleInPrepositionalCase=" . $this->GetPropertyForSQL("TitleInPrepositionalCase") . ", 
                        StaticPath=" . Connection::GetSQLString($staticPath) . ", 
                        RegionID=" . $this->GetPropertyForSQL("RegionID") . ", 
                        CityID=" . $this->GetPropertyForSQL("CityID") . ", 
                        TypeID=" . $this->GetPropertyForSQL("TypeID") . ",
                        Phone=" . $this->GetPropertyForSQL("Phone") . ",
                        Address=" . $this->GetPropertyForSQL("Address") . ",
                        Latitude=" . $this->GetPropertyForSQL("Latitude") . ",
                        Longitude=" . $this->GetPropertyForSQL("Longitude") . ",
                        UniverType=" . $this->GetPropertyForSQL("UniverType") . ",
                        UniversityLogo=" . $this->GetPropertyForSQL("UniversityLogo") . ",
                        UniversityLogoConfig=" . $this->GetPropertyForSQL("UniversityLogoConfig").",
                        `PhoneSelectionCommittee` = " . $this->GetPropertyForSQL("PhoneSelectionCommittee").",
                        `License` = " . $this->GetPropertyForSQL("License").",
                        `Content` = " . $this->GetPropertyForSQL("Content").",
                        `WhyChoose` = " . $this->GetPropertyForSQL("WhyChoose").",
                        `Description` = " . $this->GetPropertyForSQL("Description").",
                        `FormTraining` = " . $this->GetPropertyForSQL("FormTraining").",
                        `InternationalPrograms` = " . $this->GetPropertyForSQL("InternationalPrograms").",
                        `DoubleDiploma` = " . $this->GetPropertyForSQL("DoubleDiploma").",
                        `MilitaryDepartment` = " . $this->GetPropertyForSQL("MilitaryDepartment").",
                        `DelayArmy` = " . $this->GetPropertyForSQL("DelayArmy").",
                        `Hostel` = " . $this->GetPropertyForSQL("Hostel").",
                        `ExtracurricularActivities` = " . $this->GetPropertyForSQL("ExtracurricularActivities").",
                        `HostelPriceBudget` = " . $this->GetPropertyForSQL("HostelPriceBudget").",
                        `HostelPriceContract` = " . $this->GetPropertyForSQL("HostelPriceContract").",
                        `Scholarship` = " . $this->GetPropertyForSQL("Scholarship").",
                        `ScholarshipSpecialAcademic` = " . $this->GetPropertyForSQL("ScholarshipSpecialAcademic").",
                        `ScholarshipSocial` = " . $this->GetPropertyForSQL("ScholarshipSocial").",
                        `Website` = " . $this->GetPropertyForSQL("Website").",
                        `VkUrl` = " . $this->GetPropertyForSQL("VkUrl").",
                        `InstagramUrl` = " . $this->GetPropertyForSQL("InstagramUrl").",
                        `YoutubeUrl` = " . $this->GetPropertyForSQL("YoutubeUrl").",
                        `VideoURL` = " . $this->GetPropertyForSQL("VideoURL").",
                        `ScoreExamBudgetAvg` = " . $this->GetPropertyForSQL("ScoreExamBudgetAvg").",
                        `ScoreExamContractAvg` = " . $this->GetPropertyForSQL("ScoreExamContractAvg").",
                        `FamousGraduates` = " . Connection::GetSQLString($famousGraduates) .",
                        `QuestionUserID` = " . ($this->GetIntProperty("QuestionUserID") ? $this->GetPropertyForSQL("QuestionUserID") : "NULL").",
                        `QuestionUserTitle` = " . $this->GetPropertyForSQL("QuestionUserTitle").",
                        `Opened`=".$this->GetPropertyForSQL("Opened")."
                WHERE UniversityID=" . $this->GetIntProperty("UniversityID");
        } else {
            $query = "INSERT INTO `data_university` SET
                        ShortTitle=" . $this->GetPropertyForSQL("ShortTitle") . ", 
                        Title=" . $this->GetPropertyForSQL("Title") . ", 
                        TitleInPrepositionalCase=" . $this->GetPropertyForSQL("TitleInPrepositionalCase") . ", 
                        StaticPath=" . Connection::GetSQLString($staticPath) . ",                        
                        RegionID=" . $this->GetPropertyForSQL("RegionID") . ",
                        CityID=" . $this->GetPropertyForSQL("CityID") . ", 
                        TypeID=" . $this->GetPropertyForSQL("TypeID") . ",
                        Phone=" . $this->GetPropertyForSQL("Phone") . ",
                        Address=" . $this->GetPropertyForSQL("Address") . ",
                        Latitude=" . $this->GetPropertyForSQL("Latitude") . ",
                        Longitude=" . $this->GetPropertyForSQL("Longitude") . ",
                        UniverType=" . $this->GetPropertyForSQL("UniverType"). ",
                        UniversityLogo=" . $this->GetPropertyForSQL("UniversityLogo"). ",
                        UniversityLogoConfig=" . $this->GetPropertyForSQL("UniversityLogoConfig").",
                        `PhoneSelectionCommittee` = " . $this->GetPropertyForSQL("PhoneSelectionCommittee").",
                        `License` = " . $this->GetPropertyForSQL("License").",
                        `Content` = " . $this->GetPropertyForSQL("Content").",
                        `WhyChoose` = " . $this->GetPropertyForSQL("WhyChoose").",
                        `Description` = " . $this->GetPropertyForSQL("Description").",
                        `FormTraining` = " . $this->GetPropertyForSQL("FormTraining").",
                        `InternationalPrograms` = " . $this->GetPropertyForSQL("InternationalPrograms").",
                        `DoubleDiploma` = " . $this->GetPropertyForSQL("DoubleDiploma").",
                        `MilitaryDepartment` = " . $this->GetPropertyForSQL("MilitaryDepartment").",
                        `DelayArmy` = " . $this->GetPropertyForSQL("DelayArmy").",
                        `Hostel` = " . $this->GetPropertyForSQL("Hostel").",
                        `ExtracurricularActivities` = " . $this->GetPropertyForSQL("ExtracurricularActivities").",
                        `HostelPriceBudget` = " . $this->GetPropertyForSQL("HostelPriceBudget").",
                        `HostelPriceContract` = " . $this->GetPropertyForSQL("HostelPriceContract").",
                        `Scholarship` = " . $this->GetPropertyForSQL("Scholarship").",
                        `ScholarshipSpecialAcademic` = " . $this->GetPropertyForSQL("ScholarshipSpecialAcademic").",
                        `ScholarshipSocial` = " . $this->GetPropertyForSQL("ScholarshipSocial").",
                        `Website` = " . $this->GetPropertyForSQL("Website").",
                        `VkUrl` = " . $this->GetPropertyForSQL("VkUrl").",
                        `InstagramUrl` = " . $this->GetPropertyForSQL("InstagramUrl").",
                        `YoutubeUrl` = " . $this->GetPropertyForSQL("YoutubeUrl").",
                        `VideoURL` = " . $this->GetPropertyForSQL("VideoURL").",
                        `ScoreExamBudgetAvg` = " . $this->GetPropertyForSQL("ScoreExamBudgetAvg").",
                        `ScoreExamContractAvg` = " . $this->GetPropertyForSQL("ScoreExamContractAvg").",
                        `FamousGraduates` = " . Connection::GetSQLString($famousGraduates) .",
                        `QuestionUserID` = " . ($this->GetIntProperty("QuestionUserID") ? $this->GetPropertyForSQL("QuestionUserID") : "NULL").",
                        `QuestionUserTitle` = " . $this->GetPropertyForSQL("QuestionUserTitle").",
                        `Opened`=".$this->GetPropertyForSQL("Opened");
        }

        if ($stmt->Execute($query)) {
            if (!$this->GetIntProperty("UniversityID") > 0) {
                $this->SetProperty("UniversityID", $stmt->GetLastInsertID());
            }

            return true;
        } else {
            $this->AddError("sql-error");

            return false;
        }
    }

    public function saveCategories(array $categoryIds = null): bool
    {
        $universityId = $this->GetProperty('UniversityID');

        $query = QueryBuilder::init()
            ->delete()
            ->from('data_university2university_category')
            ->where(["UniversityID = {$universityId}"]);

        if (!GetStatement()->Execute($query->getSQL())){
            $this->AddError("sql-error");
            return false;
        }

        if(!empty($categoryIds)){
            foreach ($categoryIds as $categoryId){
                $query = QueryBuilder::init()
                    ->insert('data_university2university_category')
                    ->setValue('UniversityID', $universityId)
                    ->setValue('UniversityCategoryID', $categoryId);

                if (!GetStatement()->Execute($query->getSQL())){
                    $this->AddError("sql-error");
                    return false;
                }
            }

            return true;
        }

        return true;
    }

    public function Validate()
    {
        if (!$this->ValidateNotEmpty("ShortTitle")) {
            $this->AddError("university-shorttitle-empty", $this->module);
        }

        if (!$this->ValidateNotEmpty("Title")) {
            $this->AddError("university-title-empty", $this->module);
        }
        
        if ($this->GetProperty("Opened") != "Y"){
            $this->SetProperty("Opened", "N");
        }

        if ($this->IsPropertySet("StaticPath")){
            $staticPath = $this->GetProperty('StaticPath');
            $id = $this->GetIntProperty("UniversityID");
            $findId = University::getIDByStaticPath($staticPath);
            if (!empty($staticPath)){
                if ($findId && $findId != $id){
                    $this->AddError("static-path-already-taken", $this->module);
                }
            }
        }
        
        return !$this->HasErrors();
    }

    public function saveItemImage($savedImage = "")
    {
        $fileSys = new FileSys();

        $newItemImage = $fileSys->Upload(
            "UniversityLogo",
            DATA_UNIVERSITY_IMAGE_DIR
        );
        
        if ($newItemImage) {
            $this->SetProperty("UniversityLogo", $newItemImage["FileName"]);

            // Remove old image if it has different name
            if ($savedImage && $savedImage != $newItemImage["FileName"]) {
                if (file_exists(DATA_UNIVERSITY_IMAGE_DIR . $savedImage) and
                    is_file(DATA_UNIVERSITY_IMAGE_DIR . $savedImage)) {
                    @unlink(DATA_UNIVERSITY_IMAGE_DIR . $savedImage);
                }
            }
        } else {
            if ($savedImage) {
                $this->SetProperty("UniversityLogo", $savedImage);
            } else {
                $this->SetProperty("UniversityLogo", null);
            }
        }

        $this->_properties["UniversityLogoConfig"]["Width"] = 0;
        $this->_properties["UniversityLogoConfig"]["Height"] = 0;

        if ($this->GetProperty('UniversityLogo')) {
            if ($info = @getimagesize(DATA_UNIVERSITY_IMAGE_DIR . $this->GetProperty('UniversityLogo'))) {
                $this->_properties["UniversityLogoConfig"]["Width"] = $info[0];
                $this->_properties["UniversityLogoConfig"]["Height"] = $info[1];
            }
        }

        $this->LoadErrorsFromObject($fileSys);

        return !$fileSys->HasErrors();
    }

    public function getImageParams()
    {
        $paramList = array();
        foreach ($this->params as $param) {
            $paramList[] = array(
                "Name" => $param['Name'],
                "SourceName" => $param['SourceName'],
                "Width" => $param['Width'],
                "Height" => $param['Height'],
                "Resize" => $param['Resize'],
                "X1" => $this->GetIntProperty("ItemImage".$param['SourceName']."X1"),
                "Y1" => $this->GetIntProperty("ItemImage".$param['SourceName']."Y1"),
                "X2" => $this->GetIntProperty("ItemImage".$param['SourceName']."X2"),
                "Y2" => $this->GetIntProperty("ItemImage".$param['SourceName']."Y2")
            );
        }
        return $paramList;
    }

    public function prepareForTemplate(){
        if (!empty($this->GetProperty('FamousGraduates'))){
            if (is_array($this->GetProperty('FamousGraduates'))){
                $this->SetProperty('FamousGraduatesList', array_values($this->GetProperty('FamousGraduates')));
                $this->SetProperty('FamousGraduates', self::convertFamousGraduatesToString($this->GetProperty('FamousGraduatesList')));
            }
            else{
                preg_match_all('/<li>(.*)<\/li>/U', $this->GetProperty('FamousGraduates'), $matches);
                if (!empty($matches[1])){
                    $items = [];
                    foreach ($matches[1] as $key => $match){
                        preg_match('/<span.*>(.*)<\/span>/', $match, $description);
                        if (isset($description[1])){
                            $items[$key]['Name'] = str_replace($description[0], '', $match);
                            $items[$key]['Description'] = $description[1];
                        }
                    }

                    $this->SetProperty('FamousGraduatesList', $items);
                }
            }
        }
    }

}

