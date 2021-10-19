<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/college.php");
require_once(dirname(__FILE__) . "/include/college_speciality.php");
require_once(dirname(__FILE__) . "/include/college_bigdirection.php");
require_once(dirname(__FILE__) . "/../data/include/public/Region.php");
require_once(dirname(__FILE__) . "/../data/include/CityList.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");

es_include("localpage.php");
es_include("urlfilter.php");

$module = "college";
$request = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');
$urlParser = GetURLParser();
$currentCityPath = $urlParser->GetSubDomain();

switch ($request->GetProperty("Action")) {
	case "loadCollege":
		$college = new College();
		$result['status'] = 'success';

		$page = new Page();
		$page->LoadByID($request->GetIntProperty('PageID'));
		$url = $page->GetPageURL(false);

        if ($currentCityPath && $city = City::getByStaticPath($urlParser->GetSubDomain())){
            $filter = $request->GetProperty('CollegeFilter');
            $filter['CityID'] = $city->GetIntProperty('ID');
            $request->SetProperty('CollegeFilter', $filter);
        }

		$request->SetProperty('BaseURL', PROJECT_PATH . $page->GetProperty('StaticPath'));
		$college->load($request);

		$popupPage = new PopupPage($module, false);
		$tpl = $popupPage->Load('college-tmpl/college_list.html');
		$tpl->LoadFromObjectList('CollegeList', $college);
		$tpl->SetLoop('CollegePager', $college->GetPagingAsArray($url, $url));
		$result['html'] = $popupPage->Grab($tpl);
}

echo json_encode($result);