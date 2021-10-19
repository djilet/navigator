<?php
/**
 * Date:    22.12.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace Import\Tools;

class Area
{
    private $stmt;

    private $areas;

    /**
     * Area constructor.
     *
     * @param \Statement $stmt
     */
    public function __construct(\Statement $stmt)
    {
        $this->stmt = $stmt;
        $this->areas = $this->stmt->FetchIndexedAssocList(
            'SELECT `AreaID`,`Title` FROM `data_area`',
            'Title'
        );
    }


    public function getId($title)
    {
        if (!isset($this->areas[$title])) {
            $this->insert($title);
        }

        return $this->areas[$title]['AreaID'];
    }

    private function insert($title)
    {
        $sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_area`');
        if (!$sortOrder) {
            $sortOrder = 0;
        }

        $query = "INSERT INTO `data_area` SET
                      `Title` = " . \Connection::GetSQLString($title) . ",
                  `AreaImage` = NULL,
            `AreaImageConfig` = NULL,
                  `SortOrder` = " . $sortOrder;
        if ($this->stmt->Execute($query)) {
            $this->areas[$title] = [
                'Title'  => $title,
                'AreaID' => $this->stmt->GetLastInsertID(),
            ];
        }
    }
}
