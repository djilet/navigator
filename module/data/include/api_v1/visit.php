<?php
es_include("swagger.php");
class DataVisit extends LocalObject {
	
	private $module;

	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}
	
	public function addAllFromRequest($request)
	{
	    if($request->IsPropertySet('VisitListJson')){
	        $visitList = json_decode($request->GetProperty('VisitListJson'), true);
	        for($i=0; $i<count($visitList); $i++){
	            if(!$this->add($visitList[$i])){
	                return false;
	            }
	        }
	        return true;
	    }
	    return false;
	}

	public function add($object)
	{
    	$stmt = GetStatement();
		
    	if(isset($object['RegistrationID']) && isset($object['VisitTime']) && isset($object['UserID'])) {
    	    $query = 'INSERT INTO `data_exhibition_visits`
                SET RegistrationID='.intval($object['RegistrationID']).', 
                    VisitTime='.Connection::GetSQLString($object['VisitTime']).', 
                    LoadedTime='.Connection::GetSQLString(GetCurrentDateTime()).', 
                    ScannerUserID='.intval($object['UserID']);
    	    
    	    if(isset($object['ExhibitionID'])) {
    	        $query .= ', ScannerExhibitionID='.intval($object['ExhibitionID']);
    	    }
    	    if(isset($object['CityID'])) {
    	        $query .= ', ScannerCityID='.intval($object['CityID']);
    	    }
    	    if(isset($object['Room'])) {
    	        $query .= ', ScannerRoom='.Connection::GetSQLString($object['Room']);
    	    }
    	    if(isset($object['Action'])) {
    	        $query .= ', ScannerAction='.Connection::GetSQLString($object['Action']);
    	    }
    	    if(isset($object['UniversityID'])) {
    	        $query .= ', ScannerUniversityID='.intval($object['UniversityID']);
    	    }
    	    
    	    if ($stmt->Execute($query)) {
                //send to CRM
                $query = "SELECT EventID, FirstName, LastName, Who, Class, Phone, Email, City,
                    utm_source, utm_medium, utm_campaign
                    FROM event_registrations
                    WHERE RegistrationID = ".$object['RegistrationID'];

                if ($registration = $stmt->FetchRow($query)) {
                    $query = "SELECT GUID FROM data_exhibition_city
                        WHERE ExhibitionID = ".$registration['EventID']." AND CityTitle=".Connection::GetSQLString($registration['City']);
                    $cityGUID = $stmt->FetchField($query);

                    if ($cityGUID) {
						$queryCount = "SELECT COUNT(*) as registrationCount FROM data_exhibition_visits
							WHERE RegistrationID = " . $object['RegistrationID'];
		
						if ($stmt->FetchRow($queryCount)['registrationCount'] == 1) {
							$querySelect = "SELECT CRMRegistrationId
								FROM event_registrations
								WHERE RegistrationID = " . $object['RegistrationID'];
			
							if ($registration = $stmt->FetchRow($querySelect)['CRMRegistrationId']) {
								$swager = new Swagger();
								$swager->sendVisitToCRM($registration);
							}
						}
                    }
                }

    	        return true;
    	    }
    	}
		
		return false;
	}
}