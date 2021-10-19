<?php
require_once(dirname(__FILE__)."/../include/init.php");

$stmt = GetStatement();

$exhibitionID = 18;
$exhibition = $stmt->FetchRow('SELECT * FROM data_exhibition WHERE ExhibitionID='.intval($exhibitionID));
if($exhibition){
    $exhibition['Title'] = 'Навигатор профориентации март 2019 ';
    
    $query = 'INSERT INTO data_exhibition SET
        Title='.Connection::GetSQLString($exhibition['Title']).',
        PageID=NULL,
        Page2ID=NULL,
        Type='.Connection::GetSQLString($exhibition['Type']).',
        DateFrom='.Connection::GetSQLString($exhibition['DateFrom']).',
        DateTo='.Connection::GetSQLString($exhibition['DateTo']).',
        Phone='.Connection::GetSQLString($exhibition['Phone']).',
        Email='.Connection::GetSQLString($exhibition['Email']);
    
    if($stmt->Execute($query)){
        $newExhibitionID = $stmt->GetLastInsertID();
        print_r('new exhibition created: ID='.$newExhibitionID.'<br/>');
        
        $cityList = $stmt->FetchList('SELECT * FROM data_exhibition_city WHERE ExhibitionID='.intval($exhibitionID));
        foreach($cityList as $city){
            $query = 'INSERT INTO data_exhibition_city SET
                ExhibitionID='.Connection::GetSQLString($newExhibitionID).',
                Title='.Connection::GetSQLString($city['Title']).',
                CityTitle='.Connection::GetSQLString($city['CityTitle']).',
                StaticPath='.Connection::GetSQLString($city['StaticPath']).',
                Date='.Connection::GetSQLString($city['Date']).',
                Address='.Connection::GetSQLString($city['Address']).',
                Latitude='.Connection::GetSQLString($city['Latitude']).',
                Longitude='.Connection::GetSQLString($city['Longitude']).',
                InfoList='.Connection::GetSQLString($city['InfoList']).',
                SortOrder='.Connection::GetSQLString($city['SortOrder']).',
                Description='.Connection::GetSQLString($city['Description']).',
                TitleSchedule='.Connection::GetSQLString($city['TitleSchedule']).',
                TitleRegister='.Connection::GetSQLString($city['TitleRegister']).',
                GUID='.Connection::GetSQLString($city['GUID']).',
                Active='.Connection::GetSQLString($city['Active']).',
                EmailTemplate='.Connection::GetSQLString($city['EmailTemplate']).',
                EmailTheme='.Connection::GetSQLString($city['EmailTheme']);
            
            if($stmt->Execute($query)){
                $newCityID = $stmt->GetLastInsertID();
                print_r('new city created: ID='.$newCityID.'<br/>');
                
                $count = 0;
                $city2univer = $stmt->FetchList('SELECT * FROM data_exhibition_city2univer WHERE CityID='.intval($city['CityID']));
                foreach($city2univer as $item){
                    $query = 'INSERT INTO data_exhibition_city2univer SET
                        CityID='.Connection::GetSQLString($newCityID).',
                        UniversityID='.Connection::GetSQLString($item['UniversityID']).',
                        SortOrder='.Connection::GetSQLString($item['SortOrder']);
                    $stmt->Execute($query);
                    $count++;
                }
                print_r('copied city2univer: count='.$count.'<br/>');
                
                $count = 0;
                $partners = $stmt->FetchList('SELECT * FROM data_exhibition_mainpartners WHERE CityID='.intval($city['CityID']));
                foreach($partners as $item){
                    $query = 'INSERT INTO data_exhibition_mainpartners SET
                        CityID='.Connection::GetSQLString($newCityID).',
                        PartnerTitle='.Connection::GetSQLString($item['PartnerTitle']).',
                        PartnerImage='.Connection::GetSQLString($item['PartnerImage']);
                    $stmt->Execute($query);
                    $count++;
                }
                print_r('copied mainpartners: count='.$count.'<br/>');
                
                $count = 0;
                $partners = $stmt->FetchList('SELECT * FROM data_exhibition_partners WHERE CityID='.intval($city['CityID']));
                foreach($partners as $item){
                    $query = 'INSERT INTO data_exhibition_partners SET
                        CityID='.Connection::GetSQLString($newCityID).',
                        PartnerTitle='.Connection::GetSQLString($item['PartnerTitle']).',
                        PartnerImage='.Connection::GetSQLString($item['PartnerImage']);
                    $stmt->Execute($query);
                    $count++;
                }
                print_r('copied partners: count='.$count.'<br/>');
            }
        }
    }
}

?>