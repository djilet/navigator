CKEDITOR.plugins.add( 'blockquote2', {
    init: function( editor ) {
    	editor.addCommand( 'blockquote2', {
        	exec: function(editor){
        		var selection = editor.getSelection();
                var element = selection.getStartElement();
                
                var exists = element.getAscendant('div', true);
                if(exists && exists.getAttribute("class") == 'blockquote2'){
                	var html = exists.getHtml();
                	exists.remove();
                	editor.insertHtml(html);
                }
                else {
                	var ranges = selection.getRanges();
                	var el = new CKEDITOR.dom.element("div");
                	for (var i = 0, len = ranges.length; i < len; ++i) {
                		el.append(ranges[i].cloneContents());
                	}
                	var node = CKEDITOR.dom.element.createFromHtml( '<div class="blockquote2">' + el.getHtml() + '</div>', editor.document);
                	editor.insertElement(node);
                }
            }
        });
        editor.ui.addButton( 'Blockquote2', {
            label: 'Цитирование',
            command: 'blockquote2',
            icon: this.path + 'icons/blockquote.png',
            toolbar: 'insert'
        });
        
    }
});