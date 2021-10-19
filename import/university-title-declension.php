<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");

$list = GetStatement()->FetchList("SELECT UniversityID, Title FROM data_university WHERE TitleInPrepositionalCase IS NULL");

function getNewFormText($text){
    $text = urlencode($text);
    $text = rawurldecode($text);
    $url = "http://pyphrasy.herokuapp.com/inflect?phrase={$text}&forms=loct";
    if($response = file_get_contents($url)){
        return json_decode($response, true);
    }

    return false;
}

$stmt = GetStatement();

foreach ($list as $item){
    $form = getNewFormText($item['Title']);
    if ($form){
        $caseTitle = $form['loct'];
        $caseTitle = mb_ereg_replace('^[\ ]+', '', $caseTitle);
        $caseTitle = mb_strtoupper(mb_substr($caseTitle, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($caseTitle, 1, mb_strlen($caseTitle), 'UTF-8');
        if ($stmt->Execute("UPDATE data_university SET TitleInPrepositionalCase = '{$caseTitle}' WHERE UniversityID = {$item['UniversityID']}")){
            echo "UPDATED - {$item['UniversityID']} \n";
        }
        else{
            echo "ERROR - {$stmt->_dbLink->error} \n";
        }
    }
}
