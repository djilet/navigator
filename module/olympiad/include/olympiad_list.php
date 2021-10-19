<?php

namespace Module\Olympiad;


class OlympiadList extends \LocalObjectList
{
    protected $profile;
    protected $classNumber;
    protected $region;

    public function __construct()
    {
        parent::LocalObjectList();
    }

    public function load(\LocalObject $request, $itemsOnPage = 10){
        $select = array('olymp.*', 'olymp2class.ClassIDs', 'olymp2reg.RegionIDs', 'olymp2uni.UniversityIDs');
        $where = array();
        $join = array();


        if ($request->IsPropertySet('OlympiadFilter')) {
            $filter = $request->GetProperty('OlympiadFilter');

            //main olympiad
            if (!empty($filter['MainID'])){
                $where[] = "olymp.MainID = " . intval($filter['MainID']);
            }

            if (!empty($filter['OlympiadIDs'])){
                $where[] = "olymp.OlympiadID IN (" . $filter['OlympiadIDs'] . ")";
            }

            if (!empty($filter['ExcludeIDs'])){
                $where[] = "olymp.OlympiadID NOT IN (" . implode(',', $filter['ExcludeIDs']) . ")";
            }
        }

        //class
        $join[] = "LEFT JOIN (
            SELECT OlympiadID, GROUP_CONCAT(ClassID) AS ClassIDs FROM olympiad_olympiad2class GROUP BY OlympiadID
        ) AS olymp2class ON olymp.OlympiadID = olymp2class.OlympiadID ";

        //region
        $join[] = "LEFT JOIN (
            SELECT OlympiadID, GROUP_CONCAT(RegionID) AS RegionIDs FROM olympiad_olympiad2region GROUP BY OlympiadID
        ) AS olymp2reg ON olymp.OlympiadID = olymp2reg.OlympiadID ";

        //university
        $join[] = "LEFT JOIN (
            SELECT OlympiadID, GROUP_CONCAT(UniversityID) AS UniversityIDs FROM olympiad_olympiad2university GROUP BY OlympiadID
        ) AS olymp2uni ON olymp.OlympiadID = olymp2uni.OlympiadID ";


        $query = "SELECT " . implode(', ', $select) . "
			FROM olympiad_olympiad AS olymp
            ".(!empty($join) ? implode(" \n ", $join) : '')."
            ". (!empty($where) ? ' WHERE '.implode(' AND ', $where) : '') ." 
            ORDER BY olymp.Name
            ";

        //print_r($query);
        //echo '<br>';
        //exit();


        $this->SetPageParam('OlympiadPager');
        $this->SetItemsOnPage($itemsOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);

        $this->prepareForTemplate();
    }

    public function prepareForTemplate(){
        $olympiad = new Olympiad([
            Olympiad::PREPARE_DATE, Olympiad::PREPARE_REGION, Olympiad::PREPARE_CLASS_NUMBER
        ]);

        foreach ($this->_items as $index => $item) {
            $olympiad->LoadFromArray($item);
            $olympiad->prepareForTemplate();
            $this->_items[$index] = $olympiad->GetProperties();
        }

        //print_r($this->_items);
        //exit();
    }

    public static function getLevelList(){
        $query = "SELECT DISTINCT Level FROM olympiad_olympiad";
        return GetStatement()->FetchList($query);
    }
}