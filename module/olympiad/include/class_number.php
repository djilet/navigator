<?php

namespace Module\Olympiad;

es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
es_include('interfaces/static_list_interface.php');
es_include('interfaces/static_list_methods.php');

class ClassNumber extends \LocalObjectList implements \TemplateListInterface, \StaticListInterface
{
    use \TemplateListMethods;
    use \StaticListMethods;

    const ID_KEY = 'ClassID';
    const NAME_KEY = 'ClassName';

    public function load(){
        $query = "SELECT * FROM olympiad_class";
        $this->LoadFromSQL($query);
    }


//Interfaces
    public function getListForTemplate(array $selected = array(), $items = null)
    {
        return $this->prepareFromKeysName(self::ID_KEY, self::NAME_KEY, $selected, $items);
    }

    public static function createStaticList()
    {
        self::createFromLocalObjectList();
    }

}