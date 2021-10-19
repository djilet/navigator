<?php

class AbTestDetector extends LocalObject
{
    const BASETEST_LANDING = 'BasetestSignIn';
    const BASETEST_BANNERS = 'BasetestBanners';

    const VARIANT_A = 'A';
    const VARIANT_B = 'B';
    const VARIANTS = [self::VARIANT_A, self::VARIANT_B];


    public static function IsVariantA($testName){
        if (self::getFromSession($testName) == self::VARIANT_A){
            return true;
        }

        return false;
    }

    public static function saveForUser($userID, array $abTest){
        $stmt = GetStatement();
        $values = array();
        $ids = array();
        $names = self::getTestIDsByNames(array_keys($abTest));

        //remove
        foreach ($names as $index => $item) {
            $ids[] = $item['TestID'];
        }

        $query = "DELETE FROM abtest_test2user WHERE UserItemID = " . intval($userID) . " AND TestID IN (" . implode(',', $ids) . ")";
        $stmt->Execute($query);

        //save
        foreach ($abTest as $name => $variant) {
            $values[] = '(' . $userID . ', ' . $names[$name]['TestID'] . ', ' . Connection::GetSQLString($variant) . ')';
        }

        $query = "INSERT INTO abtest_test2user (UserItemID, TestID, Variant) VALUES " . implode(', ', $values);
        $stmt->Execute($query);

        //echo 'save in db <br>';
        return true;
    }

    protected static function getFromSession($testName){
        $session = GetSession();
        $user = array();
        if ($session->IsPropertySet('UserItem')){
            $user = $session->GetProperty('UserItem');
            //echo 'isset user item <br>';
        }

        $abTest = $session->GetProperty('AbTest');
        if (!isset($abTest[$testName])){
            if (isset($user['UserID']) && $user['UserID'] > 0 ){
                if ($abTest = self::getFromDB($user['UserID'])){
                    $session->SetProperty('AbTest', $abTest);
                    $session->SetProperty('AbTestSaved', true);
                    $session->SaveToDB();
                }
            }

            if (!isset($abTest[$testName])){
                $abTest[$testName] = self::getLessVariant($testName);

                if (isset($user['UserID']) && $user['UserID'] > 0 && self::saveForUser($user['UserID'], $abTest)){
                    $session->SetProperty('AbTestSaved', true);
                }

                $session->SetProperty('AbTest', $abTest);
                $session->SaveToDB();
                //echo 'save to session<br>';
            }
        }

        //echo 'load from session <br>';

        return $abTest[$testName];
    }

    protected static function getFromDB($userID){
        $stmt = GetStatement();
        $abTest = array();

        $query = "SELECT test.Name, test2user.Variant FROM `abtest_test2user` AS test2user
                LEFT JOIN abtest_test AS test ON test2user.TestID = test.TestID
                WHERE test2user.UserItemID = " . intval($userID);

        if ($result = $stmt->FetchList($query)){
            foreach ($result as $key => $item) {
                $abTest[$item['Name']] = $item['Variant'];
            }

            //echo 'load from db <br>';
            return $abTest;
        }

        return false;
    }

    protected static function getTestIDsByNames(array $names, Statement $stmt = null){
        if ($stmt == null){
            $stmt = GetStatement();
        }

        $query = "SELECT * FROM abtest_test WHERE Name IN (" . implode(', ', Connection::GetSQLArray($names)) . ")";
        $result = $stmt->FetchIndexedAssocList($query, 'Name');
        //echo 'test id by names <br>';
        return $result;
    }

    protected static function getLessVariant($testName){
        //echo 'variant less';
        $stmt = GetStatement();
        $testID = array_shift(self::getTestIDsByNames([$testName]))['TestID'];
        $query = "SELECT IF(ACount > BCount, 'B', 'A') FROM `abtest_test` WHERE TestID = " . $testID;
        if ($variant = $stmt->FetchField($query)){
            $variantName = $variant . 'Count';
            $query="UPDATE abtest_test SET " . $variantName . " = " . $variantName . " + 1 WHERE TestID = " . $testID;
            $stmt->Execute($query);
            return ($variant === 'A' ? self::VARIANT_A : self::VARIANT_B);
        }

        return false;
    }
}