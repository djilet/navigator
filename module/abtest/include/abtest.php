<?php

class AbTest extends LocalObjectList
{
    public function load(){
        $query = "SELECT * FROM abtest_test";
        $this->LoadFromSQL($query);
    }
}