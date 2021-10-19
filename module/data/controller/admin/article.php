<?php

$user = new User();
$user->LoadBySession();
$article = new DataArticle($module);

if ($user->getRole() === ROLE_UNIVERSITY){
    $agent = UniversityAgent::getByUserID($user->GetIntProperty('UserID'));
    $agentAuthor = new DataAuthor();
    $agentAuthor->LoadByID($agent->AuthorID);
}

if ($request->IsPropertySet("ArticleID"))
{
	$urlFilter->AppendFromObject($request, array("Page"));
	if ($request->GetProperty("ArticleID") > 0)
		$title = GetTranslation("title-article-edit", $module);
	else
		$title = GetTranslation("title-article-add", $module);

	$navigation[] = array("Title" => $title, "Link" => $moduleURL."&".$urlFilter->GetForURL()."&ArticleID=".$request->GetProperty("ArticleID"));
	$styleSheets = array(
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/datetimepicker/css/datetimepicker.min.css"),
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/timepicker/css/timepicker.min.css"),
		array("StyleSheetFile" => ADMIN_PATH."template/plugins/tagsinput/css/bootstrap-tagsinput.css"),
        array("StyleSheetFile" => PATH2MAIN . 'css/libs/bootstrap-select.css'),
	);
	$javaScripts = array(
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/datetimepicker.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/datetimepicker/js/locales/bootstrap-datetimepicker.ru.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/timepicker/js/timepicker.min.js"),
		array("JavaScriptFile" => ADMIN_PATH."template/plugins/tagsinput/js/bootstrap-tagsinput.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/plugins/jquery-ui/smoothness/jquery-ui.min.js"),
        array("JavaScriptFile" => ADMIN_PATH."template/js/staticpath.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ckeditor.js"),
		array("JavaScriptFile" => CKEDITOR_PATH."ajexFileManager/ajex.js"),
        array("JavaScriptFile" => PATH2MAIN . 'js/bootstrap-select.js'),
	);
	$header = array(
		"Title" => $title,
		"Navigation" => $navigation,
		"StyleSheets" => $styleSheets,
		"JavaScripts" => $javaScripts
	);

	$content = $adminPage->Load("article_edit.html", $header);

    if ($request->ValidateNotEmpty('ArticleID')){
        $article->LoadByID($request->GetProperty("ArticleID"));
    }

    //validate agent role
    if ($agent && $article->GetIntProperty('ArticleID') > 0){
        if ($article->GetProperty('AuthorID') != $agentAuthor->GetProperty('AuthorID')){
            Send403();
        }
    }

	if ($request->GetProperty('Do') == 'ChangeBest'){
	    if ($agent){
	        Send403();
        }
        DataArticle::changeBest($request->GetProperty('ArticleID'));
        $urlFilter->SetProperty('ArticleID', $request->GetProperty('ArticleID'));
        header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
        exit();
    }
	elseif ($request->GetProperty("Save")) {
        $article->LoadFromObject($request);

        //validate agent role
        if ($agent){
            $article->SetProperty('AuthorID', $agentAuthor->GetProperty('AuthorID'));
        }

		if ($article->Save())
		{
		    if ($request->IsPropertySet('SimilarArticle')){
		        $article->saveSimilar($request->GetProperty('SimilarArticle'));
            }
			header("Location: ".$moduleURL."&".$urlFilter->GetForURL());
			exit();
		}
		else
		{
			$content->LoadErrorsFromObject($article);
		}
	}
	else{
        $articles = new Articles($module);

        //Best article
        $bestArticle = $articles->getItemInfo(Articles::getBestItemID());
        $baseArticleLink = "<a href=" . $moduleURL."&".$urlFilter->GetForURL() . '&ArticleID=' . $bestArticle['ArticleID'] . ">{$bestArticle['Title']}</a>";
		$content->SetVar('BestArticleTheLink',$baseArticleLink);

		if (mb_strlen($article->GetProperty('Title')) < 83){
		    $content->SetVar('AvailableBest', true);
        }

		//Similar list
        $content->SetLoop('SimilarArticle', $articles->getSimilarList());

        //Author list
        $authorList = new DataAuthorList();
        $authorList->LoadAuthorList();

        $tagList = new ArticleTagList();
        $tagList->load();

        $content->SetLoop('SimilarArticle', $articles->getSimilarList());
        $content->SetLoop('TagList', $tagList->getListForTemplate($article->getTagIDs()));
        $content->SetLoop('AuthorList', $authorList->getListForTemplate([$article->GetProperty('AuthorID')]));

		//Preview
        $previewLink = PROJECT_PATH . 'article/' . $article->GetProperty('StaticPath') . '?Preview=true';
        $content->SetVar('PreviewLink', $previewLink);
	}
	
	$content->LoadFromObject($article);
	$content->SetLoop("ArticleImageParamList", $article->GetImageParams("Article"));
	$content->SetLoop("ArticleMainImageParamList", $article->GetImageParams("ArticleMain"));
	$content->SetVar("IsPublished", $article->ValidateNotEmpty('ArticleID') && $article->GetProperty("DateTime") <= GetCurrentDateTime());
}
else
{
	$header = array(
		"Title" => $currentSectionTitle,
		"Navigation" => $navigation,
	);
	
	$content = $adminPage->Load("article_list.html", $header);

	$articleList = new Articles($module);

	if ($request->GetProperty('Do') == 'Remove' && $request->GetProperty("ArticleIDs"))
	{
	    if ($agent){
	        foreach ($request->GetProperty("ArticleIDs") as $articleId){
                $article->LoadByID($articleId);
                if ($article->GetProperty('AuthorID') != $agentAuthor->GetProperty('AuthorID')){
                    Send403();
                }
            }
        }

		$articleList->Remove($request->GetProperty("ArticleIDs"));
		$content->LoadMessagesFromObject($articleList);
	}

	if ($request->GetProperty('ArticleFilter')['SimilarList'] == 'Y'){
        $content->SetLoop("ArticleList", $articleList->getSimilarList());
    }
	else{
	    if ($agent){
            $articleFilter = $request->GetProperty('ArticleFilter');
            $articleFilter['AuthorID'] = $agentAuthor->GetProperty('AuthorID');
            $request->SetProperty('ArticleFilter', $articleFilter);
        }

        $articleList->load($request, 20, true);
        $content->LoadFromObjectList("ArticleList", $articleList);

        $urlFilter->SetProperty('ArticleFilter', $request->GetProperty('ArticleFilter'));
        $content->SetVar("Paging", $articleList->GetPagingAsHTML($moduleURL.'&'.$urlFilter->GetForURL()));
        $urlFilter->RemoveProperty('ArticleFilter');
    }

	if ($request->IsPropertySet('ArticleFilter')){
		$content->SetVar('ArticleFilter', true);
		$content->LoadFromArray($request->GetProperty('ArticleFilter'));
	}

	$content->SetVar("ListInfo", GetTranslation('list-info1', array('Page' => $articleList->GetItemsRange(), 'Total' => $articleList->GetCountTotalItems())));
	$content->SetVar('BaseURL', $moduleURL.'&Section=' . $urlFilter->GetProperty('Section'));
}

if ($agent){
    //without similar, statuses and other
    $content->SetVar('EditorMode', true);
}