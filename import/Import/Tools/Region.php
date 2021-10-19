<?php

namespace Import\Tools;

class Region
{
    private $stmt;
    private $regions;

    /** @var \Import\Tools\Area */
    private $area;

    /**
     * Region constructor.
     *
     * @param \Statement $stmt
     */
    public function __construct(\Statement $stmt)
    {
        $this->stmt = $stmt;
        $this->area = new Area($stmt);
        $this->regions = $this->stmt->FetchIndexedAssocList(
            'SELECT `RegionID`,`Title` FROM `data_region`',
            'Title'
        );
    }


    public function getId($title, $area, $saveNew = true)
    {
		if (!isset($this->regions[$title])) {
			if ($saveNew == true){
				$this->insert($title, $area);
			}
			else{
        		//TODO Error list
			}
        }

        return $this->regions[$title]['RegionID'];
    }

    private function insert($title, $area)
    {
        $areaId = $this->area->getId($area);
        $sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_region` WHERE `AreaID`=' . $areaId);
        if (!$sortOrder) {
            $sortOrder = 0;
        }

        $query = "INSERT INTO `data_region` SET
                       `AreaID` = " . $areaId . ",
                        `Title` = " . \Connection::GetSQLString($title) . ",
                  `RegionImage` = NULL,
            `RegionImageConfig` = NULL,
                    `SortOrder` = " . $sortOrder;
        if ($this->stmt->Execute($query)) {
            $this->regions[$title] = [
                'Title'    => $title,
                'RegionID' => $this->stmt->GetLastInsertID(),
            ];
        }
    }
}
