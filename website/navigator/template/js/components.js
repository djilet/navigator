class AjaxRequester {
    static send(url, data, cbDone = null, cbFail = null, type = 'POST', dataType = 'json'){
        $.ajax({
            url: url,
            type: type,
            dataType: dataType,
            data: data,
        })
        .done(function(response) {
            if (typeof DEV_MODE !== 'undefined' && DEV_MODE){
                //console.log('done');
            }
            if (typeof cbDone  == 'function') {
                cbDone(response);
            }
        })
        .fail(function(response) {
            if (typeof DEV_MODE !== 'undefined' && DEV_MODE){
                console.log(response.responseText);
                $('body').append(response.responseText);
            }
            //console.log("error");
        });
    }
}


class AutoSaveForm {
    constructor(form){
        this.form = form;
        this.formID = form.attr('id');
        this.inputs = this.form.find('.form-control');
        this.restore();
    }

    init(){
        this.inputs.change(this.changeFn.bind(this));
    }

    changeFn(){
        let inputs = this.form.find('.form-control');
        let data = {};
        inputs.each(function () {
            data[$(this).attr('name')] = $(this).val();
        });
        this.save(data);
    }

    save(data){
        localStorage.setItem(this.formID, JSON.stringify(data));
    }

    restore(){
        let data = JSON.parse(localStorage.getItem(this.formID));
        for (let inputName in data) {
            let input = this.inputs.filter('[name="' + inputName +'"]');
            input.val(data[inputName]);
        }
    }
}

class InfiniteScrollPagination{
    constructor(pagination, paginationSelector) {
        this.block = pagination;
        this.eventName = 'scroll.InfScroll';
        this.contentSelector = this.block.data('content-selector');
        this.insertToSelector = this.block.data('insert-selector');
        this.scrollToSelector = this.block.data('scroll-selector');
        this.form = $('#' + this.block.attr('data-form'));
        this.paginationSelector = paginationSelector;
        this.setOutputArea();
    }

    setOutputArea(){
        //TODO replace it own method (copy from main.js:updateAjaxFormContent())
        let outputArea = this.form.next('.ajax-content'); //or
        if(outputArea.length == 0) outputArea = this.form.closest('.ajax-content'); //or
        if(outputArea.length == 0) outputArea = this.form.closest('.row').find('.ajax-content');
        this.outputArea = outputArea;
    }

    init(){
        $(document).unbind(this.eventName);
        this.setPageInForm(this.activePage.find('a').attr('data-page'));
        this.scrollToElement = this.outputArea.find(this.scrollToSelector);
        this.scrollToOffset = this.scrollToElement.offset();
        if (this.nextPage && this.scrollToOffset.top > 0 && this.block.offset().top > 0){
            $(document).bind(this.eventName, this.handler.bind(this));
        }
    }

    handler(event){
        let offsetTop = this.scrollToOffset.top;
        let scrollBottom = $(window).scrollTop() + $(window).height();

        if (scrollBottom > offsetTop){
            this.showNextPage();
            $(document).unbind(this.eventName);
        }
    }

    get activePage(){
        return this.block.find('.active');
    };

    get nextPage(){
        return this.activePage.next();
    };

    setPageInForm(val){
        this.form.find('.page').val(val);
    }

    showNextPage(){
        if (this.nextPage.length){
            this.setPageInForm(this.nextPage.find('a').attr('data-page'));
            AjaxRequester.send(
                this.form.attr('action'),
                this.form.serialize(),
                (data) => {
                    if (data && data.status && data.status === 'success' && data.html) {
                        let html = $(data.html);
                        let appendTo = (this.insertToSelector ? this.outputArea.find(this.insertToSelector) : this.outputArea);
                        let content = (this.contentSelector ? html.find(this.contentSelector) : html);

                        appendTo.append(content);
                        let newPagination = html.find(this.paginationSelector);
                        this.block.replaceWith(newPagination);
                        this.setPageInForm(newPagination.find('.active').find('a').attr('data-page'));
                        initInfinityScroll();
                    }
                }
            );
        }
    }
}

class CustomToolTip {
    static get templatePrototype(){
        return $(
            `<div class="custom-tooltip" style="display:none;">
                <div class="arrow">
                </div>
                <div class="content">
                </div>
            </div>
        `);
    }

    set content(content){
        this.block.find('.content').html(content);
    }

    get content(){
        return this.block.find('.content');
    }

    get width(){
        return this.block.width();
    }

    init(template = CustomToolTip.templatePrototype){
        this.block = template;
        $('body').append(this.block);
    }

    show(){
        this.block.fadeIn();
    }

    hide(){
        this.block.hide();
    }

    showUnderCursor(params){
        let {offset, coords} = params;
        this.block.css({
            left: coords.x - this.width/2,
            top: coords.y + (offset || 20),
        });
        this.show();
    }
}