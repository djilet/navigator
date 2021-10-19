CKEDITOR.dialog.add( 'selectArticleDialog', function( editor ) {
	var items = [];
	globalArticleList.forEach(function(e) {
		items.push([e.label, e.value]);
	});
	
    return {
        title: 'Вставка статьи',
        minWidth: 400,
        minHeight: 200,
        onOk: function (){
            var articleID = $('#article-select').val();
            this.element.setAttribute('data-article', articleID);

            globalArticleList.forEach((e) => {
                if(e.value === articleID){
                    this.element.setHtml('Вставка статьи - ' + e.label);
                }
            });

            this.commitContent( this.element );
            if ( this.insertMode ) {
                editor.insertElement(this.element);
            }
        },
        contents: [
            {
                id: 'basic',
                elements: [
                    {
                        type: 'html',
                        setup: function (el){
                            var control = $('#article-select');
                            control.selectpicker();
                        },
                        html: `<select class="selectpicker" data-live-search="true" id="article-select">
                                    ${items.map((item) => `<option value="${item[1]}">${item[0]}</option>`)}
                                </select>
                               `,
                    }
                ]
            }
        ],
        
        onShow: function() {
            var selection = editor.getSelection();
            var element = selection.getStartElement();
            
            if ( element) {
                var exists = element.getAscendant('div', true);
                if(exists && exists.getAttribute("data-article")){
                	element = exists;
                }
            }
            
            if ( !element || !element.getAttribute("data-article")) {
                element = editor.document.createElement( 'div' );
                element.addClass('article-inline');
                this.insertMode = true;
            } else {
                this.insertMode = false;
            }
            
            this.element = element;
            this.setupContent(this.element);
        },
    };
});