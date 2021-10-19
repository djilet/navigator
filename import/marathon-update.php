<?php 

require_once(dirname(__FILE__)."/../include/init.php");

//change_2_1();
//insert_2_4();
//update_3_1();

function change_2_1()
{
    $stmt = GetStatement();
    $stmt->Execute("UPDATE `marathon_stage_part` SET `Title` = 'Посмотри видео: \"Почему ты еще не выбрал профессию?\"', `Type` = 'video', `Description` = 'Чтобы выбрать профессию, нужно... знать из чего выбирать! По статистике, школьник средней и старшей школы могут назвать не более 30 профессий, а ведь это меньше 10% от всего разнообразия, которое есть сегодня на рынке труда. Может быть поэтому ты еще не выбрал(а) профессию? Разбираемся в видео.', `MinCountForComplete` = NULL WHERE `marathon_stage_part`.`PartID` = 201");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20102");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20103");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20104");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20105");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20106");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20107");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20108");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20109");
    $stmt->Execute("DELETE FROM `marathon_stage_part_task` WHERE `marathon_stage_part_task`.`TaskID` = 20110");
    $stmt->Execute("INSERT INTO `marathon_stage_part_video` (`PartID`, `YoutubeID`) VALUES ('201', 'rtBxxlfKMfA')");
}

function insert_2_4()
{
    $stmt = GetStatement();
    $stmt->Execute("UPDATE `marathon_stage_part` SET SortOrder=SortOrder+1 WHERE StageID=2 AND SortOrder>=2");
    $stmt->Execute("INSERT INTO `marathon_stage_part` (`PartID`, `StageID`, `Title`, `Type`, `XP`, `Description`, `SortOrder`, `ActiveDateTime`, `MinCountForComplete`) VALUES ('204', '2', 'Посмотри видео: \"Самые высокооплачиваемые профессии\"', 'video', '0', 'Уровень дохода - важный показатель успешности. Если на вершине ваших приоритетов находятся деньги, то подборка высокооплачиваемых профессий заметно продвинет вас в профориентации. Но помните, что любимую работу человек всегда делает хорошо, а за хорошую работу легко получать высокую зарплату.', '2', NULL, NULL)");
    $stmt->Execute("INSERT INTO `marathon_stage_part_video` (`PartID`, `YoutubeID`) VALUES ('204', 'Gyaq1ZnK05k')");
    
    $userList = $stmt->FetchList("SELECT MarathonUserID FROM `marathon_stage_part2user` WHERE PartID=202");
    for($i=0; $i<count($userList); $i++){
        $stmt->Execute("INSERT INTO `marathon_stage_part2user` (`PartID`, `MarathonUserID`, `Status`, `XP`) VALUES ('204', ".$userList[$i]['MarathonUserID'].", 'complete', 0)");
    }
}

function update_3_1()
{
    $stmt = GetStatement();
    $stmt->Execute("UPDATE `marathon_stage_part` SET `Title` = 'Посмотри 3 видео', `Description` = 'Продолжаем погружение в мир профессий! На прошлом этапе ты читал(а) интервью, теперь пора увидеть профессионалов своими глазами. Предлагаем тебе посмотреть любые 3 видео о профессиях. И обязательно возращайся к этапам! Тут еще много полезностей для тебя.', `MinCountForComplete` = '3' WHERE `marathon_stage_part`.`PartID` = 301;");
    $stmt->Execute("UPDATE `marathon_stage_part_task` SET `Title` = 'Кто работает в IT компаниях?', `Description` = '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/tPL0S736NO0\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>' WHERE `marathon_stage_part_task`.`TaskID` = 30101");

    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30102', '301', 'theory', '10 глупых вопросов ПСИХОЛОГУ–ПСИХОТЕРАПЕВТУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/47THplYQR7U\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '2')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30103', '301', 'theory', '10 глупых вопросов АДВОКАТУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/Oc0EPsOAkAI\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '3')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30104', '301', 'theory', '10 глупых вопросов ХИРУРГУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/xjGO2RaDpI4\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '4')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30105', '301', 'theory', '20 глупых вопросов СУДМЕДЭКСПЕРТУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/rbgFr2KmLJk\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '5')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30106', '301', 'theory', '10 глупых вопросов РЕЖИССЕРУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/QcBKFC4GNfA\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '6')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30107', '301', 'theory', '10 глупых вопросов АКУШЕРУ-ГИНЕКОЛОГУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/eEWjw7YaPsc\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '7')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30108', '301', 'theory', '10 глупых вопросов АКТРИСЕ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/5VhDslMV2Jc\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '8')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30109', '301', 'theory', '10 глупых вопросов БАЛЕРИНЕ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/qjo-xf1Ps5I\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '9')");
    $stmt->Execute("INSERT INTO `marathon_stage_part_task` (`TaskID`, `PartID`, `Type`, `Title`, `Description`, `XP`, `SortOrder`) VALUES ('30110', '301', 'theory', '10 глупых вопросов ИНЖЕНЕРУ-ГЕОДЕЗИСТУ', '<div style=\"position: relative;width: 100%;height: 0;padding-bottom: 56.25%;\"><iframe style=\"position: absolute;top: 0;left: 0;width: 100%;height: 100%;\" id=\"ifr\" src=\"https://www.youtube.com/embed/8dezr58EJnA\" frameborder=\"0\" allow=\"encrypted-media\" allowfullscreen></iframe></div>', '0', '10')");
}

?>