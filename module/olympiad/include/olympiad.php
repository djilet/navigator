<?php

namespace Module\Olympiad;

require_once dirname(__FILE__) . '/class_number.php';
require_once dirname(__FILE__) . '/profile.php';
require_once dirname(__FILE__) . '/olympiad_example.php';
require_once dirname(__FILE__) . '/../../data/include/public/Region.php';

class Olympiad extends \LocalObject
{
    const PREPARE_DATE = 'PrepareDate';
    const PREPARE_REGION = 'PrepareRegion';
    const PREPARE_CLASS_NUMBER = 'PrepareClassNumber';
    const PREPARE_EXAMPLE_FILES = 'PrepareExample';

    protected static $classNumber;
    protected static $region;

    protected $prepare;

    public function __construct(
        $prepareFields = [self::PREPARE_DATE, self::PREPARE_REGION, self::PREPARE_CLASS_NUMBER, self::PREPARE_EXAMPLE_FILES]
    )
    {
        parent::LocalObject();

        $this->prepare = $prepareFields;

        if ($this->prepareIt(self::PREPARE_CLASS_NUMBER)){
            if (is_null(self::$classNumber)){
                self::$classNumber = new ClassNumber();
                self::$classNumber->assocList = self::$classNumber->getAssocStaticList(ClassNumber::ID_KEY);
            }
        }

        if ($this->prepareIt(self::PREPARE_REGION)){
            if (is_null(self::$region)){
                self::$region = new \Region();
                self::$region->assocList = self::$region->getAssocStaticList(\Region::ID_KEY);
            }
        }
    }

    public function prepareIt($key){
        if (in_array($key, $this->prepare)){
            return true;
        }

        return false;
    }

    public function load($id){
        $query = "SELECT olymp.*, olymp2class.ClassIDs
                  FROM olympiad_olympiad AS olymp
                  LEFT JOIN (
                        SELECT OlympiadID, GROUP_CONCAT(ClassID) AS ClassIDs FROM olympiad_olympiad2class GROUP BY OlympiadID
                    ) AS olymp2class ON olymp.OlympiadID = olymp2class.OlympiadID
                  WHERE olymp.OlympiadID = " . intval($id);

        $this->LoadFromSQL($query);
        $this->prepareForTemplate();
    }

    public function prepareForTemplate(){
        /**
         * Create  template list by key from group ID
         * @example [RegionIDs => '1,3']
         * @param $key
         * @param \TemplateListInterface $staticList
         * @param null $cb
         */
        $fn = function($key, \TemplateListInterface $staticList, $cb = null){
            $list = array();
            if ($this->IsPropertySet($key . 'IDs')){
                foreach (explode(',', $this->GetProperty($key . 'IDs')) as $i => $id) {
                    if (isset($staticList->assocList[$id])){
                        $list[] = $staticList->assocList[$id];
                    }
                }
                if (!empty($list)){
                    $result = $staticList->getListForTemplate(array(), $list);
                    if (is_callable($cb)){
                        call_user_func($cb, $result);
                    }
                    $this->SetProperty($key . 'NameList', $result);
                }
            }
        };

        if ($this->prepareIt(self::PREPARE_DATE)){
            //date registration
            $dateFields = [
                'RegistrationFrom',
                'RegistrationTo',
                'QualifyingFrom',
                'QualifyingTo',
                'FinalFrom',
                'FinalTo',
            ];

            foreach ($dateFields as $index => $key) {
                $date = new \DateTime($this->GetProperty($key));
                $this->SetProperty($key . 'Day', $date->format('d'));
                $this->SetProperty($key . 'Mount', GetTranslation("date-".$date->format('F')));
            }
        }

        if ($this->prepareIt(self::PREPARE_CLASS_NUMBER)){
            //Class
            $fn('Class', self::$classNumber, function($result) {
                $classes = array();
                $current = 0;
                $str = '';
                foreach ($result as $index => $item) {
                    $classes[$item['Name']] = $item['Name'];
                }

                for ($i = 0; $i <= max($classes); $i++){
                    if (empty($classes[$i])){
                        continue;
                    }

                    if ($classes[$i + 1] == $classes[$i] + 1){
                        if ($current > 0){
                            continue;
                        }
                        else{
                            $str .= $classes[$i] . '-';
                            $current++;
                        }
                    }
                    else{
                        $str .= $classes[$i];
                        if (($i + 1) < max($classes)){
                            $str .= ', ';
                        }
                        $current = 0;
                    }
                }
                $this->SetProperty('ClassInfo', $str);
            });
        }

        if($this->prepareIt(self::PREPARE_REGION)){
            //TODO find better the realization
            //Region
            if ($this->GetProperty('RegionIDs') == 'All'){
                $this->SetProperty('RegionNameList', [['Name' => 'Вся Россия']]);
            }
            else{
                $fn('Region', self::$region);
            }
        }

        if ($this->prepareIt(self::PREPARE_EXAMPLE_FILES)){
            $classList = $this->GetProperty('ClassNameList');

            foreach ($classList as $index => $item) {
                $exampleFile = new OlympiadExampleFile();
                if ($exampleFile->initFile($this->GetProperty('OlympiadID'), $item['Id'], 'pdf')){
                    $classList[$index]['ExampleFileLink'] = $exampleFile->getLink();
                }
            }

            $this->SetProperty('ClassNameList', $classList);
        }
    }

    public static function getIdByStaticPath($mainID, $path){
        $query = "SELECT OlympiadID
                  FROM olympiad_olympiad
                  WHERE MainID = " . intval($mainID) . "
                  AND StaticPath = " . \Connection::GetSQLString($path);

        return GetStatement()->FetchField($query);
    }
}