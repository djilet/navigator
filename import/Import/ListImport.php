<?php

namespace Import;

require_once(dirname(__FILE__)."/../../module/data/include/admin/list.php");
require_once(dirname(__FILE__)."/../../module/data/include/admin/list_list.php");

class ListImport extends BaseImport
{
    protected $map = [
        'Название' => 0,
        'ТипСписка' => 1,
        'РегионID' => 2,
        'Ссылка текущая' => 3,
        'Описание' => 4,
        'МетаЗаголовок' => 5,
        'МетаОписание' => 6,
        'URL' => 7,
    ];

    protected $oldListListByTitle;

    protected $stmt;

    public function __construct(){
        parent::__construct();
        $this->stmt = GetStatement();

        $this->oldListListByTitle = $this->stmt->FetchIndexedAssocList(
            'SELECT l.ListID, l.Title, l.Description	 FROM `data_list` AS l',
            'Title'
        );
    }

    public function  findByTitle(){
        if (isset($this->oldListListByTitle[$this->value('Название')])){
            return $this->oldListListByTitle[$this->value('Название')]['ListID'];
        }

        return false;
    }

    public function insert()
    {

    }

    public function update($id)
    {
        $item = new \LocalObject();
        $item->SetProperty('ListID', $id);
        $item->SetProperty('Title', $this->value('Название'));
        $item->SetProperty('Description', $this->value('Описание'));
        $item->SetProperty('Type', 'Filter');
        $item->SetProperty('StaticPath', $this->value('URL'));
        $item->SetProperty('MetaTitle', $this->value('МетаЗаголовок'));
        $item->SetProperty('MetaDescription', $this->value('МетаОписание'));
        $item->SetProperty('Public', 'N');

        $filter = [$this->value('РегионID')];
        $item->SetProperty('FilterRegion', $filter);

        $dataList = new \DataList();
        $dataList->LoadFromObject($item);
        if ($dataList->Save()){
            if ($id > 0){
                $message = 'update - ' . $id;
            }
            else{
                $message = 'insert - ' . $this->value('Название');
            }
        }
        else{
            $message = ' error - ' . $this->value('Название');
        }

        echo $message . '<br>';
    }
}