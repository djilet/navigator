<?php
require_once(dirname(__FILE__)."/articles.php");

abstract class BaseRSS extends LocalObject
{
    protected $xmlns = [
        [
            'attribute' => 'turbo',
            'value' => 'http://turbo.yandex.ru',
        ],
    ];

    protected $version = '2.0';
    protected $name = '';

    abstract public function load();
    abstract public function getItems();

    public function __construct($name){
        parent::LocalObject();
        $this->name = $name;
    }

    public function getName(){
        return $this->name;
    }

    public function getXmlns(){
        return $this->xmlns;
    }

    public function getVersion(){
        return $this->version;
    }

    public static function getInstance($listName){
        switch ($listName){
            case 'articles':
                return new ArticlesRSS($listName);
            break;

            default:
                return false;
            break;
        }
    }
}