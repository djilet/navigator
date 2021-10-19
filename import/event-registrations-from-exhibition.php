<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(__DIR__ . "/../module/users/include/user.php");
require_once(__DIR__ . "/../module/data/include/public/OnlineEvents.php");

$stmt = GetStatement();

$exhibitionID = 40;
$exhibitionStaticPath = 'online';
$eventID = 309;

$signedUsersCount = 0;
$createdLinksCount = 0;

// Зарегистрировать на онлайн событие пользователей, зарегистрированных на выставку
$query = "SELECT * FROM event_registrations WHERE EventID=".intval($exhibitionID)." AND StaticPath=".Connection::GetSQLString($exhibitionStaticPath);
$registrations = $stmt->FetchList($query);

$user = new UserItem();

if(count($registrations) > 0) {
    foreach ($registrations as $registration) {
        if ($userID = $user->getIDByEmail($registration['Email'])) {
            $query = "SELECT * FROM data_online_event2user WHERE OnlineEventID=".intval($eventID)." AND UserItemID = ".intval($userID);
            $registrationsEvents = $stmt->FetchList($query);

            if (count($registrationsEvents) > 0) {
                print_r('Пользователь с UserID='.$userID." уже зарегистрирован на онлайн-событие<br/>");
            } else {
                $onlineEvents = new OnlineEvents('data');
                if ($onlineEvents->signUser($eventID, $userID)) {
                    $signedUsersCount++;
                } else {
                    print_r('Ошибка регистрации на онлайн событие, UserID='.$userID."<br/>");
                }
            }
        } else {
            print_r('Пользователя с Email='.$registration['Email']." не существует<br/>");
        }
    }
}

// Добавить короткие ссылки в регистрации онлайн событий, где их нет
$query = "SELECT * FROM data_online_event2user WHERE OnlineEventID=".intval($eventID)." AND ShortLink IS NULL";
$registrationsWithoutLinks = $stmt->FetchList($query);

if(count($registrationsWithoutLinks) > 0) {
    $onlineEvents = new OnlineEvents("data");

    foreach ($registrationsWithoutLinks as $registration) {
        $shortLink = $onlineEvents->getShortSignURL($eventID, $registration['UserItemID']);

        if($shortLink)
        {
            $query = "UPDATE `data_online_event2user` 
                        SET ShortLink=".Connection::GetSQLString($shortLink)."
                        WHERE OnlineEventID=".intval($eventID)." AND UserItemID=".intval($registration['UserItemID']);

            if ($stmt->Execute($query)) {
                $createdLinksCount++;
            } else {
                print_r('Ошибка добавления ссылки, UserID='.$registration['UserItemID']."<br/>"."<br/>");
                break;
            }
        }
        else
        {
            print_r('Ошибка создания ссылки, UserID='.$registration['UserItemID']."<br/>");
            break;
        }
    }
}

echo "Зарегистрировано на онлайн событие: ".$signedUsersCount."<br/>";
echo "Создано ссылок: ".$createdLinksCount;