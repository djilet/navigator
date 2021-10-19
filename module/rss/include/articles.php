<?php
require_once(dirname(__FILE__)."/../../data/include/Articles.php");
require_once(dirname(__FILE__)."/../../question/include/message_list.php");

class ArticlesRSS extends BaseRSS
{
    protected $name = 'articles';
    protected $items = array();
    protected $similarArticles = array();

    public function load()
    {
        $this->_properties['ChannelTitle'] = 'Статьи';
        $this->_properties['ChannelLink'] = 'https://propostuplenie.ru/article/';
        $this->_properties['ChannelDescription'] = '';
    }

    public function loadItems($header = null){
        $articles = new Articles();
        $questionMessageList = new QuestionMessageList("question");
        $request = New LocalObject();
        $articles->load($request, 0);
        //similar
        $this->similarArticles = $articles->getSimilarList();

        foreach ($articles->GetItems() as $index => $item) {
            $info = $articles->getItemInfo($item['ArticleID']);

            //date
            $info['DateTime'] = date(DateTime::RFC822, strtotime($info['DateTime']));

            //comments
            $questionMessageList->load(new LocalObject(array(
                "Type" => "article",
                "AttachID" => $item['ArticleID'],
                "Status" => "public"
            )));
            $info['QuestionMessageList'] = $questionMessageList->GetItems();

            //similar
            $info['SimilarArticles'] = $this->similarArticles;

            //banners
            $info['BannerTopSm'] = $header['BannerTopSm'];

            $articleDom = new DomDocument('1.0', 'UTF-8');
            $articleDom->loadHTML("\xEF\xBB\xBF" . $info['Content'],LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $paragraphs = $articleDom->getElementsByTagName('p');

            if ($paragraphs->length > 0 && !empty($header['BannerSidebar'])) {
                $bannerContent = "
                <a id='banner' href='{$header['BannerSidebar'][0]['Link']}'>
                    <img src='{$header['BannerSidebar'][0]['ItemImageFullPath']}'>
                </a>";
                $banner = $articleDom->createElement("p", $bannerContent);
                $targetNode = $paragraphs->item($paragraphs->length / 2);
                $targetNode->parentNode->insertBefore($banner, $targetNode);
            }

            $info['Content'] = html_entity_decode($articleDom->saveHTML());

            $this->items[] = $info;
        }

        //print_r($this->items);
    }

    public function getItems($header = null){
        $this->loadItems($header);
        return $this->items;
    }

    public function prepareTemplate(Template &$content, $header){
        $this->Load();
        $content->LoadFromObject($this);
        $content->SetVar('ListRSS',$this->getName());
        $content->SetVar('XmlVersion', $this->getVersion());
        $content->SetLoop('Xmlns', $this->getXmlns());

        //items
        $content->SetLoop('Items', $this->getItems($header));

        $content->SetVar('HostURL', GetCurrentProtocol().$_SERVER['HTTP_HOST']);
    }
}