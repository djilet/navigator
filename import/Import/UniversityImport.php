<?php

namespace Import;

class UniversityImport extends BaseImport
{
    protected $map = [
        'ИдВуза'                 => 0,
        'Аббревиатура'           => 3,
        'Название'               => 4,
        'Область'                => 1,
        'Регион'                 => 2,
        'Направление'            => 6,
        'ОфСайт'                 => 7,
        'Адрес'                  => 8,
        'ТипВуза'                => 5,
        'ТелПриемнойКомиссии'    => 9,
        'Лицензия'               => 10,
        'ОВузеВЦифрах'           => 11,
        'ФормаОбучения'          => 12,
        'МеждународныеПрограммы' => 13,
        'ДвойнойДиплом'          => 14,
        'ВоеннаяКафедра'         => 15,
        'ОтсрочкаОтАрмии'        => 16,
        'ВнеучебнаяДеятельность' => 17,
        'Общежитие'              => 18,
        'СтоимостьБюджет'        => 19,
        'СтоимостьКонтракт'      => 20,
        'Стипендия'              => 21,
        'СтипендияЗаУспехи'      => 22,
        'СтипенцияСоцЛьготы'     => 23,
        'ИзвестныеВыпускники'    => 24,
        'Phone' => 25,
        'VideoURL' => 26,
        'VkUrl'=> 27,
        'InstagramUrl'=> 28,
        'YoutubeUrl'=> 29,
        'ScoreExamBudgetAvg'=> 30,
        'ScoreExamContractAvg'=> 31,
    ];

    private $importId_univerId;
    private $oldUniversityIdByTitle;

    /** @var \Import\Tools\Region  Регион */
    private $region;

    /** @var \Import\Tools\Type  Направление вуза */
    private $type;
    
    private $imageDir; 

    public function __construct()
    {
        parent::__construct();

        $this->imageDir = PROJECT_DIR.'import/source/univers/';
        $this->region = new Tools\Region($this->stmt);
        $this->type = new Tools\Type($this->stmt);

        $this->importId_univerId = $this->stmt->FetchIndexedAssocList(
            "SELECT `ImportID`, `UniversityID`, StaticPath FROM `data_university` WHERE `ImportID`<>''",
            'ImportID'
        );

        $this->oldUniversityIdByTitle = $this->stmt->FetchIndexedAssocList("SELECT * FROM `data_university`",'Title');
    }

    public function findUniversityByImportID($importId)
    {
        if (empty($importId)) {
            return 0;
        }

        if (!isset($this->importId_univerId[$importId])) {
            return 0;
        }

        return $this->importId_univerId[$importId]['UniversityID'];
    }

    public function findUniversityByTitle($title, $shortTitle)
    {
        if (isset($this->oldUniversityIdByTitle[$title])){
            return intval($this->oldUniversityIdByTitle[$title]['UniversityID']);
        }
        return 0;
    }

    public function insert()
    {
    	$regionId = $this->region->getId(
            $this->value('Регион'),
            $this->value('Область')
        );

        $typeId = $this->type->getId($this->value('ТипВуза'));

        /** @var \Import\Tools\CoorPoint $location */
        $location = Tools\Location::getCoordinateByAddress($this->value('Адрес'));
        
        $query = "INSERT INTO `data_university` SET
                                  `ImportID` = " . $this->field('ИдВуза') . ",
                                `ShortTitle` = " . $this->field('Аббревиатура') . ",
                                     `Title` = " . $this->field('Название') . ",
                                `StaticPath` = " . \Connection::GetSQLString(RuToStaticPath($this->field('Аббревиатура'))) . ", 
                                  `RegionID` = " . intval($regionId) . ",
                                    `TypeID` = " . intval($typeId) . ",
                                   `Address` = " . $this->field('Адрес') . ",
                                  `Latitude` = " . \Connection::GetSQLString($location->latitude) . ",
                                 `Longitude` = " . \Connection::GetSQLString($location->longitude) . ",
                                `UniverType` = " . $this->field('ТипВуза') . ",
                            `UniversityLogo` = NULL,
                      `UniversityLogoConfig` = NULL,
                   `PhoneSelectionCommittee` = " . $this->field('ТелПриемнойКомиссии') . ",
                                   `License` = " . $this->field('Лицензия') . ",
                                   `Content` = " . $this->field('ОВузеВЦифрах') . ",
                              `FormTraining` = " . $this->field('ФормаОбучения') . ",
                     `InternationalPrograms` = " . $this->field('МеждународныеПрограммы') . ",
                             `DoubleDiploma` = " . $this->field('ДвойнойДиплом') . ",
                        `MilitaryDepartment` = " . $this->field('ВоеннаяКафедра') . ",
                                 `DelayArmy` = " . $this->field('ОтсрочкаОтАрмии') . ",
                                    `Hostel` = " . $this->field('Общежитие') . ",
                 `ExtracurricularActivities` = " . $this->field('ВнеучебнаяДеятельность') . ",
                         `HostelPriceBudget` = " . $this->field('СтоимостьБюджет') . ",
                       `HostelPriceContract` = " . $this->field('СтоимостьКонтракт') . ",
                               `Scholarship` = " . $this->field('Стипендия') . ",
                `ScholarshipSpecialAcademic` = " . $this->field('СтипендияЗаУспехи') . ",
                         `ScholarshipSocial` = " . $this->field('СтипенцияСоцЛьготы') . ",
                           `FamousGraduates` = " . $this->field('ИзвестныеВыпускники') . ",
                           `Phone` = " . $this->field('Phone') . ",
                           `VideoURL` = " . $this->field('VideoURL') . ",
                           `VkUrl` = " . $this->field('VkUrl') . ",
                           `InstagramUrl` = " . $this->field('InstagramUrl') . ",
                           `YoutubeUrl` = " . $this->field('YoutubeUrl') . ",
                           `ScoreExamBudgetAvg` = " . $this->field('ScoreExamBudgetAvg') . ",
                           `ScoreExamContractAvg` = " . $this->field('ScoreExamContractAvg') . ",
                                   `Website` = " . $this->field('ОфСайт');
        if ($this->stmt->Execute($query)) {
            $id = $this->stmt->GetLastInsertID();
            $this->saveImage($id, $this->value('ИдВуза'));
            return $id;
        }

        return null;
    }

    public function update($id)
    {
        if (!empty($this->importId_univerId[$this->value('ИдВуза')]['StaticPath'])){
            $staticPath = $this->importId_univerId[$this->value('ИдВуза')]['StaticPath'];
        }
        elseif (!empty($this->oldUniversityIdByTitle[$this->value('Название')])){
            $staticPath = $this->oldUniversityIdByTitle[$this->value('Название')]['StaticPath'];
        }
        else{
            $staticPath = RuToStaticPath($this->field('Аббревиатура'));
        }

    	$regionId = $this->region->getId(
            $this->value('Регион'),
            $this->value('Область')
        );
        $typeId = $this->type->getId($this->value('ТипВуза'));

        /** @var \Import\Tools\CoorPoint $location */
        $location = Tools\Location::getCoordinateByAddress($this->value('Адрес'));

        $query = "UPDATE `data_university` SET
                                  `ImportID` = " . $this->field('ИдВуза') . ",
                                `ShortTitle` = " . $this->field('Аббревиатура') . ",
                                     `Title` = " . $this->field('Название') . ",
                                `StaticPath` = " . \Connection::GetSQLString($staticPath) . ",                                 
                                  `RegionID` = " . intval($regionId) . ",
                                    `TypeID` = " . intval($typeId) . ",
                                   `Address` = " . $this->field('Адрес') . ",
                                  `Latitude` = " . \Connection::GetSQLString($location->latitude) . ",
                                 `Longitude` = " . \Connection::GetSQLString($location->longitude) . ",
                                `UniverType` = " . $this->field('ТипВуза') . ",
                   `PhoneSelectionCommittee` = " . $this->field('ТелПриемнойКомиссии') . ",
                                   `License` = " . $this->field('Лицензия') . ",
                                   `Content` = " . $this->field('ОВузеВЦифрах') . ",
                              `FormTraining` = " . $this->field('ФормаОбучения') . ",
                     `InternationalPrograms` = " . $this->field('МеждународныеПрограммы') . ",
                             `DoubleDiploma` = " . $this->field('ДвойнойДиплом') . ",
                        `MilitaryDepartment` = " . $this->field('ВоеннаяКафедра') . ",
                                 `DelayArmy` = " . $this->field('ОтсрочкаОтАрмии') . ",
                                    `Hostel` = " . $this->field('Общежитие') . ",
                 `ExtracurricularActivities` = " . $this->field('ВнеучебнаяДеятельность') . ",
                         `HostelPriceBudget` = " . $this->field('СтоимостьБюджет') . ",
                       `HostelPriceContract` = " . $this->field('СтоимостьКонтракт') . ",
                               `Scholarship` = " . $this->field('Стипендия') . ",
                `ScholarshipSpecialAcademic` = " . $this->field('СтипендияЗаУспехи') . ",
                         `ScholarshipSocial` = " . $this->field('СтипенцияСоцЛьготы') . ",
                           `FamousGraduates` = " . $this->field('ИзвестныеВыпускники', [$this, 'prepareFamousGraduates']) . ",
                           `Phone` = " . $this->field('Phone') . ",
                           `VideoURL` = " . $this->field('VideoURL') . ",
                           `VkUrl` = " . $this->field('VkUrl') . ",
                           `InstagramUrl` = " . $this->field('InstagramUrl') . ",
                           `YoutubeUrl` = " . $this->field('YoutubeUrl') . ",
                           `ScoreExamBudgetAvg` = " . $this->field('ScoreExamBudgetAvg') . ",
                           `ScoreExamContractAvg` = " . $this->field('ScoreExamContractAvg') . ",
                                   `Website` = " . $this->field('ОфСайт') . "
            WHERE UniversityID=" . intval($id);
        $this->stmt->Execute($query);
        $this->saveImage($id, $this->value('ИдВуза'));
    }

    public function prepareFamousGraduates($str)
    {
        return preg_replace('/\*(.*?)<\/li>/ui', '<span class="position">$1</span></li>', $str);
    }

    private function saveImage($id, $value)
    {
        $rows = $this->stmt->FetchList('SELECT * FROM `data_university_image` WHERE UniversityID='.intval($id));
        
        $i = 0;
        while (file_exists($this->imageDir.$value.'_'.(++$i).'.jpg')) {
            $filepath =  $this->imageDir.$value.'_'.$i.'.jpg';
            
            if (isset($rows[$i-1])) {
                copy($filepath, DATA_UNIVERSITY_IMAGE_DIR.$rows[$i-1]['ItemImage']);
            } else {
                $fileSys = new \FileSys();
                
                $filename = $fileSys->GenerateUniqueName(DATA_UNIVERSITY_IMAGE_DIR, 'jpg');
                $fileSys->Move($filepath, DATA_UNIVERSITY_IMAGE_DIR.$filename);
                
                $this->stmt->Execute('INSERT INTO `data_university_image` SET 
                    `ItemImage`='.\Connection::GetSQLString($filename).',
                    `UniversityID`='.intval($id).',
                    `SortOrder`='.$i);
            }
        }
        
        if ($i < count($rows)) {
            while (isset($rows[$i-1])) {
                
                if (file_exists(DATA_UNIVERSITY_IMAGE_DIR.$rows[$i-1]['ItemImage'])) {
                    @unlink(DATA_UNIVERSITY_IMAGE_DIR.$rows[$i-1]['ItemImage']);
                }
                
                $this->stmt->Execute('DELETE FROM `data_university_image` WHERE ImageID='.$rows[$i-1]['ImageID']);
                
                ++$i;
            }
        }
    }

    public function uniqStaticPath(){
        if ($result = $this->stmt->FetchList("SELECT GROUP_CONCAT(UniversityID) AS UniversityIDs, StaticPath, COUNT(StaticPath) FROM `data_university` AS u GROUP BY StaticPath HAVING COUNT(StaticPath) > 1")){
            foreach ($result as $key => $item) {
                $universityIDs = explode(',', $item['UniversityIDs']);
                foreach ($universityIDs as $index => $univerID) {
                    if ($index > 0){
                        $staticPath = $item['StaticPath'] . '-' . $index;
                        $query = "UPDATE data_university
							  SET StaticPath = " . \Connection::GetSQLString($staticPath)
                            . " WHERE UniversityID = " . intval($univerID);
                        if (!$this->stmt->Execute($query)){
                            echo $query;
                            return false;
                        }
                    }

                }
            }
        }
    }
}
