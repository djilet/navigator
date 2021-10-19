<?php

class OpenDayList extends LocalObjectList
{
    const MODULE_NAME = 'data';
    const EVENT_TYPE = 'dod';

    protected static $params;
    protected $now;

    public static function getAll(array $filter = null, $order = ['Date ASC'], int $onPage = 40)
    {
        $query = QueryBuilder::init()
            ->select([
                'od.*',
                'city.Title AS CityTitle',
                'COUNT(reg.RegistrationID) as DeviceStatusCount'
            ])
            ->from(OpenDay::TABLE_NAME . ' AS od')
            ->addJoin('LEFT JOIN data_open_day_registration AS reg ON od.ID = reg.EventID')
            ->addJoin('LEFT JOIN data_city AS city ON od.CityID = city.ID')
            ->group(['od.ID']);

        if (!empty($filter)){
            if (isset($filter['Active'])){
                $query->addWhere("Active = '{$filter['Active']}'");
            }
            if (!empty($filter['DateGte'])){
                $query->addWhere("Date >= '{$filter['DateGte']}'");
            }

            if (!empty($filter['DateLt'])){
                $query->addWhere("Date < '{$filter['DateLt']}'");
            }

            if (!empty($filter['CityIDs'])){
                $cityIDs = implode(", ", Connection::GetSQLArray($filter['CityIDs']));
                $query->addWhere("od.CityID IN ({$cityIDs})");
            }

            if (!empty($filter['UniversityID'])){
                $query->addJoin('LEFT JOIN data_open_day2university AS od2un ON od.ID = od2un.OpenDayID');
                $query->addWhere("od2un.UniversityID='{$filter['UniversityID']}'");
            }
        }

        //TODO order
        $query->order($order);

        //echo $query->getSQL();exit();

        $item = new static();
        $item->SetItemsOnPage($onPage);
        $item->SetCurrentPage();
        $item->LoadFromSQL($query->getSQL());
        return $item;
    }

    /**
     * OpenDayList constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::LocalObjectList();
        $this->now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
    }

    /**
     * @param int $daysLimit
     * @param int $inDayLimit
     * @throws Exception
     */
    public function prepareForEventList($daysLimit = 0, $inDayLimit = 0)
    {
        $typeTitle = GetTranslation('online-event-' . self::EVENT_TYPE, self::MODULE_NAME);

        if (!empty($this->_items)) {
            $result = array();
            $fmt = new IntlDateFormatter(
                'ru_RU',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Europe/Moscow',
                IntlDateFormatter::GREGORIAN
            );

            foreach ($this->_items as $item) {
                $date = new DateTime($item['Date'], new DateTimeZone('Europe/Moscow'));
                $day = $date->format('d-m-Y');
                if (!isset($result[$day])) {
                    if($daysLimit && count($result) >= $daysLimit) break;
                    $fmt->setPattern('EEEE');
                    $weekStr = $fmt->format($date);
                    $fmt->setPattern('d MMMM');
                    $dateStr = $fmt->format($date);
                    $result[$day] = array(
                        'Date'         => $dateStr,
                        'DayOfTheWeek' => $weekStr,
                        'Day'          => $date->format('d'),
                        'Month'        => GetTranslation("date-".$date->format('F')),
                        'Children'     => array(),
                        '__ROWNUM__'   => count($result) + 1
                    );
                }

                if($inDayLimit && count($result[$day]['Children']) >= $inDayLimit) continue;

                //rename for list
                $item['EventDateTime'] = $item['Date'];
                $item['EventType'] = self::EVENT_TYPE;
                $item['TypeTitle'] = $typeTitle;


                $dateFrom = new DateTime($item['DateFrom'], new DateTimeZone('Europe/Moscow'));
                $item['StartDateUTC'] = $dateFrom->format("Y-m-d H:i:s e");

                $dateTo = new DateTime($item['DateTo'], new DateTimeZone('Europe/Moscow'));

                $item['EndDateUTC'] = $dateTo->format("Y-m-d H:i:s e");

                if ($date < $this->now) {
                    $item['isFinished'] = 1;
                    $fmt->setPattern('d MMMM Y');
                    $item['FinishedDate'] = $fmt->format($date);
                    /*$item['inProgress'] = 1;
                    $item['Progress'] = (($this->now->format('U') - $date->format('U')) * 100) / $duration;
                    $item['Progress'] = number_format($item['Progress'], 2, '.', '');
                    $fmt->setPattern('d MMMM в HH:mm');
                    $item['ProgressDate'] = $fmt->format($date);

                    $diff = $this->now->diff($date);
                    $item['ProgressTime'] = $diff->format('%H:%I:%S');*/
                }

                //$item['TimePrefix'] = $this->getDayPrefix($date);

                $fmt->setPattern('d MMMM в HH:mm');
                $item['OnlineEventDateTitle'] = $fmt->format($date);

                $result[$day]['Children'][] = $item;
            }

            $this->_items = array_values($result);
        }
    }

    public static function remove($listId)
    {
        if (! is_array($listId) or empty($listId)) {
            return false;
        }

        $query = QueryBuilder::init()
            ->delete()
            ->from(OpenDay::TABLE_NAME)
            ->addWhere('ID IN (' . implode(',', Connection::GetSQLArray($listId)) . ')');

        return GetStatement()->Execute($query->getSQL());
    }
}