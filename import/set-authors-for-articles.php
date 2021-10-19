<?php
require_once(dirname(__FILE__)."/../include/init.php");
require_once(__DIR__ . "/../module/data/include/admin/author_list.php");

$articlesCopy = GetStatement()->FetchList("SELECT * FROM data_article_copy WHERE Author IS NOT NULL");
$authorList = new DataAuthorList();
$author = new DataAuthor();

$authorList->LoadAuthorList();
$assocAuthorList = $authorList->getAssocItems('Title');

foreach ($articlesCopy as $articleCopy){
    $authorName = str_replace('ё', 'е', trim($articleCopy['Author']));
    if (!isset($assocAuthorList[$authorName])){
        $author->LoadFromArray([
            'Title' => $authorName
        ]);
        if ($author->Save()){
            $assocAuthorList[$authorName] = $author->GetProperties();
        }
        else{
            echo "error save author {$articleCopy['Author']} for {$articleCopy['ArticleID']} \n";
            continue;
        }
    }

    $authorID = $assocAuthorList[$authorName]['AuthorID'];
    $query = "UPDATE data_article SET AuthorID = {$authorID} WHERE ArticleID = {$articleCopy['ArticleID']}";
    if (!GetStatement()->Execute($query)){
        echo "error update article {$articleCopy['ArticleID']} \n";
        echo "{$query} \n";
    }

    echo "success {$articleCopy['ArticleID']} \n";
}
