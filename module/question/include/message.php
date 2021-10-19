<?php
require_once dirname(__FILE__) . '/../../users/include/user.php';
require_once __DIR__ . '/../../data/include/public/University.php';
es_include("user.php");

class QuestionMessage extends LocalObject
{
    var $module;
    const TYPE_ARTICLE = 'article';
    const TYPE_UNIVERSITY = 'university';

    function __construct($module = 'data', $data = array())
    {
        parent::LocalObject($data);
        $this->module = $module;
    }

    public static function get(int $id)
    {
        $query = QueryBuilder::init()
            ->select([
                'message.*',
            ])
            ->from("question_message AS message")
            ->where([
                "message.MessageID = {$id}"
            ]);

        $item = new static();
        $item->LoadFromSQL($query->getSQL());
        if ($item->GetIntProperty('MessageID') > 0){
            return $item;
        }

        return null;
    }

    public function loadFromRequest(LocalObject $request, UserItem $user = null, int $authorId = null){
        if (is_null($user)){
            $user = new UserItem();
            $user->loadBySession();
        }

        $this->SetProperty('Type', $request->GetProperty('Type'));
        $this->SetProperty('AttachID', $request->GetIntProperty('AttachID'));
        $this->SetProperty('Status', 'public');
        $this->SetProperty('Text', $request->GetProperty('Text'));
        $this->SetProperty('ParentID', $request->GetProperty('ParentID'));
        if($request->GetProperty('Type') == 'university'||
            $request->GetProperty('Type') == 'speciality' ||
            $request->GetProperty('Type') == 'college' ||
            $request->GetProperty('Type') == 'collegeSpeciality'){
            $university = new University();
            $universityData = $university->getByID($request->GetIntProperty('AttachID'));
            if(($universityData && $universityData['QuestionUserID'] == $user->GetProperty('UserID')) ||
                intval(GetFromConfig('DefaultUserID', 'question')) == $user->GetIntProperty('UserID')){
                $this->SetProperty('Colored', 'Y');
            }
            $this->SetProperty('ShortTitle', $universityData['ShortTitle']);
            $this->SetProperty('AnswerURL', $universityData['UniversityURL']."#tab-4");
        }
        if ($user->GetIntProperty('UserID') > 0){
            $this->SetProperty('UserID', $user->GetProperty('UserID'));
            $this->SetProperty('UserCommentsStatus', $user->GetProperty('CommentsStatus'));
        }
        else{
            $this->SetProperty('AnonUserName', $request->GetProperty('AnonUserName'));
        }

        if ($authorId > 0){
            $this->SetProperty('Colored', 'Y');
            $this->SetProperty('AuthorID', $authorId);
        }
    }

    function Save()
    {
        $result = $this->Validate();
        if (!$result)
        {
            return false;
        }

        if($this->GetProperty("Colored") != "Y")
            $this->SetProperty("Colored", "N");

        $stmt = GetStatement();

        $query = "INSERT INTO `question_message` SET
        ParentID=".$this->GetPropertyForSQL("ParentID").", 
        Type=".$this->GetPropertyForSQL("Type").", 
        AttachID=".$this->GetPropertyForSQL("AttachID").", 
        AuthorID=".$this->GetPropertyForSQL("AuthorID").",
        AnonUserName=".$this->GetPropertyForSQL("AnonUserName").",
        UserID=".$this->GetPropertyForSQL("UserID").", 
        Status=".$this->GetPropertyForSQL("Status").",
        Text=".$this->GetPropertyForSQL("Text").",
        Colored=".$this->GetPropertyForSQL("Colored").",
        Created=".Connection::GetSQLString(GetCurrentDateTime());

        if ($stmt->Execute($query))
        {
            if (!$this->GetIntProperty("MessageID") > 0)
                $this->SetProperty("MessageID", $stmt->GetLastInsertID());

            //email notification to administrator
            $template = new Page();
            $sendTo = null;
            if($template->LoadByStaticPath("question-message-notification"))
            {
                $userItem = new UserItem($this->module);
                $userItem->loadByID($this->GetIntProperty("UserID"));
                $content = $template->GetProperty("Content");
                $content = str_replace("[UserName]", $userItem->GetProperty('UserName'), $content);
                $attachID = $this->GetProperty('AttachID');
                if ($this->GetProperty('Type')=='university'){
                    $university = new University();
                    $universityData = $university->getByID($attachID);
                    if (!empty($universityData['QuestionUserID'])){
                        $userItem->loadByID($universityData['QuestionUserID']);
                        $sendTo = $userItem->GetProperty('UserEmail');
                    }

                    $content = str_replace("[Link]", '/'.DATA_PROFESSION_PAGE_UNIVERSITY.'/?universityID='.$attachID, $content);
                } elseif ($this->GetProperty('Type')=='college'){
                    $content = str_replace("[Link]", '/'.$this->GetProperty('Type').'/?CollegeID='.$attachID, $content);
                } else if ($this->GetProperty('Type')=='article') {
                    $content = str_replace("[Link]", '/'.$this->GetProperty('Type').'/?ArticleID='.$attachID, $content);
                } else if ($this->GetProperty('Type')=='speciality') {
                    $content = str_replace("[Link]", '/university?specialityID='.$attachID, $content);
                } else if ($this->GetProperty('Type')=='collegeSpeciality') {
                    $content = str_replace("[Link]", '/college?CollegeSpecialityID='.$attachID, $content);
                }
                $content = str_replace("[Text]", $this->GetProperty('Text'), $content);
                $content = str_replace("[Created]", GetCurrentDateTime(), $content);
                if (!empty($sendTo)){
                    SendMailFromAdmin($sendTo, "Навигатор поступления: новое сообщение в вопрос-ответ", $content);
                }
                //SendMailFromAdmin("alexandr.oshcipov@maximumtest.ru", "Навигатор поступления: новое сообщение в вопрос-ответ", $content);
                //SendMailFromAdmin("alina.gilmutdinova@maximumtest.ru", "Навигатор поступления: новое сообщение в вопрос-ответ", $content);
                //SendMailFromAdmin("anna.gagarina@maximumtest.ru", "Навигатор поступления: новое сообщение в вопрос-ответ", $content);
                //SendMailFromAdmin("anna.podobrazhnykh@maximumtest.ru", "Навигатор поступления: новое сообщение в вопрос-ответ", $content);
            }

            //email notification for answer
            if($this->GetProperty("ParentID") && $this->GetProperty("ShortTitle") && $this->GetProperty("AnswerURL"))
            {
                $query = "SELECT m.UserID, u.UserEmail, u.UserName FROM `question_message` m LEFT JOIN `users_item` u ON m.UserID=u.UserID WHERE m.MessageID=".$this->GetIntProperty("ParentID");
                $userInfo = $stmt->FetchRow($query);
                if(!empty($userInfo['UserEmail']))
                {
                    $template = new Page();
                    if($template->LoadByStaticPath("question-message-answer"))
                    {
                        $content = $template->GetProperty("Content");
                        $content = str_replace("[UserName]", $userInfo["UserName"], $content);
                        $content = str_replace("[ShortTitle]", $this->GetProperty("ShortTitle"), $content);
                        $content = str_replace("[AnswerURL]", "https://propostuplenie.ru/university".$this->GetProperty("AnswerURL"), $content);
                        SendMailFromAdmin($userInfo["UserEmail"], "Навигатор поступления: новый ответ на вопрос", $content);
                    }
                }
            }

            return true;
        }
        else
        {
            $this->AddError("sql-error");
            return false;
        }
    }

    function Validate()
    {
        if(!$this->ValidateNotEmpty("Type"))
            $this->AddError("message-type-empty", $this->module);
        if(!$this->ValidateNotEmpty("AttachID"))
            $this->AddError("message-attach-empty", $this->module);
        if(!$this->ValidateNotEmpty("Status"))
            $this->AddError("message-status-empty", $this->module);
        if(!$this->ValidateNotEmpty("Text"))
            $this->AddError("message-text-empty", $this->module);
        if(!$this->ValidateNotEmpty("UserID") && !$this->ValidateNotEmpty("AuthorID") && !$this->ValidateNotEmpty('AnonUserName'))
            $this->AddError("message-name-empty", $this->module);


        return !$this->HasErrors();
    }
}

