<?php

if (!defined('IS_ADMIN')) {
    echo "Incorrect call to admin interface";
    exit();
}

require_once(dirname(__FILE__) . "/init.php");
require_once(dirname(__FILE__) . "/include/message_list.php");
require_once(dirname(__FILE__) . "/include/message.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");
require_once(dirname(__FILE__) . "/../data/include/Articles.php");
require_once(dirname(__FILE__) . "/../data/include/UniversityAgent.php");
require_once(dirname(__FILE__) . "/../data/include/admin/university.php");
require_once(dirname(__FILE__) . "/../data/include/admin/author_list.php");
es_include("urlfilter.php");

$module = $request->GetProperty('load');
$adminPage = new AdminPage($module);
$urlFilter = new URLFilter();
$request = new LocalObject(array_merge($_GET, $_POST));
$page = DefineInitialPage($request);
$questionMessage = new QuestionMessage();

$articles = new Articles();
$dataUniversity = new DataUniversity();

$urlFilter->LoadFromObject($request);
$user = new User();
$user->LoadBySession();

if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
}

$navigation = array(
    array("Title" => GetTranslation("module-admin-title", $module), "Link" => $moduleURL),
);
$javaScripts = array(
	array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
    array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
    array("JavaScriptFile" => PATH2MAIN . 'js/components.js'),
);
$styleSheets = array(
    array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css")
);
$header = array(
		"Title" => GetTranslation("module-admin-title", $module),
		"Navigation" => $navigation,
		"JavaScripts" => $javaScripts,
        "StyleSheets" => $styleSheets,
);

$list = new QuestionMessageList($module);
$content = $adminPage->Load("message_list.html", $header);
$content->LoadFromObject($request);

if ($agent){
    $articles->load(new LocalObject([
        'ArticleFilter' => [
            'AuthorID' => $agent->AuthorID,
        ]
    ]), 0);

    $articleIds = array_map(function ($item){
        return $item['ArticleID'];
    }, $articles->GetItems());

    $request->SetProperty('MultipleFilter', [
        [
            'Type' => QuestionMessage::TYPE_ARTICLE,
            'AttachIds' => $articleIds
        ],
        [
            'Type' => QuestionMessage::TYPE_UNIVERSITY,
            'AttachIds' => [$agent->UniversityID]
        ]
    ]);

    $content->SetVar('WithoutCommentStatus', true);
    $content->SetVar('WithoutAuthor', true);
}

if ($request->IsPropertySet('Do')){
	switch ($request->GetProperty('Do')){
		case 'Remove':
			if ($request->GetProperty("MessageIDs")){
			    if($agent){
			        foreach ($request->GetProperty("MessageIDs") as $messageId){
                        $questionMessage = QuestionMessage::get($messageId);
                        $attachId = $questionMessage->GetProperty('AttachID');

                        if ($questionMessage->GetProperty('Type') === QuestionMessage::TYPE_ARTICLE){
                            if (!in_array($attachId, $articleIds)){
                                Send403();
                            }
                        }
                        elseif ($questionMessage->GetProperty('Type') === QuestionMessage::TYPE_UNIVERSITY){
                            if ($agent->UniversityID != $attachId){
                                Send403();
                            }
                        }
                        else{
                            Send403();
                        }
                    }
                }
				$list->removeByIDs($request->GetProperty("MessageIDs"));
			}
			break;
		case 'RemoveOnWeek':
			if ($userIds = $request->GetProperty("UserID")){
                $date = new DateTime();
                $date->modify("-7 day");

			    if ($agent){
                    $filter = [
                        'MultipleFilter' => [
                            [
                                'Type' => QuestionMessage::TYPE_ARTICLE,
                                'AttachIds' => $articleIds,
                                'UserIds' => $userIds,
                            ],
                            [
                                'Type' => QuestionMessage::TYPE_UNIVERSITY,
                                'AttachIds' => [$agent->UniversityID],
                                'UserIds' => $userIds,
                            ]
                        ],
                        'CreatedGt' => $date->format('Y-m-d')
                    ];

                    $list->remove($filter);
                }
			    else{
                    $list->removeByUserID($userIds);
                }
			}
			break;
		case 'SwitchCommentStatus':
		    if ($agent){
		        Send403();
            }
			if ($userID = $request->GetProperty("UserID")){
				$userItem = new UserItem();
				$userItem->loadByID($userID);
				$userItem->switchCommentStatus();
				$userItem->updateData($userItem);
			}
			break;
	}

    $urlFilter->RemoveProperty('Do');
    $urlFilter->RemoveProperty('MessageIDs');
    $urlFilter->RemoveProperty('UserID');
    $urlFilter->RemoveProperty('load');
    Send302($moduleURL . '&' . $urlFilter->GetForURL());
}

$list->load($request);
$list->prepareAttachInfo();
$authorList = new DataAuthorList();
$authorList->LoadAuthorList();

//exit();
//TODO move to service
$preparedList = [];
foreach ($list->GetItems() as $index => $item){
    if ($item['Type'] == QuestionMessage::TYPE_ARTICLE){
        $item['AuthorList'] = $authorList->GetItems();

        if ($item['ChildList']){
            foreach ($item['ChildList'] as $childIndex => $child){
                $item['ChildList'][$childIndex]['AuthorList'] = $authorList->GetItems();
            }
        }
    }

    $preparedList[] = $item;
}

$content->SetLoop('QuestionMessageList', $preparedList);
$content->SetVar("Paging", $list->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
$content->SetVar('ParamsForURL', $urlFilter->GetForURL());
$content->SetVar('TotalItemsCount', $list->GetCountTotalItems());

$content->LoadErrorsFromObject($list);
$content->LoadMessagesFromObject($list);

$content->SetVar('BaseURL', $moduleURL.'&PageID='.$request->GetIntProperty('PageID'));

//User module page url
$content->SetVar('UserModuleURL', Module::getAdminModuleUrl('users'));
$adminPage->Output($content);