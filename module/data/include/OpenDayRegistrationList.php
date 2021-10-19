<?php
require_once(dirname(__FILE__) . "/../../users/include/user.php");

class OpenDayRegistrationList extends LocalObjectList {

    private $module;
    protected $visitsList;
    protected $errorList;

    public function __construct($module = 'data', $data = array())
    {
        parent::LocalObjectList($data);

        $this->module = $module;
        $this->SetSortOrderFields(array(
            "id_desc" => "r.RegistrationID DESC",
        ));

        $this->SetOrderBy("id_desc");
    }

    public function load($onPage = 40, $fullList = false, $eventID = false)
    {
        $query = "SELECT r.*, 
			CONCAT('source=', r.utm_source, CONCAT_WS('',', medium=', r.utm_medium, ', campaign=', r.utm_campaign, ', term=', r.utm_term, ', content=', r.utm_content)) as UTM,
			r.ShortLink AS TicketURL, COUNT(v.VisitID) as VisitCount
			FROM `data_open_day_registration` AS r
            LEFT JOIN `data_open_day` d ON r.EventID=d.ID 
            LEFT JOIN `data_open_day_visit` v ON r.RegistrationID=v.RegistrationID";
        if($eventID)
        {
            $query .= ' WHERE r.EventID='.intval($eventID);
        }
        $query .= ' GROUP BY r.RegistrationID';
        if($fullList == true)
            $this->SetItemsOnPage(0);
        else
            $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        $this->LoadFromSQL($query);
    }

    public function exportToCSV()
    {
        ob_start();
        $f = fopen("php://output", "w");

        $row = array("ID", "Имя", "Фамилия", "Кто привел", "Город", "Кто", "Класс", "Время", "Интерес", "Телефон", "E-mail", "Посетил", "Зарегистрирован", "Источник", "UTM", "Ссылка на билет", "Страница регистрации", "Направления", "Вузы", "Категории лекций");
        fputcsv($f, $row, ";");

        foreach($this->GetItems() as $item)
        {
            $row = array(
                $item["RegistrationID"],
                $item["FirstName"],
                $item["LastName"],
                $item["BaseRegistrationID"],
                $item["City"],
                $item["Who"],
                $item["Class"],
                $item["Time"],
                $item["Interest"],
                $item["Phone"],
                $item["UserEmail"],
                $item["VisitCount"]>0?'Да':'',
                $item["Created"],
                $item["Source"],
                $item["UTM"],
                $item["TicketURL"],
                $item["StaticPath"],
                $item["AdditionalBigDirection"],
                $item["AdditionalUniversity"],
                $item["AdditionalType"]
            );
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="registrations.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }

    public function exportToCSVGroup($eventID)
    {
        ob_start();
        $f = fopen("php://output", "w");

        $csvline = array(
            "Фамилия родителя",
            "Имя родителя",
            "Телефон родителя",
            "Email родителя",
            "Родитель посетил",
            "Родитель ID",
            "Фамилия школьника",
            "Имя школьника",
            "Телефон школьника",
            "Email школьника",
            "Школьник посетил",
            "ID школьника",
            "Город",
            "Год выпуска",
            "Время",
            "Интерес",
            "Зарегистрирован",
            "Источник",
            "UTM source",
            "UTM medium",
            "UTM campaign",
            "UTM term",
            "UTM content",
            "Ссылка на билет",
            "Страница регистрации",
            "Направления",
            "Вузы",
            "Категории лекций");
        fputcsv($f, $csvline, ";");

        $stmt = GetStatement();
        $query = "SELECT r.*, r.Email as UserEmail,
			r.ShortLink AS TicketURL, COUNT(v.VisitID) as VisitCount
			FROM `data_open_day_registration` AS r
            LEFT JOIN `data_open_day_visit` v ON r.RegistrationID=v.RegistrationID
            WHERE r.EventID=".intval($eventID)."
            GROUP BY r.RegistrationID 
            ORDER BY r.RegistrationID DESC";
        $results = $stmt->FetchIndexedList($query);
        foreach($results as $id=>$row)
        {
            if(!isset($results[$id]["InUse"]))
            {
                $child = null;
                $parent = null;
                if($row["Who"] == "Ученик")
                {
                    $child = $row;
                    if($row["BaseRegistrationID"] && isset($results[$row["BaseRegistrationID"]]) && $results[$row["BaseRegistrationID"]]["Who"] == "Родитель")
                    {
                        $results[$row["BaseRegistrationID"]]["InUse"] = 1;
                        $parent = $results[$row["BaseRegistrationID"]];
                    }
                }
                elseif($row["Who"] == "Родитель")
                {
                    $parent = $row;
                    if($row["BaseRegistrationID"] && isset($results[$row["BaseRegistrationID"]]) && $results[$row["BaseRegistrationID"]]["Who"] == "Ученик")
                    {
                        $results[$row["BaseRegistrationID"]]["InUse"] = 1;
                        $child = $results[$row["BaseRegistrationID"]];
                    }
                }
                else
                {
                    $child = $row;
                }

                $year = "";
                if($child != null && intval($child["Class"])>0 && $child["Who"] == "Ученик")
                {
                    $year = 2031 - intval($child["Class"]);
                }
                elseif($parent != null && intval($parent["Class"])>0)
                {
                    $year = 2031 - intval($parent["Class"]);
                }

                $csvline = array(
                    ($parent != null)?$parent["LastName"]:"",
                    ($parent != null)?$parent["FirstName"]:"",
                    ($parent != null)?FormatPhone($parent["Phone"]):"",
                    ($parent != null)?$parent["UserEmail"]:"",
                    ($parent != null)?($parent["VisitCount"]>0?"Да":""):"",
                    ($parent != null)?$parent["RegistrationID"]:"",
                    ($child != null)?$child["LastName"]:"",
                    ($child != null)?$child["FirstName"]:"",
                    ($child != null)?FormatPhone($child["Phone"]):"",
                    ($child != null)?$child["UserEmail"]:"",
                    ($child != null)?($child["VisitCount"]>0?"Да":""):"",
                    ($child != null)?$child["RegistrationID"]:"",
                    ($child != null)?$child["City"]:$parent["City"],
                    $year,
                    ($child != null)?$child["Time"]:$parent["Time"],
                    ($child != null)?$child["Interest"]:$parent["Interest"],
                    ($child != null)?$child["Created"]:$parent["Created"],
                    ($child != null)?$child["Source"]:$parent["Source"],
                    ($child != null)?$child["utm_source"]:$parent["utm_source"],
                    ($child != null)?$child["utm_medium"]:$parent["utm_medium"],
                    ($child != null)?$child["utm_campaign"]:$parent["utm_campaign"],
                    ($child != null)?$child["utm_term"]:$parent["utm_term"],
                    ($child != null)?$child["utm_content"]:$parent["utm_content"],
                    ($child != null)?$child["TicketURL"]:$parent["TicketURL"],
                    ($child != null)?$child["StaticPath"]:$parent["StaticPath"],
                    ($child != null)?$child["AdditionalBigDirection"]:$parent["AdditionalBigDirection"],
                    ($child != null)?$child["AdditionalUniversity"]:$parent["AdditionalUniversity"],
                    ($child != null)?$child["AdditionalType"]:$parent["AdditionalType"]
                );
                fputcsv($f, $csvline, ";");
            }
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="registrations.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }

    public function exportVisitsByUserToCSV($eventID)
    {
        $stmt = GetStatement();
        $query = "SELECT v.RegistrationID, v.VisitTime, v.ScannerUserID, v.ScannerOpenDayID, v.ScannerRoom, v.ScannerAction, v.ScannerUniversityID,
            r.FirstName, r.LastName, r.Who, r.Class, r.Phone, r.Email, r.City, u.ShortTitle AS UniversityTitle
            FROM data_open_day_visit v
            LEFT JOIN data_open_day_registration r ON v.RegistrationID=r.RegistrationID
            LEFT JOIN data_university u ON v.ScannerUniversityID=u.UniversityID
            WHERE v.ScannerOpenDayID=".intval($eventID);
        $visitList = $stmt->FetchList($query);

        $actionSet = array();
        $registrationSet = array();
        for($i=0; $i<count($visitList); $i++)
        {
            $actionKey = $visitList[$i]["ScannerRoom"];
            if(isset($visitList[$i]["ScannerAction"])) $actionKey.='-'.$visitList[$i]["ScannerAction"];
            if(isset($visitList[$i]["ScannerUniversityID"])) $actionKey.='-'.$visitList[$i]["UniversityTitle"];
            if(!in_array($actionKey, $actionSet))
            {
                $actionSet[] = $actionKey;
            }

            $regKey = $visitList[$i]["RegistrationID"].$visitList[$i]["ScannerOpenDayID"];
            if(!isset($registrationSet[$regKey]))
            {
                $registrationSet[$regKey] = array(
                    "RegistrationID" => $regKey,
                    "Info" => $visitList[$i],
                    "Visits" => array(),
                );
            }
            if(!isset($registrationSet[$regKey]["Visits"][$actionKey]))
            {
                $registrationSet[$regKey]["Visits"][$actionKey] = $visitList[$i]["VisitTime"];
            }
        }

        ob_start();
        $f = fopen("php://output", "w");

        $row = array("ID Регистрации", "Имя", "Фамилия", "Город", "Кто", "Класс", "Телефон", "E-mail");
        for($i=0; $i<count($actionSet); $i++)
        {
            $row[] = $actionSet[$i];
        }
        fputcsv($f, $row, ";");

        foreach($registrationSet as $item)
        {
            $row = array(
                $item["Info"]["RegistrationID"],
                $item["Info"]["FirstName"],
                $item["Info"]["LastName"],
                $item["Info"]["City"],
                $item["Info"]["Who"],
                $item["Info"]["Class"],
                $item["Info"]["Phone"],
                $item["Info"]["Email"]
            );
            for($i=0; $i<count($actionSet); $i++)
            {
                $row[] = $item["Visits"][$actionSet[$i]];
            }
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="visit2user.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }

    public function exportVisitsToCSV($eventID)
    {
        $stmt = GetStatement();
        $query = "SELECT v.RegistrationID, v.VisitTime, v.ScannerRoom, v.ScannerAction, v.ScannerUniversityID,
            r.FirstName, r.LastName, r.Who, r.Class, r.Phone, r.Email, r.City, u.ShortTitle AS UniversityTitle, br.FirstName AS BaseFirstName, br.LastName AS BaseLastName
            FROM data_open_day_visit v
            LEFT JOIN data_open_day_registration r ON v.RegistrationID=r.RegistrationID
            LEFT JOIN data_open_day_registration br ON r.BaseRegistrationID=br.RegistrationID
            LEFT JOIN data_university u ON v.ScannerUniversityID=u.UniversityID
            WHERE v.ScannerOpenDayID=".intval($eventID);
        $visitList = $stmt->FetchList($query);

        ob_start();
        $f = fopen("php://output", "w");

        $row = array("ID Регистрации", "Имя", "Фамилия", "Кто привел", "Город", "Кто", "Класс", "Телефон", "E-mail", "Зона отметки", "Лекция/вуз", "Время отметки");
        fputcsv($f, $row, ";");

        foreach($visitList as $item)
        {
            $row = array(
                $item["RegistrationID"],
                $item["FirstName"],
                $item["LastName"],
                $item["BaseFirstName"].' '.$item["BaseLastName"],
                $item["City"],
                $item["Who"],
                $item["Class"],
                $item["Phone"],
                $item["Email"],
                $item["ScannerRoom"],
                $item["ScannerAction"].(isset($item["ScannerUniversityID"])?('/'.$item["UniversityTitle"]):''),
                $item["VisitTime"]
            );
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="visits.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }

    public function iniVisitsFromCSV($file){
        if ($file['error'] > 0){
            $this->AddError('file-error',$this->module);
            return false;
        }
        if (!in_array($file['type'], array('text/csv','text/plain','text/x-csv','application/vnd.ms-excel','application/csv','application/x-csv','text/comma-separated-values','text/x-comma-separated-values'))){
            $this->AddError('file-not-csv',$this->module);
            return false;
        }

        if (($this->visitsList = fopen($file['tmp_name'], "r")) == FALSE) {
            $this->AddError('file-open-error',$this->module);
            return false;
        }

        return true;
    }

    public function nextVisitsRow($delimiter = ';', $enclousere='"'){
        if ($row = fgetcsv($this->visitsList, 0, $delimiter, $enclousere)){
            return $row;
        }
        else{
            fclose($this->visitsList);
            return false;
        }
    }

    public function importFromCSV(int $eventID)
    {
        $openDay = OpenDay::load($eventID);

        $i = 0;
        while ($row = $this->nextVisitsRow()){
            $form = [];
            $openDayRegistration = new OpenDayRegistration();

            $i++;
            if ($row[0] == 'Email'){
                continue;
            }

            $formRequest = new LocalObject();
            $formRequest->SetProperty('EventID', $eventID);
            $currentCityInfo = null;
            $error = array();

            $user = new UserItem();
            if ($id = $user->getIDByEmail($row[0])){
                $user->loadByID($id);
            }

            $form['UserEmail'] = array($row[0]);
            $form['UserName'] = array($row[2]);
            $form['UserLastName'] = array($row[3]);
            $form['UserWho'] = array($row[5]);
            $form['UserClassNumber'] = array($row[6]);
            $form['UserPhone'] = array($row[1]);
            $form['UserInterest'] = array($row[7]);
            $form['UserTime'] = array($row[8]);

            $formRequest->SetProperty('RegisterForm', $form);

            if (empty($error['ErrorList'])){
                if (!$openDayRegistration->registration($formRequest, $openDay, $user, OpenDay::BASE_PAGE_URL, false)){
                    if (isset($formRequest->GetProperty('RegisterFormList')[0]['ErrorList'])){
                        $error['ErrorList'] = $formRequest->GetProperty('RegisterFormList')[0]['ErrorList'];
                    }
                }
            }

            if (!empty($error['ErrorList'])){
                $error['Line'] = $i;
                $error['Email'] = $row[0];
                $error['Name'] = $row[2];
                $errorList[] = $error;
            }

            if (!empty($errorList)){
                $this->setErrorList($errorList);
            }
        }
    }

    public function HasErrors(): bool
    {
        return !empty($this->_errors) || !empty($this->errorList);
    }

    /**
     * @return mixed
     */
    public function getErrorList(): array
    {
        return $this->errorList;
    }

    /**
     * @param mixed $errorList
     */
    public function setErrorList(array $errorList)
    {
        $this->errorList = $errorList;
    }
}