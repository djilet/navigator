class AnalyticsSystem {
    constructor(){
        //this.Ym = yaCounter47132955;
        this.Ga = 'gtag_UA_111538395_1';
        this.Am = amplitude;
        this.Systems = ['Ga','Am'];
        
        this.AmApiKey = amplitudeKey;
    }

    sendEvent(name, properties = null){
        if (this.Systems.includes('Ym')){
            this.Ym.reachGoal(name);
        }

        if (this.Systems.includes('Ga')){
            ga(this.Ga + '.send', 'event', properties.group, name);
        }

        if (this.Systems.includes('Am')){
            this.Am.getInstance().init(this.AmApiKey);
            if (user_id > 0) {
                this.Am.getInstance().setUserId(user_id);
            }
            
            if (device_id.length > 0) {
                this.Am.getInstance().setDeviceId(device_id);
            }

            this.Am.getInstance().logEvent(name, properties);
        }
    }
}


$(document).ready(function () {
    //Articles
    $('.other-articles .item a').click(function(event) {
        let params = {};
        let eventName;
        let item = $(this).closest('.item');
        let title = item.find('.title');
        let articleTitle = $('.article__headblock .header-section');

        params.name = $.trim(title.text());
        params.article = $.trim(articleTitle.text());


        if (item.closest('.other-articles').hasClass('best')) {
            eventName = 'related_articles_best';
        }
        else{
            eventName = 'related_articles_similar';
            params.position = item.index() + 1;
        }

        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(eventName, params);
    });

    //new comment from vk
    VK.Observer.subscribe("widgets.comments.new_comment", function(handler){
        let article = $('#page-single-article');
        if (article.length){
            let as = new AnalyticsSystem();
            as.sendEvent(
                'new_article_comment',
                {
                    articleID : article.data('id'),
                }
            );
        }
    });

//Social widget
    /*$('.social-widget#widget-vk').iframeTracker({
        blurCallback: function(event){
            let as = new AnalyticsSystem();
            as.sendEvent(
                'vk_widget',
                {
                    group : 'social'
                }
            );
        }
    });

    $('.social-widget#widget-fb').iframeTracker({
        blurCallback: function(event){
            let as = new AnalyticsSystem();
            as.sendEvent(
                'fb_widget',
                {
                    group : 'social'
                }
            );
        }
    });

    $('.social-widget#widget-ig').iframeTracker({
        blurCallback: function(event){
            let as = new AnalyticsSystem();
            as.sendEvent(
                'ig_widget',
                {
                    group : 'social'
                }
            );
        }
    });*/

//Sign (in|up)
    $('#checkin-form').submit(function(event) {
        //TODO normal init
        yaCounter47132955.reachGoal('Click_registration2');
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'signup_button_clicked',
            {
                group : 'Sign(in|up)'
            }
        );
    });

    $('#signin-form').submit(function(event) {
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'login_button_clicked',
            {
                group : 'Sign(in|up)'
            }
        );
    });

//Base test
    //result item
    $('body').on('click', '.base-test .professions .information-list .item:not(.active)', function(event) {
        let item = $(this);
        let medals = ['Gold', 'Silver', 'Bronze'];
        var medal = '';

        for (var i = 0; i < medals.length; i++) {
            if (item.hasClass(medals[i])) {
                medal = medals[i];
            }
        }

        if (medal.length < 1) {
            medal = 'Other';
        }

        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'basetest_results_clicks',
            {
                profession_medal: medal,
                profession_name: $.trim(item.find('.content > .title .name').text()),
            }
        );
    });

    //where learn
    $('body').on('click', '.base-test .professions .information-list .item #where-learn-btn', function(event) {
        let item = $(this.closest('.item'));
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'basetest_results_clicks',
            {
                where_to_learn: $.trim(item.find('.content > .title .name').text()),
            }
        );
    });

    //result filter
    $('.base-test .checkbox-filter .checkbox a').on('change', function(event) {
        if (!$(this).next('input').is(':disabled')) {
            let as = new AnalyticsSystem();
            as.Systems = ['Am'];
            as.sendEvent(
                'basetest_results_filters',
                {
                    filter_name: $(this).prop('innerText'),
                    filter_category: $(this).closest('.checkbox-filter-block').children('.title').prop('innerText'),
                }
            );
        }
    });

    //result load more
    $('#load-more-profession').click(function(event) {
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'basetest_results_clicks',
            {
                show_more: true,
            }
        );
    });

    //reset test
    $('#reset-test-btn').click(function(event) {
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'basetest_results_clicks',
            {
                do_it_again: true,
            }
        );
    });

//Banners
    $('#base-test-landing-btn').click(function(event) {
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];
        as.sendEvent(
            'basetest_landing',
            {
                banner_name: 'top_button',
                from: getCurrentModuleUrl(),
            }
        );
    });

    let bannerLinks = $('.banner a');
    bannerLinks.click(function() {
        let linkName = $(this).data('name');
        let as = new AnalyticsSystem();

        as.Systems = ['Am'];
        as.sendEvent(
            'click_banner',
            {
                name: linkName,
                from: getCurrentModuleUrl(),
            }
        );
    });
    if (bannerLinks.length){
        let as = new AnalyticsSystem();
        as.Systems = ['Am'];

        bannerLinks.each(function () {
            if ($(this).outerWidth() > 0 || $(this).outerHeight() > 0){
                let linkName = $(this).data('name');
                as.sendEvent(
                    'show_banner',
                    {
                        name: linkName,
                    }
                );
            }
        });
    }

    //Forms
    $('.registration-exhibition-form').submit(function (event) {
        let count = $(this).find('.block-registration').length;
        for (let i = 0; i < count; i++){
            fbq('track', 'CompleteRegistration');
        }
    })
});

