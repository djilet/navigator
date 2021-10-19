<?php

namespace Import;

class SpecialityAddressImport extends BaseImport {
	public function __construct(){
		parent::__construct();
	}
	public function insert(){
	}
	public function update($id){
	}

	public function customUpdate($row, $stmt, $counter){
		$query = "SELECT Latitude, Longitude FROM data_speciality WHERE Address='".$row['Address']."' AND LENGTH(Latitude) > 0 LIMIT 1";
		$coordinates = $stmt->FetchRow($query);
		if ($coordinates) {
			$query = "UPDATE data_speciality SET `Latitude`='".$coordinates['Latitude']."', `Longitude`='".$coordinates['Longitude']."' WHERE `SpecialityID` = ".$row['SpecialityID'];
			$stmt->Execute($query);
			echo "Специальность с ID=".$row['SpecialityID']." Обновлена из имеющихся координат в БД ".$counter."<br>";
		} else {
			$location = Tools\Location::getCoordinateByAddress($row['Address']);
			$lat = $location->latitude;
			$long = $location->longitude;
			$query = "UPDATE data_speciality SET `Latitude`='".$lat."', `Longitude`='".$long."' WHERE `SpecialityID` = ".$row['SpecialityID'];;
			$stmt->Execute($query);
			echo "Специальность с ID=".$row['SpecialityID']." Обновлена по запросу ".$counter."<br>";
		}
	}
}