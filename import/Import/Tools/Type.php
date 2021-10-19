<?php

namespace Import\Tools;

class Type
{
    private $stmt;
    private $types;

    /**
     * Region constructor.
     *
     * @param \Statement $stmt
     */
    public function __construct(\Statement $stmt)
    {
        $this->stmt = $stmt;
        $this->types = $this->stmt->FetchIndexedAssocList(
            'SELECT `TypeID`,`Title` FROM `data_type`',
            'Title'
        );
    }


    public function getId($title)
    {
        if (! isset($this->types[$title])) {
            $this->insert($title);
        }

        return $this->types[$title]['TypeID'];
    }

    private function insert($title)
    {
        $sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_type`');
        if (! $sortOrder) {
            $sortOrder = 0;
        }

        $query = "INSERT INTO `data_type` SET
                      `Title` = ".\Connection::GetSQLString($title).",
                  `TypeImage` = NULL,
            `TypeImageConfig` = NULL,
                  `SortOrder` = " . $sortOrder;
        if ($this->stmt->Execute($query)) {
            $this->types[$title] = [
                'Title' => $title,
                'TypeID' => $this->stmt->GetLastInsertID()
            ];
        }
    }
}
