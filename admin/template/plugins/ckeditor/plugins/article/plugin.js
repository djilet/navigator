var globalArticleList = null;
$.ajax({
    url: "../module/data/ajax.php",
    type: "post",
    data: {"Action":"linked-article"},
    dataType: 'json',
    success: function (data) {
    	globalArticleList = data;
    }
});

CKEDITOR.plugins.add( 'article', {
    init: function( editor ) {
    	editor.addCommand( 'article', new CKEDITOR.dialogCommand( 'selectArticleDialog' ) );
        CKEDITOR.dialog.add( 'selectArticleDialog', this.path + 'dialogs/select.js' );
    	
    	editor.ui.addButton( 'InsertArticle', {
            label: 'Вставить статью',
            command: 'article',
            icon: this.path + 'icons/article.png',
            toolbar: 'insert'
        });
        
    }
});