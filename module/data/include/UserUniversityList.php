<?php
/**
 * Class UserUniversityList
 */
class UserUniversityList extends LocalObjectList
{
    const TABLE_NAME = 'data_user_university';

    public static function where(array $filter = null): QueryBuilder
    {
        $query = QueryBuilder::init()
            ->select([
                'city.*',
            ])
            ->from(self::TABLE_NAME . ' AS user_university');

        if (!empty($filter)){
            if (!empty($filter['UniversityIds'])){
                $Ids = implode(", ", Connection::GetSQLArray($filter['UniversityIds']));
                $query->addWhere("user_university.UniversityID IN ({$Ids})");
            }

            if (!empty($filter['CreatedGte'])){
                $dateTime = Connection::GetSQLDateTime($filter['CreatedGte']);
                $query->addWhere("user_university.Created >= {$dateTime}");
            }

            if (!empty($filter['CreatedLt'])){
                $dateTime = Connection::GetSQLDateTime($filter['CreatedLt']);
                $query->addWhere("user_university.Created < {$dateTime}");
            }
        }

        return $query;
    }

    public static function withUser(QueryBuilder $query)
    {
        return $query->addJoin("LEFT JOIN users_item AS user ON user_university.UserID = user.UserID");
    }

    public static function withUniversity(QueryBuilder $query)
    {
        return $query->addJoin("LEFT JOIN data_university AS university ON user_university.UniversityID = university.UniversityID");
    }

    public static function withSpeciality(QueryBuilder $query)
    {
        return $query->addJoin("LEFT JOIN data_speciality AS speciality ON user_university.SpecialityID = speciality.SpecialityID");
    }

    public static function getAllWithDependents(array $filter = null, int $onPage = 40)
    {
        $query = self::withSpeciality(self::withUniversity(self::withUser(self::where($filter))));
        $query->select([
            'user_university.Created',
            'speciality.Title AS SpecialityTitle',
            'user.UserName',
            'user.UserEmail',
            'user.UserPhone',
            'user.UserWho',
            'user.ClassNumber',
            'user.City',
        ]);

        $item = new static();
        $item->SetItemsOnPage($onPage);
        $item->SetCurrentPage();
        $item->LoadFromSQL($query->getSQL());
        return $item;
    }
}