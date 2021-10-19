CKEDITOR.plugins.add( 'carousel', {
    init: function( editor ) {
        CKEDITOR.dialog.add( 'CarouselDialog', this.path + 'dialogs/carousel.js' );
    	editor.addCommand('InsertCarousel', new CKEDITOR.dialogCommand( 'CarouselDialog' ));
    	editor.ui.addButton( 'InsertCarousel', {
            label: 'Вставить карусель',
            command: 'InsertCarousel',
            icon: this.path + 'icons/collection.png',
            toolbar: 'insert'
        });
        
    }
});