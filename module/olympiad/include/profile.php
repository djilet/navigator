<?php

namespace Module\Olympiad;

es_include('interfaces/template_list_Interface.php');
es_include('interfaces/template_list_methods.php');
es_include('interfaces/static_list_interface.php');
es_include('interfaces/static_list_methods.php');

class Profile extends \LocalObjectList implements \TemplateListInterface, \StaticListInterface
{
    use \TemplateListMethods;
    use \StaticListMethods;

	protected $module;
	protected $stmt;

	const ID_KEY = 'ProfileID';
	const NAME_KEY = 'Name';

	public function __construct($module = 'olympiad')
	{
		parent::LocalObjectList();
		$this->module = $module;
		$this->stmt = GetStatement();
	}

	public function load(){
        $query = "SELECT * FROM olympiad_profile";
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