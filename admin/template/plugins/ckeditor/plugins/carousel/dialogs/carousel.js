CKEDITOR.dialog.add( 'CarouselDialog', function( editor ) {
    return {
        title: 'Вставить карусель',
        minWidth: 400,
        minHeight: 200,

        contents: [
            {
                id: 'basic',
                elements: [
                    {
                        type: 'text',
                        id: 'count',
                        label: 'Количество слайдов',
                        'default': 'sd',
                        validate: function() {
                            const value = parseInt(this.getValue());
                            if (value > 10){
                                alert('Максимально можно добавить 10 слайдов');
                                return false;
                            }
                        },
                        setup: function(element) {
                            this.setValue(element.getText());
                        },
                        commit: function(element) {
                            let html = '';
                            for (let i = 0; i < this.getValue(); i++){
                                html += '<div class="item"></div>';
                            }

                            element.appendHtml(html);
                        }
                    }
                ]
            }
        ],
        
        onShow: function() {
            let selection = editor.getSelection();
            let element = selection.getStartElement();

            if ( element) {
                let exists = element.getAscendant('div', true);
                if(exists && exists.getAttribute("data-carousel-widget")){
                    element = exists;
                }
            }

            if ( !element || !element.getAttribute("data-carousel-widget")) {
                element = editor.document.createElement('div');
                element.addClass('carousel-widget');
                element.setAttribute('data-carousel-widget', true);
                this.insertMode = true;
            } else {
                this.insertMode = false;
            }

            this.element = element;
            this.setupContent(this.element);
        },

        onOk: function() {
        	this.commitContent(this.element);
            if ( this.insertMode ) {
                editor.insertElement(this.element);
            }
        }
    };
});