<?php 
define("IS_ADMIN", true);
require_once(dirname(__FILE__)."/../../include/init.php");
require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/tracker.php");
es_include("user.php");

$user = new User();
if (!$user->LoadBySession() || !$user->Validate(array(INTEGRATOR, ADMINISTRATOR, ONLINEEVENT))) {
    $result["SessionExpired"] = GetTranslation("your-session-expired");
    exit();
}
else {
    $request = new LocalObject(array_merge($_GET, $_POST));
	$tracker = new Tracker();

    if ($request->GetProperty('Do') == 'ReportCSV'){

		$date_from = $request->GetProperty("ReportDateFrom");
		$date_to = $request->GetProperty("ReportDateTo");
		$last_id = $request->GetProperty("LastID");
		$max_id = $request->GetProperty("MaxID");

		$part_count = 100;
		if (empty($last_id)) {
			$last_id = 0;
		}
		if (empty($max_id)) {
			$max_id = 0;
		}

		$file_name = 'export.csv';
		$file_path = TRACKER_EXPORT_DIR . $file_name;
		if ( $answer = $tracker->getTrackingList($date_from, $date_to, $part_count, $last_id, $max_id) ) {
			if($answer['Status'] == 'success'){
				$answer['FilePath'] = $file_path;
				$answer['FileUrl'] = GetUrlPrefix() . "website/" . WEBSITE_FOLDER . TRACKER_FILE_DIR . $file_name;
				$result = $answer;
			}
			elseif ($answer['Status'] == 'work') {
					if( $tracker->exportToCsv($file_path, $last_id) ){
					$result = $answer;
				}
			}
		}
	}
}

if ( $tracker->HasErrors() ) {
	$result['Status'] = 'error';
	$result['Error'] = $tracker->GetErrorsAsString();
}

echo json_encode($result);