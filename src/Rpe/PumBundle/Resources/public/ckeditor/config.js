/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';
    config.toolbar = [
        { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source', '-', 'Save'/*, 'NewPage', 'Preview', 'Print', '-', 'Templates'*/ ] },
        { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
        { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Find', 'Replace', '-', /*'SelectAll',*/ '-', 'Scayt' ] },
        { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
        { name: 'paragraphA', groups: [ 'indent', 'align', 'bidi' ], items: [ 'Outdent', 'Indent', '-', /*'Blockquote', 'CreateDiv',*/ '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'/*, '-', 'BidiLtr', 'BidiRtl', 'Language'*/ ] },
        //{ name: 'belin', items: [ 'Belin' ] },
        '/',
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
        { name: 'paragraphB', groups: [ 'list', 'blocks' ], items: [ 'NumberedList', 'BulletedList', '-', /*'Outdent', 'Indent', '-',*/ 'Blockquote', /*'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language'*/ ] },
        { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
        { name: 'insert', items: [ 'oembed', 'Image', 'Table', 'HorizontalRule', 'SpecialChar', /*'PageBreak',*/ 'Iframe', 'Googledocs', 'EqnEditor', 'gg' ] },
        { name: 'styles', items: [ /*'Styles', 'Format', 'Font',*/ 'FontSize' ] },
        { name: 'colors', items: [ 'TextColor', 'BGColor' ] }
        // { name: 'others', items: [ '-' ] }
    ];

    config.extraPlugins            = 'autosave';
    //config.extraPlugins            = 'belin';
    config.autosave_SaveKey        = 'viaeduc_post_autosave';
    config.autosave_NotOlderThen   = 720;

    config.filebrowserBrowseUrl    = '/ajax-library';
    config.filebrowserWindowWidth  = '950';
    config.filebrowserWindowHeight = '480';
  /*  config.coreStyles_italic =
    {
        element : 'span',
        attributes : { 'class' : 'italic' }
    };*/
};
