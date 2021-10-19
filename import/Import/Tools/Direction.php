<?php
/**
 * Date:    26.12.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace Import\Tools;

class Direction
{
    private $stmt;
    private $direction;
    private $bigDirection;
    
    /**
     * Region constructor.
     *
     * @param \Statement $stmt
     */
    public function __construct(\Statement $stmt)
    {
        $this->stmt = $stmt;
        $this->direction = $this->stmt->FetchIndexedAssocList(
            'SELECT `DirectionID`,`Title` FROM `data_direction`',
            'Title'
        );
        $this->bigDirection = $this->stmt->FetchIndexedAssocList(
            'SELECT `BigDirectionID`,`Title` FROM `data_bigdirection`',
            'Title'
        );
    }


    public function getId($title, $bigTitle)
    {
        if (! isset($this->direction[$title])) {
            $oldTitle = preg_replace('/([\d.]+\s)/ui', '', $title);

            if (isset($this->direction[$oldTitle])) {
                $this->update($this->direction[$oldTitle]['DirectionID'], $title, $oldTitle);
            } else {
                $this->insert($title, $bigTitle);
            }
        }

        return $this->direction[$title]['DirectionID'];
    }

    private function insert($title, $bigTitle)
    {
        if (!isset($this->bigDirection[$bigTitle])) {
            //create big direction if not exists
            $sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_bigdirection`');
            if (! $sortOrder) {
                $sortOrder = 0;
            }
            $query = "INSERT INTO `data_bigdirection` SET
                      `Title` = ".\Connection::GetSQLString($bigTitle).",
                  `SortOrder` = " . $sortOrder;
            if ($this->stmt->Execute($query)) {
                $this->bigDirection[$bigTitle] = [
                    'Title' => $bigTitle,
                    'BigDirectionID' => $this->stmt->GetLastInsertID()
                ];
            }
        }
        
        $sortOrder = $this->stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_direction`');
        if (! $sortOrder) {
            $sortOrder = 0;
        }

        $query = "INSERT INTO `data_direction` SET
                      `Title` = ".\Connection::GetSQLString($title).",
             `BigDirectionID` = ".\intval($this->bigDirection[$bigTitle]['BigDirectionID']).",
                  `SortOrder` = " . $sortOrder;
        if ($this->stmt->Execute($query)) {
            $this->direction[$title] = [
                'Title' => $title,
                'DirectionID' => $this->stmt->GetLastInsertID()
            ];
        }
    }

    private function update($id, $title, $oldTitle)
    {
        $query = "UPDATE `data_direction` SET
                      `Title` = ".\Connection::GetSQLString($title).'
                      WHERE `DirectionID`='.intval($id);
        if ($this->stmt->Execute($query)) {
            $this->direction[$title] = [
                'Title' => $title,
                'DirectionID' => $id
            ];
            unset($this->direction[$oldTitle]);
        }
    }

    public function uniqStaticPath(){
        if ($result = $this->stmt->FetchList("SELECT GROUP_CONCAT(DirectionID) AS DirectionIDs, StaticPath, COUNT(StaticPath) FROM `data_direction` GROUP BY StaticPath HAVING COUNT(StaticPath) > 1")){
            foreach ($result as $key => $item) {
                $Ids = explode(',', $item['DirectionIDs']);
                foreach ($Ids as $index => $id) {
                    if ($index > 0){
                        $staticPath = $item['StaticPath'] . '-' . $index;
                        $query = "UPDATE data_direction
							  SET StaticPath = " . \Connection::GetSQLString($staticPath)
                            . " WHERE DirectionID = " . intval($id);
                        if (!$this->stmt->Execute($query)){
                            echo $query;
                            return false;
                        }
                    }

                }
            }
        }
    }
}