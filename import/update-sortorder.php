<?php
require_once(dirname(__FILE__)."/../include/init.php");
es_include("logger.php");

$stmt = GetStatement();

$query = "SELECT u.UniversityID FROM data_university u";
$list = $stmt->FetchList($query);

for($i=0; $i<count($list); $i++)
{
	$query = "SELECT s.SpecialityID, stud.BudgetScopeWave1, stud.BudgetScopeWave2, s.Additional1, s.Additional2, s.Additional3, s.Additional4, s.Additional5, s.Additional6, s.Additional7, s.Additional8, s.Additional9, s.Additional10, GROUP_CONCAT(e.EgeID) AS Ege 
		FROM data_speciality s
		LEFT JOIN data_ege e ON s.SpecialityID=e.SpecialityID 
		LEFT JOIN (
					SELECT s.SpecialityID, s.Year, s.BudgetScopeWave1, s.BudgetScopeWave2, s.PaidScope, s.PaidPrice
					FROM data_speciality_study AS s
                    LEFT JOIN (SELECT SpecialityID, MAX(Year) AS Year FROM data_speciality_study GROUP BY SpecialityID) AS sy ON s.SpecialityID = sy.SpecialityID
					WHERE s.Type = 'Full' AND s.Year = sy.Year
					GROUP BY s.SpecialityID
				) AS stud ON s.SpecialityID = stud.SpecialityID
		WHERE s.UniversityID=".intval($list[$i]["UniversityID"])."
		GROUP BY s.SpecialityID";

	$budjetList = $stmt->FetchList($query);
	$count1 = 0;
	$count1count = 0;
	$count2 = 0;
	$count2count = 0;
	for($j=0; $j<count($budjetList); $j++)
	{
		$subjectCount = $budjetList[$j]["Ege"] ? count(explode(',', $budjetList[$j]["Ege"])) : 0;
		if($budjetList[$j]["Additional1"]) $subjectCount++;
		if($budjetList[$j]["Additional2"]) $subjectCount++;
		if($budjetList[$j]["Additional3"]) $subjectCount++;
		if($budjetList[$j]["Additional4"]) $subjectCount++;
		if($budjetList[$j]["Additional5"]) $subjectCount++;
		if($budjetList[$j]["Additional6"]) $subjectCount++;
		if($budjetList[$j]["Additional7"]) $subjectCount++;
		if($budjetList[$j]["Additional8"]) $subjectCount++;
		if($budjetList[$j]["Additional9"]) $subjectCount++;
		if($budjetList[$j]["Additional10"]) $subjectCount++;

		$budget1 = $budjetList[$j]["BudgetScopeWave1"];
		$budget2 = $budjetList[$j]["BudgetScopeWave2"];

		if(intval($budget1) > 0)
		{
			$count1 += ($budget1 / $subjectCount);
			$count1count += 1;	
		}
		if(intval($budget2) > 0)
		{
			$count2 += ($budget2 / $subjectCount);
			$count2count += 1;	
		}
		
		$specialitySortOrder = 0;
		if($subjectCount > 0){
		    if(intval($budget1) > 0){
		        $specialitySortOrder = $budget1 / $subjectCount;
		    }
		    else if(intval($budget2) > 0){
		        $specialitySortOrder = $budget2 / $subjectCount;
		    }
		}
		$stmt->Execute("UPDATE data_speciality SET SortOrder=".$specialitySortOrder." WHERE SpecialityID=".intval($budjetList[$j]["SpecialityID"]));
	}
	$sortOrder = 0;
	if($count1count > 0)
	{
		$sortOrder = intval($count1 / $count1count);
	}
	else if($count2count > 0)
	{
		$sortOrder = intval($count2 / $count2count);
	}
	$stmt->Execute("UPDATE data_university SET SortOrder=".$sortOrder." WHERE UniversityID=".intval($list[$i]["UniversityID"]));
}

?>