<?php

use Module\Tracker\AnalyticSystem;

require_once(dirname(__FILE__)."/user.php");
require_once(dirname(__FILE__)."/user_list.php");
require_once(dirname(__FILE__)."/question.php");
require_once(dirname(__FILE__)."/question_list.php");
require_once(dirname(__FILE__)."/question_result.php");

class BaseTest
{
	protected $module;
	protected $testUserQuestionList;

	public function __construct($module = 'basetest'){
		$this->module = $module;
	}

//Get
	public function getTestUser($pageID, $testUserID = null){
		$testUser = new BaseTestUser($this->module);

		if ($testUserID < 1){
			$testUserID = $testUser->init($pageID);
		}

		$testUser->load($testUserID);

		return $testUser;
	}

	/**
	 * Get active BaseTestUser, if not, it create
	 * @param $userID
	 * @return BaseTestUser
	 */
	public function getActiveTestUserByUserID($pageID, $userID){
		$testUser = new BaseTestUser($this->module);

		//Get active testUser
		$session = GetSession();
		$testUserID = $testUser->getActiveIDByUserID($userID);

        $sessionTestUserID = 0;
		if ($session->IsPropertySet('BaseTestUser')){
            $sessionTestUser = $session->GetProperty('BaseTestUser');
            $sessionTestUserID = $sessionTestUser['BaseTestUserID'];
        }

        if ($testUserID < $sessionTestUserID){
            if ($testUser->load($testUserID)){
                $this->reset($testUser);
            }

            $testUser->load($sessionTestUserID);
            $testUser->SetProperty('UserID', $userID);
            $testUser->save();
        }
        else{
            if ($testUserID < 1){
                $testUserID = $testUser->init($pageID, $userID);
            }

            $testUser->load($testUserID);
        }



		return $testUser;
	}

	/**
	 * Get active BaseTestQuestionResult, if not, it create
	 * @param $testUserID
	 * @param null $questionID
	 * @return BaseTestQuestionResult
	 */
	public function getQuestionResult($testUserID, $questionID = null){
		$questionResult = new BaseTestQuestionResult();
		$id = 0;

		if (intval($questionID) > 0){
			if (!$id = $questionResult::getIdByFields($testUserID, $questionID)){
				$id = $questionResult->init($testUserID, $questionID);
			}
		}
		else{
			if (!$questionResult->loadActive($testUserID)){
				if ($nextQuestion = BaseTestQuestion::getNotInit($testUserID)){
					$id = $questionResult->init($testUserID, $nextQuestion['QuestionID']);
				}
			}
		}

		if ($id > 0){
			$questionResult->load($id);
		}

		return $questionResult;
	}

	public function getQuestion($questionID){
		$question = new BaseTestQuestion();
		$question->load($questionID);
		return $question;
	}

	public function saveQuestionResult($questionResultID, $answers){
		$questionResult = new BaseTestQuestionResult();
		$questionResult->load($questionResultID);

		$questionResult->SetProperty('Answers', $answers);
		$questionResult->SetProperty('Status', $questionResult::STATUS_COMPLETE);
		if ($questionResult->save()){
			return true;
		}

		return false;
	}

	public function reset(BaseTestUser $testUser){
		$testUser->SetProperty('Status', $testUser::STATUS_RESET);
		if ($testUser->save()){
			return true;
		}

		return false;
	}

	public function setCompleteTest(BaseTestUser $testUser){
		$testUser->SetProperty('CompleteDate', GetCurrentDateTime());
		if ($testUser->save()){
			return true;
		}

		return false;
	}

	public function saveFeedback(BaseTestUser $testUser, $rating, $message = null){
		$rating = intval($rating);
		if ($rating > 0){
			$testUser->SetProperty('FeedbackRating', $rating);
			if (!empty($message)){
				$testUser->SetProperty('FeedbackMessage', $message);
			}

			$testUser->save();

			//AnalyticSystem
			AnalyticSystem\Sender::sendEvent(AnalyticSystem\BaseSystem::EVENT_BASETEST_FEEDBACK,
				[
					'stars' => $rating,
					'comment' => (!empty($message) ? 1 : 0),
				]
			);
			//AnalyticSystem end
		}
	}

    public static function getOrderProfessionList($testUserID, LocalObject $request){
        require_once(dirname(__FILE__) . "/../../data/include/public/Professions.php");
        require_once(dirname(__FILE__) . "/../../data/include/profession.php");
        $stmt = GetStatement();
        $where = array();
        $join = array('LEFT JOIN `data_profession_industry` AS ind ON p.Industry=ind.IndustryID');

        $createQuery = function($testUserID, $join, $where){
            $query = "SELECT p.ProfessionID,
				(MIN(res_who.Position) + MIN(res_want.Position) + MIN(res_ind.Position)) AS PositionOrder
				FROM data_profession AS p
				
				LEFT JOIN data_profession2who AS prof_who ON p.ProfessionID = prof_who.ProfessionID
				LEFT JOIN (
					SELECT answer.ItemID, answer.Position FROM `basetest_result` AS result
					LEFT JOIN basetest_result_answers AS answer ON result.QuestionResultID = answer.QuestionResultID
					LEFT JOIN basetest_question AS quest ON result.QuestionID = quest.QuestionID
					WHERE result.BaseTestUserID = " . intval($testUserID) . " AND quest.DataTable = 'WhoWork'
				) AS res_who ON prof_who.WhoWorkID = res_who.ItemID
				
				
				LEFT JOIN data_profession2want AS prof_want ON p.ProfessionID = prof_want.ProfessionID
				LEFT JOIN (
					SELECT answer.ItemID, answer.Position FROM `basetest_result` AS result
					LEFT JOIN basetest_result_answers AS answer ON result.QuestionResultID = answer.QuestionResultID
					LEFT JOIN basetest_question AS quest ON result.QuestionID = quest.QuestionID
					WHERE result.BaseTestUserID = " . intval($testUserID) . " AND quest.DataTable = 'WantWork'
				) AS res_want ON prof_want.WantWorkID = res_want.ItemID
				
				LEFT JOIN (
					SELECT answer.ItemID, answer.Position FROM `basetest_result` AS result
					LEFT JOIN basetest_result_answers AS answer ON result.QuestionResultID = answer.QuestionResultID
					LEFT JOIN basetest_question AS quest ON result.QuestionID = quest.QuestionID
					WHERE result.BaseTestUserID = " . intval($testUserID) . " AND quest.DataTable = 'Industry'
				) AS res_ind ON p.Industry = res_ind.ItemID
				" . (!empty($join) ? implode(' ', $join) : '') . "
        		" . ((count($where) > 0)?"WHERE ".implode(" AND ", $where) : "") . "
				GROUP BY p.ProfessionID
				ORDER BY PositionOrder, p.SortOrder ASC";

            //echo $query;
            return $query;
        };

        $query = call_user_func_array($createQuery, array($testUserID, $join, $where));
        if ($fullResult = $stmt->FetchList($query)){
            $professionList = array();

            $medals = array('Gold','Silver','Bronze');
            $medalItems = array();
            $i = 0;
            $el = 0;
            foreach ($fullResult as $index => $item) {
                if (!isset($medals[$el])){
                    break;
                }
                $medalItems[$item['ProfessionID']]['Medal'] = $medals[$el];
                $medalItems[$item['ProfessionID']]['Position'] = $index + 1;
                $i++;
                if ($i >= 3){
                    $i = 0;
                    $el++;
                }
            }

            if($request->IsPropertySet('ProfessionFilter')){
                $filter = $request->GetProperty('ProfessionFilter');
                Professions::prepareFilter($filter, $where);
                $query = call_user_func_array($createQuery, array($testUserID, $join, $where));
                $result = $stmt->FetchList($query);
            }
            else{
                $result = $fullResult;
            }

            if (!empty($result)){
                $profession = new DataProfession();

                foreach ($result as $index => $item) {
                    $professionList[$index] = $profession->getItemInfo($item['ProfessionID'],'',false);
                    if (isset($medalItems[$item['ProfessionID']])){
                        $professionList[$index]['Medal'] = $medalItems[$item['ProfessionID']]['Medal'];
                        $professionList[$index]['Position'] = $medalItems[$item['ProfessionID']]['Position'];
                    }
                }
            }

            return $professionList;
        }

        return false;
    }

    public static function getProfessionStatistic(){
        $request = new LocalObject();
        $userList = new BaseTestUserList();
        $request->SetProperty('Completed', 'Y');
        $userList->load($request, 0);
        $statistic = array();
        foreach ($userList->GetItems() as $index => $item) {
            $professions = self::getOrderProfessionList($item['BaseTestUserID'], $request);
            $professions = array_slice($professions, 0, 3);
            $point = 1;
            foreach ($professions as $key => $profession) {
                if (isset($statistic[$point][$profession['ProfessionID']])){
                    $statistic[$point][$profession['ProfessionID']]['Count']++;
                }
                else{
                    $statistic[$point][$profession['ProfessionID']]['Title'] = $profession['Title'];
                    $statistic[$point][$profession['ProfessionID']]['Count'] = 1;
                }

                $point++;
            }
        }

        $sortFn = function($a, $b)
        {
            if ($a['Count'] == $b['Count']) {
                return 0;
            }
            return ($a['Count'] < $b['Count']) ? 1 : -1;
        };

        foreach ($statistic as $index => $item) {
            usort($item, $sortFn);
            $statistic[$index] = $item;
        }

        return $statistic;
    }

//Check
	/*public function allQuestionResultCompleted($testUserID){
		$userCompletedCount = BaseTestQuestionResult::getCountCompleted($testUserID);
		$questionCount = BaseTestQuestion::getCountQuestions();

		if ($userCompletedCount >= $questionCount){
			return true;
		}

		return false;
	}*/
}