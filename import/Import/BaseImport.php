<?php

namespace Import;

abstract class BaseImport
{
	use Tools\Parsing;

    /** @var  string Путь до файла импорта */
    private $filepath;

    /** @var  resource Ресурс открытого файла импорта */
    private $fopen;

    /** @var array Отношение Имя поля => Номер колонки в csv */
    protected $map = [];

    /** @var string Разделитель полей */
    protected $delimiter = ';';

    /** @var string Разделитель текста */
    protected $enclosure = '"';

    /** @var  \Statement */
    protected $stmt;

    /** @var array */
    protected $errors = [];

    protected $row;

    /**
     * BaseImport constructor.
     */
    public function __construct()
    {
        $this->stmt = GetStatement();
    }


    /**
     * Устанавливает файл импорта
     *
     * @param $path string Файл импорта
     */
    public function setImportFile($path)
    {
        $this->filepath = $path;
        $this->checkPermissionFile();
    }

    /**
     * Проверка существования файла, а также необходимых прав на файл
     *
     * @return bool Может ли использоваться данный файл
     */
    private function checkPermissionFile()
    {
        if (! file_exists($this->filepath)) {
            $this->errors[] = "Файл импорта {$this->filepath} не найден";
            return false;
        }

        if (! is_readable($this->filepath)) {
            $this->errors[] = "Нет прав на чтение файла {$this->filepath}";
            return false;
        }

        $this->fopen = fopen($this->filepath, 'r');
        if (! is_resource($this->fopen)) {
            $this->errors[] = "Не удалось открыть файл {$this->filepath}";
            return false;
        }

        return true;
    }

    public function getNext()
    {
        $this->row = fgetcsv($this->fopen, 0, $this->delimiter, $this->enclosure);
        return $this->row;
    }

    public function field($name, $sanitizer = null, $escape = true)
    {
        if (empty($this->row) or !isset($this->map[$name]) or !isset($this->row[$this->map[$name]])) {
            $value = "";
        }
        else {
            $value = trim($this->row[$this->map[$name]]);
        }

        
        if ($sanitizer) {
            $value = call_user_func_array($sanitizer, [$value]);
        }

        if ($escape) {
            $value = \Connection::GetSQLString($value);
        }

        return $value;
    }

    public function value($name, $sanitize = null)
    {
        return $this->field($name, $sanitize, false);
    }

    protected function floatval($str)
    {
        return (float)$str;
    }

    protected function rmDoubleSpace($str)
    {
        return preg_replace('/\s+/u', ' ', $str);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return implode('<br>', $this->errors);
    }


    public function __destruct()
    {
        if (is_resource($this->fopen)) {
            fclose($this->fopen);
        }
    }

    public function toStart()
    {
        if (is_resource($this->fopen)) {
            fseek($this->fopen, 0);
        }
    }

    public function printMapInfo(){
        $data = $this->getNext();
        $filedNames = array();

        //Map fields
        foreach ($this->map as $key => $index) {
            $filedNames[] = $key . ' - ' . $data[$index] . '(' . $index . ')';
            unset($data[$index]);
        }
        echo "Map:\n";
        print_r($filedNames);

        //other fileds
        if (!empty($data)){
            echo "\nNot specified fields:\n";
            $fields = [];
            foreach ($data as $index => $field){
                $fields[] = $field . '(' . $index . ')';
            }

            print_r($fields);
        }
    }

    public function printRepeatedRows(array $index){
        $result = array();
        while ($row = $this->getNext()){
            $str = '';
            foreach ($index as $key => $val) {
                $str .= $row[$val] . ';';
            }

            $result[$str]++;
        }

        arsort($result);
        print_r($result);
    }

    abstract public function insert();
    abstract public function update($id);
}
