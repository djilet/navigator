<?php

class QueryBuilder
{
    protected $select;
    protected $insert;
    protected $update;
    protected $delete = null;
    protected $setValues = [];
    protected $from;
    protected $join = [];
    protected $where = [];
    protected $group;
    protected $having = [];
    protected $order;
    protected $limit;

    public function delete($arg = ''){
        $this->delete = $arg;
        return $this;
    }

//Insert
    public function insert(string $arg):self {
        $this->insert = $arg;
        return $this;
    }

    public function setValue(string $field, $value):self{
        $this->setValues[] = $field . ' = ' . $value;
        return $this;
    }

    public function update(string $arg):self{
        $this->update = $arg;
        return $this;
    }

//Select
    public function select(array $arg):self {
        $this->select = $arg;
        return $this;
    }

    public function addSelect(string $arg):self {
        $this->select[] = $arg;
        return $this;
    }

    public function from(string $arg):self {
        $this->from = $arg;
        return $this;
    }

    public function join(array $arg):self {
        $this->join = $arg;
        return $this;
    }

    public function addJoin(string $arg):self {
        $this->join[] = $arg;
        return $this;
    }

    public function where(array $arg):self {
        $this->where = $arg;
        return $this;
    }

    public function addWhere(string $arg):self {
        $this->where[] = $arg;
        return $this;
    }

    public function group(array $arg):self {
        $this->group = $arg;
        return $this;
    }

    public function having(array $arg):self {
        $this->having = $arg;
        return $this;
    }

    public function addHaving(string $arg):self {
        $this->having[] = $arg;
        return $this;
    }

    public function order(array $arg):self {
        $this->order = $arg;
        return $this;
    }

    public function limit(int $arg):self {
        $this->limit = $arg;
        return $this;
    }

    public static function init():self {
        return new self();
    }

    public function getSQL():string {
        $op = '';

        if (!is_null($this->select)){
            $op = "SELECT \n  " . implode(",\n  ", $this->select);
        }
        elseif (!is_null($this->insert)){
            $op = "INSERT INTO " . $this->insert;
        }
        elseif (!is_null($this->update)){
            $op = "UPDATE " . $this->update;
        }
        elseif (!is_null($this->delete)){
            $op = "DELETE ";
        }

        return (
            $op .
            (!empty($this->from) ? "\nFROM " . $this->from : "") .
            (!empty($this->setValues) ? "\n SET " . implode(",\n ", $this->setValues) : '') .
            (!empty($this->join) ? "\n" . implode("\n", $this->join) : '') .
            (count($this->where) > 0 ? "\nWHERE \n  " . implode("\nAND\n  ", $this->where) : "") .
            (!empty($this->group) ? "\nGROUP BY \n  " . implode(", ", $this->group) : '') .
            (count($this->having) > 0 ? "\nHAVING \n  " . implode("\nAND\n  ", $this->having) : "") .
            (!empty($this->order) ? "\nORDER BY \n  " . implode(", ", $this->order) : '') .
            (!empty($this->limit) ? "\nLIMIT " . $this->limit : '')
        );
    }
}