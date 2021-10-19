<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");
es_include('filesys.php');

function createLink($id){
    $url = GetUrlPrefix() . "vuzi/?universityID={$id}";
    return "<a href='{$url}' target='_blank'>[ссылка]</a>";
}

$import = new Import\UniversityImport();
if ($import->setImportFile(__DIR__ . '/source/univers_spbgu.csv') === false) {
    echo $import->getErrors();
    exit;
}

while (($data = $import->getNext()) !== false) {

    if ($data[0] == 'id вуза') {
        /*
         * Это строка с заголовками, пропускаем ее
         */
        continue;
    }

    if ($id = $import->findUniversityByImportID($data[0])) {
        $import->update($id);
        $link = createLink($id);
        print_r($data[0]." - update by import ID {$link}<br/>");
    } else {
        $id = $import->findUniversityByTitle(
            $import->value('Название'),
            $import->value('Аббревиатура')
        );

        if ($id) {
            $link = createLink($id);
            $import->update($id);
            print_r($data[0]." - update by title {$link}<br/>");
        } else {
            if ($id = $import->insert()){
                $link = createLink($id);
                print_r($data[0]." - insert new {$link}<br/>");
            }
        }
    }

}

$import->uniqStaticPath();