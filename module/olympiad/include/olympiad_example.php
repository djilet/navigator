<?php

namespace Module\Olympiad;


class OlympiadExampleFile
{
    protected $fileName;
    protected $filePath;

    public function initFile($olympiadID, $classNumber, $extension){
        $fileName = $olympiadID . '_' . $classNumber . '.' . $extension;
        if (file_exists(OLYMPIAD_DIR . 'examples/' . $fileName)){
            $this->filePath = OLYMPIAD_DIR . 'examples/' . $fileName;
            $this->fileName = $fileName;
            return true;
        }

        return false;
    }

    public function getLink(){
        return OLYMPIAD_DIR_URL . 'examples/' . $this->fileName;
    }

    public function save(){

    }

    public function remove(){

    }
}