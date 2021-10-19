<?php

namespace Module\Olympiad;

require_once dirname(__FILE__) . '/olympiad_list.php';

class MainList extends \LocalObjectList
{
    protected $olympiadList;

    public function __construct()
    {
        parent::LocalObjectList();
        $this->olympiadList = new OlympiadList();
    }

    public function loadForFilter(\LocalObject $request, $itemsOnPage = 8){
        $where = array();
        $join = array();

        //print_r($request);
        if ($request->IsPropertySet('OlympiadFilter')) {
            $filter = $request->GetProperty('OlympiadFilter');

            //main olympiad
            if (!empty($filter['MainID'])){
                $where[] = "olymp.MainID = " . intval($filter['MainID']);
            }

            //class number
            if (!empty($filter['ClassNumber'])){
                $join[] = "LEFT JOIN olympiad_olympiad2class AS olymp2class ON olymp.OlympiadID = olymp2class.OlympiadID";
                $where[] = "olymp2class.ClassID IN (" . implode(', ', $filter['ClassNumber']) . ")";
            }

            //subjects
            if (!empty($filter['Subject'])){
                $where[] = "olymp.SubjectID IN (" . implode(', ', $filter['Subject']) . ")";
            }

            if (!empty($filter['Region'])){
                $join[] = "LEFT JOIN olympiad_olympiad2region AS olymp2reg ON olymp.OlympiadID = olymp2reg.OlympiadID";
                $where[] = "olymp2reg.RegionID IN (" . implode(', ', $filter['Region']) . ")";
            }

            //level
            if (!empty($filter['Level'])){
                $where[] = "olymp.Level IN (" . implode(', ', $filter['Level']) . ")";
            }

            //Upcoming
            if (!empty($filter['Upcoming'])){
                $where[] = "olymp.RegistrationTo >= " . \Connection::GetSQLDateTime(GetCurrentDateTime());
            }

            //Exception olympiad
            if (!empty($filter['ExceptionOlympiadID'])){
                $where[] = "olymp.OlympiadID NOT IN (" . implode(', ', $filter['ExceptionOlympiadID']) . ")";
            }
        }

        $query = "SELECT main.*, GROUP_CONCAT(olymp.OlympiadID) AS OlympiadIDs
			FROM olympiad_olympiad AS olymp
            LEFT JOIN olympiad_main AS main ON olymp.MainID = main.MainID
            ".(!empty($join) ? implode(" \n ", $join) : '')."
            ". (!empty($where) ? ' WHERE '.implode(' AND ', $where) : '') ." 
            GROUP BY olymp.MainID";

        //print_r($query);
        //echo '<br>';
        //exit();


        $this->SetPageParam('MainPager');
        $this->SetItemsOnPage($itemsOnPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);

        $this->prepareOlympiadList();

        //print_r($this);
    }

    public function prepareOlympiadList(){
        foreach ($this->_items as $index => $item) {
            $request = new \LocalObject([
                'OlympiadFilter' => [
                    'OlympiadIDs' => $item['OlympiadIDs']
                ]
            ]);

            $this->olympiadList->load($request, 0);
            $this->_items[$index]['OlympiadList'] = $this->olympiadList->GetItems();
        }
    }
}