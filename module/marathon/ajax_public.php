<?php
require_once(dirname(__FILE__) . "/../../include/init.php");
require_once(dirname(__FILE__) . "/include/user.php");
require_once(dirname(__FILE__) . "/include/user_info_item.php");
require_once(dirname(__FILE__) . "/include/part.php");
require_once(dirname(__FILE__) . "/include/map_step.php");
require_once(dirname(__FILE__) . "/../users/include/user.php");
es_include("localpage.php");

$module = "marathon";
$post = new LocalObject(array_merge($_GET, $_POST));
$result = array('status' => 'error');

switch ($post->GetProperty("Action")) {
	case "completePart":
        $user = new UserItem(null);
        $user->loadBySession();
        
        if ($user->IsPropertySet('UserID') && $post->GetProperty("PartID")) {
            $marathonUser = new MarathonUser($module);
            $marathonUser->load($user->GetProperty('UserID'));
            $marathonUserID = $marathonUser->GetIntProperty('MarathonUserID');
            
            $part = new MarathonPart($module);
            if($part->completePart($post->GetProperty("PartID"), $marathonUserID)){
                $result['status'] = 'success';
            }
            else {
                $result['status'] = 'error';
            }
        }
        else {
            $result['status'] = 'error';
        }
        
        break;

	case "loadStep":
		$user = new UserItem(null);
		$user->loadBySession();

		$marathonUser = new MarathonUser($module);
		$marathonUser->load($user->GetProperty('UserID'));
		$marathonUserID = $marathonUser->GetIntProperty('MarathonUserID');

		$step = new MarathonMapStep($post->GetProperty('Map'), $marathonUserID);

		if ($step->GetProperty('MoreType') == 'NoSave' && $step->validate()){
			$step->skipStepForUser();
		}
		
		$popupPage = new PopupPage($module, false);
		$template = $popupPage->Load("marathon-tmpl/page_step.html");
		$template->LoadFromObject($step);
		$template->SetVar('Ajax',true);
		if ($post->IsPropertySet('Redirect')){
			$template->SetVar('Redirect',$post->GetProperty('Redirect'));
		}
		$content = $popupPage->Grab($template);

		$result['status'] = 'success';
		$result['content'] = $content;
		break;


	case "getInfo":
		$user = new UserItem(null);
		$user->loadBySession();

		$marathonUser = new MarathonUser($module);
		$marathonUser->load($user->GetProperty('UserID'));
		$marathonUserID = $marathonUser->GetIntProperty('MarathonUserID');

		$popupPage = new PopupPage($module, false);
		$template = $popupPage->Load("marathon-tmpl/page_info.html");

		$current = 0;
		$items = UserInfoItem::GetItemsName();
		foreach ($items as $key => $item){
				$task_list[$key]['Name'] = $item;
				if (UserInfoItem::getUserInfoIDByItem($marathonUserID, $item)){
					$task_list[$key]['Complete'] = 1;
				}
				elseif($current == 0){
					$current = 1;
					$task_list[$key]['Current'] = 1;
				}
			}

			if($post->IsPropertySet("Item")){
				$item_name = $post->GetProperty("Item");
				$info_item = new UserInfoItem($item_name, $marathonUserID);
				$info_item->load();
			}
			else{
				foreach ($items as $key => $item) {
					if(!UserInfoItem::getUserInfoIDByItem($marathonUserID, $item)){
						$info_item = new UserInfoItem($item, $marathonUserID);
						$info_item->load();
						if ($key >= count($items) -1){
							$template->SetVar('LastInfo', 1);
						}
						break;
					}
				}
			}

		$template->LoadFromObject($info_item);
		$template->SetVar('Ajax',true);
		$template->SetLoop('TaskList', $task_list);
		$content = $popupPage->Grab($template);

		$result['status'] = 'success';
		$result['content'] = $content;
		break;

	case "getSocialLink":
		$popupPage = new PopupPage($module, false);
		$template = $popupPage->Load("marathon-tmpl/page_modal_social.html");
		$content = $popupPage->Grab($template);

		$result['status'] = 'success';
		$result['content'] = $content;
		break;

	case "getCompleteMarathonPage":
		$popupPage = new PopupPage($module, false);
		$template = $popupPage->Load("marathon-tmpl/page_modal_complete.html");
		$content = $popupPage->Grab($template);

		$result['status'] = 'success';
		$result['content'] = $content;
		break;

}

echo json_encode($result);
