<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$stmt = GetStatement();
$query = "DROP TABLE IF EXISTS `tmp_data_speciality_study`";
$stmt->Execute($query);

$query = "CREATE TABLE IF NOT EXISTS `tmp_data_speciality_study` (
      `SpecialityID` int NOT NULL,
      `BudgetNext` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `Budget` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetLast` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetMinNext` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetMin` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetMinLast` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetCountNext` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetCount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `BudgetCountLast` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidPriceNext` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidPrice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidPriceLast` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidScopeNext` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidScope` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
      `PaidScopeLast` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
    );";
$stmt->Execute($query);

$query = "ALTER TABLE `tmp_data_speciality_study` ADD UNIQUE KEY `SpecialityID` (`SpecialityID`);";
$stmt->Execute($query);

$query = "INSERT INTO `tmp_data_speciality_study` SELECT sp.SpecialityID,
            IF(stud_n.BudgetScopeWave1 > 0, stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2 ) AS BudgetNext,
            IF(stud_c.BudgetScopeWave1 > 0, stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2 ) AS Budget,
            IF(stud_l.BudgetScopeWave1 > 0, stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2 ) AS BudgetLast,
            
            IF(stud_n.BudgetScopeWave1 > 0 AND stud_n.BudgetScopeWave2 > 0, 
               LEAST(stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2), 
               GREATEST(stud_n.BudgetScopeWave1, stud_n.BudgetScopeWave2)) AS BudgetMinNext,
            IF(stud_c.BudgetScopeWave1 > 0 AND stud_c.BudgetScopeWave2 > 0, 
               LEAST(stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2), 
               GREATEST(stud_c.BudgetScopeWave1, stud_c.BudgetScopeWave2)) AS BudgetMin,
            IF(stud_l.BudgetScopeWave1 > 0 AND stud_l.BudgetScopeWave2 > 0, 
               LEAST(stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2), 
               GREATEST(stud_l.BudgetScopeWave1, stud_l.BudgetScopeWave2)) AS BudgetMinLast,
            
            stud_n.BudgetCount AS BudgetCountNext,
            stud_c.BudgetCount AS BudgetCount,
            stud_l.BudgetCount AS BudgetCountLast,
            
            stud_n.PaidPrice AS PaidPriceNext,
            stud_c.PaidPrice AS PaidPrice,
            stud_l.PaidPrice AS PaidPriceLast,
            
            stud_n.PaidScope AS PaidScopeNext,
            stud_c.PaidScope AS PaidScope,
            stud_l.PaidScope AS PaidScopeLast
            
            FROM `data_speciality` AS sp
            LEFT JOIN data_ege e ON sp.SpecialityID=e.SpecialityID
            LEFT JOIN data_subject s ON s.SubjectID=e.SubjectID
            
            LEFT JOIN (SELECT SpecialityID, MAX(Year) AS Year FROM data_speciality_study WHERE Type = 'Full' GROUP BY SpecialityID) AS sp_year ON sp.SpecialityID = sp_year.SpecialityID
            LEFT JOIN data_speciality_study AS stud_n ON sp.SpecialityID = stud_n.SpecialityID AND sp_year.Year = stud_n.Year AND stud_n.Type = 'Full'
            LEFT JOIN data_speciality_study AS stud_c ON sp.SpecialityID = stud_c.SpecialityID AND sp_year.Year - 1 = stud_c.Year AND stud_c.Type = 'Full'
            LEFT JOIN data_speciality_study AS stud_l ON sp.SpecialityID = stud_l.SpecialityID AND sp_year.Year - 2 = stud_l.Year AND stud_l.Type = 'Full'
        
            GROUP BY sp.SpecialityID";
$result = $stmt->Execute($query);

$result = $result === true ? " UPDATE tmp_data_speciality_study SUCCESS" : " UPDATE tmp_data_speciality_study ERROR";
echo $result . "<br/>";

$logFile = fopen(PROJECT_DIR . 'var/log/cron.log', 'a+');
fwrite($logFile, date('Y-m-d H:i:s')  . $result . PHP_EOL);

$query = "DROP TABLE IF EXISTS `tmp_data_ege`";
$stmt->Execute($query);

$query = "CREATE TABLE IF NOT EXISTS `tmp_data_ege` (
      `SpecialityID` int NOT NULL,
      `AllCount` int NOT NULL,
      `GPA` int NOT NULL,
      `SubjectID` int NOT NULL,
      `Score` int NOT NULL
    );";
$stmt->Execute($query);

$query = "INSERT INTO `tmp_data_ege`
    SELECT ege.SpecialityID, all_count.AllCount, (stud.GPA * 1) AS GPA, ege.SubjectID, ege.Score
    FROM data_ege AS ege 
    LEFT JOIN ( 
        SELECT SpecialityID, Count(SpecialityID) AS AllCount 
        FROM data_ege GROUP BY SpecialityID 
    ) AS all_count ON ege.SpecialityID = all_count.SpecialityID 
    LEFT JOIN (
        SELECT spec.SpecialityID, spec.Year, IF(spec.BudgetScopeWave1 > 0, spec.BudgetScopeWave1, spec.BudgetScopeWave2 ) AS GPA
        FROM data_speciality_study AS spec
        LEFT JOIN (
            SELECT SpecialityID, MAX(Year) AS Year
            FROM data_speciality_study
            WHERE (IF(BudgetScopeWave1, BudgetScopeWave1, BudgetScopeWave2) * 1) > 0 GROUP BY SpecialityID
        ) AS sy ON spec.SpecialityID = sy.SpecialityID
        WHERE spec.Type = 'Full' AND spec.Year = sy.Year
        GROUP BY spec.SpecialityID
    ) AS stud ON ege.SpecialityID = stud.SpecialityID";
$result = $stmt->Execute($query);

$result = $result === true ? " UPDATE tmp_data_ege SUCCESS" : " UPDATE tmp_data_ege ERROR";
echo $result . "<br/>";

$logFile = fopen(PROJECT_DIR . 'var/log/cron.log', 'a+');
fwrite($logFile, date('Y-m-d H:i:s')  . $result . PHP_EOL);


