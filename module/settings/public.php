<?php
require_once(dirname(__FILE__) . "/init.php");
es_include("modulehandler.php");

class SettingsHandler extends ModuleHandler{
    protected $version;
    protected $env;
    protected $path2main;
    const DEV_MODE = 'dev';

	public $module = 'settings';

	public function __construct(){
        parent::ModuleHandler();

        //env
        if (GetFromConfig('DevMode', 'common') == 1){
            $this->env = self::DEV_MODE;
        }

        //path2main
        $this->path2main = PROJECT_PATH . 'website/' . WEBSITE_FOLDER . '/template/';

        //version
        $this->version = GetFromConfig('Version','common');
    }

    public function ProcessHeader($module, Page $page = null){
        $data = array();
        $pageModule = array();

        if (!is_null($page)){
            $pageModule = $page->GetProperty('Link');
        }

        $data['IncludeJS'] = $this->loadJS([$pageModule]);
        $data['IncludeCSS'] = $this->loadCss([$pageModule]);

        //VK api id
        $data['VkApiID'] = GetFromConfig('ApiID', 'vk');
        $data['RecaptchaSite'] = GetFromConfig('RecaptchaSite', 'google');

		return $data;
    }

    public function loadCss(array $modules){
	    $css = array();
        //path2main
        $path2pain = $this->path2main;
        if ($this->env !== self::DEV_MODE){
            $path2pain .= 'dist/css/';
        }
        else{
            $path2pain .= 'css/source/';
        }

        //Fonts
        $css[] = ['Path' => 'https://fonts.googleapis.com/css?family=Roboto+Slab:700|Roboto:400,500&amp;subset=cyrillic'];
        $css[] = ['Path' => 'https://fonts.googleapis.com/css?family=Russo+One&amp;subset=cyrillic'];
        $css[] = ['Path' => 'https://fonts.googleapis.com/css?family=Exo+2:400,500,600,700,800,900&amp;subset=cyrillic'];
        $css[] = ['Path' => 'https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800'];
        $css[] = ['Path' => 'https://fonts.googleapis.com/icon?family=Material+Icons'];
        
        //libs
        if ($this->env == self::DEV_MODE){
            $css[] = ['Path' => $this->path2main . 'css/libs/bootstrap-select.css'];
            $css[] = ['Path' => $this->path2main . 'css/libs/bootstrap-multiselect.css'];
            $css[] = ['Path' => $this->path2main . 'css/libs/owl.carousel.css'];
            $css[] = ['Path' => $this->path2main . 'css/libs/bootstrap.min.css'];
            $css[] = ['Path' => $this->path2main . 'css/libs/simplelightbox.min.css'];
            $css[] = ['Path' => $this->path2main . 'css/libs/jquery.datetimepicker.css'];
        }
        else{
            $css[] = ['Path' => $path2pain . 'libs.css?ver=' . $this->version];
        }

        //styles
        $css[] = ['Path' => $path2pain . 'general.css?ver=' . $this->version];
        $css[] = ['Path' => $path2pain . 'main.css?ver=' . $this->version];
        $css[] = ['Path' => $path2pain . 'form.css?ver=' . $this->version];
        $css[] = ['Path' => $path2pain . 'basetest.css?ver=' . $this->version];

        //for modules
        foreach ($modules as $index => $module){
            switch ($module){
                case 'marathon':
                case 'proftest':
                    $css[] = ['Path' => 'https://fonts.googleapis.com/css?family=Montserrat:400,500,600,700&amp;subset=cyrillic'];
                    $css[] = ['Path' => $path2pain . 'marathon.css?ver=' . $this->version];
                    break;
            }
        }

        return $css;
    }

    public function loadJS(array $modules){
        //path2main
        $path2pain = $this->path2main;
        if ($this->env !== self::DEV_MODE){
            $path2pain .= 'dist/js/';
        }
        else{
            $path2pain .= 'js/';
        }

        $js = [
            //libs
            ['Path' => $path2pain . 'jquery-1.12.4.min.js'],
            ['Path' => $path2pain . 'bootstrap.min.js'],
            ['Path' => $path2pain . 'bootstrap-select.js'],
            ['Path' => $path2pain . 'bootstrap-multiselect.js'],
            ['Path' => $path2pain . 'jquery.countdown.min.js'],
            ['Path' => $path2pain . 'owl.carousel.min.js'],
            ['Path' => $path2pain . 'moment.js'],
            ['Path' => $path2pain . 'moment-timezone-with-data.js'],
            ['Path' => $path2pain . 'jquery.mask.js'],
            ['Path' => $path2pain . 'sticky-kit.js'],
            /*['Path' => $path2pain . 'jquery.iframetracker.min.js'],*/
            ['Path' => $path2pain . 'linkify/linkify.min.js'],
            ['Path' => $path2pain . 'linkify/linkify-jquery.min.js'],
            ['Path' => $path2pain . 'simple-lightbox.min.js'],
            ['Path' => $path2pain . 'jquery.datetimepicker.full.js'],
            ['Path' => 'https://vk.com/js/api/openapi.js?160'],

            //scripts
            ['Path' => $path2pain . 'components.js?ver=' . $this->version],
            ['Path' => $path2pain . 'analytics-systems.js?ver=' . $this->version],
            ['Path' => $path2pain . 'form.js?ver=' . $this->version],
            ['Path' => $path2pain . 'main.js?ver=' . $this->version],
            ['Path' => $path2pain . 'chat.js?ver=' . $this->version],
        ];

        //for modules
        foreach ($modules as $index => $module) {
            switch ($module){
                case 'marathon':
                case 'proftest':
                    $js[] = ['Path' => $path2pain . 'marathon.js?ver=' . $this->version];
                    break;

                case 'basetest':
                    $js[] = ['Path' => 'https://code.jquery.com/ui/1.12.0/jquery-ui.min.js'];
                    $js[] = ['Path' => $path2pain . 'jquery.ui.touch-punch.min.js'];
                    $js[] = ['Path' => $path2pain . 'base-test.js?ver=' . $this->version];
                    break;
            }
        }

        return $js;
    }
}