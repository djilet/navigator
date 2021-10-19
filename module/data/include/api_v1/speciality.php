<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobject.php");

class DataSpeciality extends LocalObject
{
	var $module;

	public function DataSpeciality($module, $data = array())
	{
		parent::LocalObject($data);
		$this->module = $module;
	}

	public function LoadByID($specialityID)
	{
		$stmt = GetStatement();

		$query = "SELECT sp.SpecialityID, sp.UniversityID, sp.Title, 
				sp.Additional1, sp.Additional2, sp.Additional3, sp.Additional4, sp.Additional5, sp.Additional6, sp.Additional7, sp.Additional8, sp.Additional9, sp.Additional10,
				sp.Period, sp.BudgetNext,
				sp.Price, sp.Link, u.DelayArmy as `Delay`, u.Hostel, sp.Budget,
                sp.Students, sp.Score2016 AS Score,
				u.ShortTitle as UniversityShortTitle, u.Title as UniversityTitle,
				d.Title AS DirectionTitle, u2u.SpecialityID AS IsEnrollee
            FROM `data_speciality` AS sp
            INNER JOIN data_university u ON sp.UniversityID=u.UniversityID
            LEFT JOIN `data_direction` AS d ON sp.DirectionID=d.DirectionID
            LEFT JOIN `data_user_university` AS u2u ON u2u.SpecialityID=sp.SpecialityID
            WHERE sp.SpecialityID=" . $specialityID;
        if ($row = $stmt->FetchRow($query)) {

            $row["SubjectsList"] = $stmt->FetchList("SELECT s.Title, e.Score, e.isProfile FROM data_ege e
                LEFT JOIN data_subject s ON e.SubjectID=s.SubjectID
                WHERE e.SpecialityID=" . intval($row['SpecialityID']));

            $additionalList = array();
            for ($i = 1; $i <= 10; $i++) {
                if (!empty($row["Additional" . $i])) {
                    $additionalList[] = array("Title" => $row["Additional" . $i]);
                }
                unset($row["Additional" . $i]);
            }
            $row["AdditionalList"] = $additionalList;

            if (empty($row['Score'])) {
                $budget = explode('#', $row['Budget']);
                $row['Score'] = $budget[0];
            }
            unset($row['Budget']);

            if (empty($row['Students'])) {
                $row['Students'] = $row['BudgetNext'];
            }
            unset($row['BudgetNext']);

            $row['Price'] = preg_replace('/\D+/', '', $row['Price']);

            $this->_properties = $row;
        }
	}

}
