$(document).ready(function(){
    // Var Params
    var draggable_options = {
        cursorAt: { left: 10 , top: 10},
        revert: 'invalid',
        containment: 'document',
        cursor: 'move',
        helper: function(event,ui){
            var li_drag = $(this).parents('li'),
                img = $(li_drag.html()).find('.img').clone().addClass('img_clone').appendTo(li_drag),
                w = img.width(),
                h = img.height();

            $(this).draggable( 'option', 'cursorAt').left = w/2;
            $(this).draggable( 'option', 'cursorAt').top = h/2;

            return img;
        },
        start: function(event, ui) {
            var li_drag = $(this).parents('li');
            li_drag.addClass('drag_selected');
            $('<div class="dark_layer"></div>').css({'width':li_drag.width()+'px','height':li_drag+'px'}).prependTo(li_drag);
            $('.library-right,.library-left').addClass('drag');
            $(this).draggable( 'option', 'cursorAt', { left: 5 } );
        },
        stop: function(event, ui) {
            var li_drag = $(this).parents('li');
            li_drag.removeClass('drag_selected');
            $('.dark_layer').remove();
            $('.library-right,.library-left').removeClass('drag');
            $('body').css('cursor','auto');
        }
    };
    var droppable_options = {
        accept: '.library-right ul li .move',
        hoverClass: 'drop_hover',
        drop: function( event, ui ) {

            var liDrag = $(ui.draggable.parents('li')),
                liDrop = $(this);

            var dropUrl = liDrag.data('dropurl');
            if (liDrop.data('entityid')) {
                dropUrl += '/' + liDrop.data('entityid');
            }

            $.ajax({
                url: dropUrl,
                type: 'GET'
            }).success(function(html) {
                if (html == 'OK') {
                    liDrag.remove();
                }
            }).fail(function() {

            });;

            // alert('Media '+li_drag.attr('id')+' dropped in folder '+li_drop.attr('id')+' !!');

        }
    };

    //filters
    // TODO
    // Vérifier que c'est dans le rpe.common.js
    $('#big').on('click',function(event){
        $('.library-right').addClass('big');
    });
    $('#small').on('click',function(event){
        $('.library-right').removeClass('big');
    });

    //drag
    function libraryInitDraggableMedias() {
        var movers = $( '.library-right ul li:not(.empty) .move');
        if (movers.length) {
            movers.draggable(draggable_options);
        }
    }
    $(document.body).on('js_autoload_xhr_success', '.library-right', function(ev){
        libraryInitDraggableMedias();
    });
    $(document.body).on('js_loadmore_xhr_success', '.library-left li.js-loadmore, .library-left .folder-name.js-loadmore', function(ev){
        libraryInitDraggableMedias();
    });


    //drop
    var droppers = $( '.library-left ul li' ),
        dropmode = false;
    if (droppers.length) {
        droppers.droppable(droppable_options);
        droppers.first().droppable('disable');
        dropmode = true;
    }

    //add folder
    $('.library-top .add-folder-button').on('click',function(event){

        if( $( '.add-folder-form-wrapper' ).hasClass('add') ){
            $(this).addClass('active');
            $( '.add-folder-form-wrapper' ).removeClass('add');
            $(this).removeClass('icon-minus');
            $(this).addClass('icon-plus');
            $(this).html('Ajouter');
        }
        else{
            $( '.add-folder-form-wrapper' ).addClass('add');
            $(this).removeClass('active');
            $(this).removeClass('icon-plus');
            $(this).addClass('icon-minus');
            $(this).html('Annuler');
        }
    });
    $('.library').on('submit', 'form.add-folder-form', function(ev){
        ev.preventDefault();

        var self = $(this);
        self.find('input, button').attr('readonly', true);

        var formObj  = $(this);
        var formURL  = formObj.attr("action");

        if( self.hasClass('active') ){
            return false;
        } else {
            self.addClass('active');

            $.ajax({
                url: formURL,
                type: 'POST',
                data:  self.serialize(),
            }).success(function(html) {
                // self.find('input[type=text]').val('');
                $('.library-left > ul').append(html);
                if (dropmode) {
                    $('.library-left li:not(ui-droppable)').droppable(droppable_options);
                }

                self.parents('.library-top').removeClass('add');
                $('.add-folder-button').removeClass('active');

                $('.library-top .add-folder-button').trigger('click');
                self.html('');
            }).fail(function() {

            });;
        }
    });

    //select folder
    $(document.body).on('click', '.library-left li:first-child, .library-left .folder-name', function(ev){
        var self = $(this),
            li = (self.hasClass('folder-name')) ? self.parents('li') : self,
            selected = $( '.library-left li.selected' );

            selected.removeClass('selected');
            li.addClass( 'selected' );

            $('#media_folder_id').val(self.attr('data-entityid'));

        if (dropmode) {
            selected.droppable('enable');
            li.droppable('disable');
        }
    });

    $(document.body).on('click', '.start-upload', function(ev){
        var selected = $( '.library-left li.selected' );
        $('#media_folder_id').val(selected.attr('data-entityid'));
    });

    //edit folders
    $(document.body).on('click', '.library-left ul li .edit', function(ev){
        var li = $(this).parents('li');

        $( '.library-left li.edition' ).removeClass('edition');
        li.addClass( 'edition' );
    });

    $('.library-left').on('submit', '.edit-input', function(ev){
        ev.preventDefault();

        var self = $(this);
        self.find('input, button').attr('readonly', true);

        var formObj  = $(this);
        var formURL  = formObj.attr("action");

        if( self.hasClass('active') ){
            return false;
        } else {
            self.addClass('active');

            $.ajax({
                url: formURL,
                type: 'POST',
                data:  self.serialize(),
            }).success(function(html) {
                // self.find('input[type=text]').val('');
                self.parents('li').replaceWith(html);
            }).fail(function() {

            });
        }
    });

    function getUrlParam( paramName ) {
        var reParam = new RegExp( '(?:[\?&]|&)' + paramName + '=([^&]+)', 'i' ),
            match = window.location.search.match(reParam) ;

        return ( match && match.length > 1 ) ? match[ 1 ] : null ;
    }
    /*// UPLOAD DOC POPIN TO CKEDITOR
    var results = getUrlParam( 'CKEditorFuncNum' );
    if (results != null) {
        $('.library-right').on('submit', '.edit-input', function(ev){
            ev.preventDefault();

            var self     = $(this),
                formURL  = self.attr("action"),
                formData = new FormData(this);

            $.ajax({ // SEND DATAS FORM
                url: formURL,
                type: 'POST',
                data:  formData,
                processData: false,
                contentType: false
            }).success(function(html) {
                $.ajax({ // GET MEDIA URL
                    url: '/ajax-library?CKEditor=resource_content&CKEditorFuncNum=1&langCode=fr',
                    type: 'POST'
                }).success(function(html) {
                    var idMedias = [];

                    $(html).find('li').each(function(){
                        idMedias.push($(this).attr('data-mediaid'));
                    });

                    idMedias = idMedias.sort(function (a, b) {
                        return a - b;
                    });

                    var lastIdMedias   = idMedias[idMedias.length-1],
                        lastMediasLink = $('#js-media_'+lastIdMedias, html),
                        fileUrl        = lastMediasLink.find('img').attr('src'),
                        funcNum        = getUrlParam( 'CKEditorFuncNum' );

                    window.opener.CKEDITOR.tools.callFunction( funcNum, fileUrl );
                    window.close();

                    $('.cke_dialog_footer').find('.cke_dialog_ui_hbox_first a').children().click();

                });
            });

            return false;
        });
    }*/

    //cancel edit folders
    $(document.body).on('click', '.library-left ul li .btn-cancel', function(ev){
        $(this).parents('form').html('<span class="loader"></span>');
        $( '.library-left li.edition' ).removeClass('edition');
    });

    //edit media
    $(document.body).on('click', '.library-right ul li .edit', function(ev){
        var li = $(this).parents('li');

        if( li.hasClass('edition') ){
            $( '.library-right li' ).removeClass('edition');
        }
        else{
            $( '.library-right li.edition' ).removeClass('edition');
            li.addClass('edition');
        }
    });
    $('[data-belin]').on("click", function(){
        $('.library_belin').show();
        $('.library-right ul').html("");
    });

     $(document.body).on('click', '.library-left [data-loadtarget]', function(){
        $('.library_belin').hide();
    });

    // notify that document is uploaded
    $(document).on('change', '.rpe-upload' , function(){
        var $textDiv = $(this).parent().find('.fileupload'),
            $button  = $(this).parent(),
            $submit_button = $('.library #media_submit');

        if($(this).val() != '') {
            if($(this).closest('form').find('.uploaded-files-wrapper.edit').length) {
                $textDiv.text('Document modifié');
            } else {
                $textDiv.text('Document ajouté');
            }
            $button.removeClass('orange').addClass('green');
            $submit_button.removeAttr('disabled');
        } else {
            $textDiv.text('Ajouter');
            $button.removeClass('green').addClass('orange');
            $submit_button.attr('disabled', 'disabled').html('Envoyer');
        }

        $('.uploaded-files-wrapper.edit').html('');

    });
    
    $(document).ajaxComplete(function(){
        if ($(".library-right .edit-input").find('input[type="text"]').val() != "") {
            $('.library #media_submit').removeAttr('disabled');
        }
    });

    $(document).ajaxComplete(function(){
        $('#fileupload').fileupload({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
        });

        $('#fileupload').bind('fileuploadadd', function (e, data) {
            console.log(e, data);
        });

        $('#fileupload').bind('fileuploaddone', function (e, data) {
            // UPLOAD DOC POPIN TO CKEDITOR
            var results = getUrlParam( 'CKEditorFuncNum' );
            if (results != null) {
                var formURL  = $(this).find(".library-right").data('autoload');

                $.ajax({ // SEND DATAS FORM
                    url: formURL,
                    type: 'POST',
                    processData: false,
                    contentType: false
                }).success(function(html) {
                    var idMedias = [];

                    $(html).find('li').each(function(){
                        idMedias.push($(this).attr('data-mediaid'));
                    });

                    // console.log( $(html).find('li') );
                    // console.log(idMedias);

                    idMedias = idMedias.sort(function (a, b) {
                        return a - b;
                    });

                    var lastIdMedias   = idMedias[idMedias.length-1],
                        lastMediasLink = $('#js-media_'+lastIdMedias, html),
                        fileUrl        = lastMediasLink.find('img').attr('src'),
                        funcNum        = getUrlParam( 'CKEditorFuncNum' );

                    window.opener.CKEDITOR.tools.callFunction( funcNum, fileUrl );
                    window.close();

                    $('.cke_dialog_footer').find('.cke_dialog_ui_hbox_first a').children().click();
                });
            }

        });
    });
    // Percentage Bar
    if($(document).find('.library-percentage-bar')){
        var $this      = $(this),
            $bar       = $(this).find('.library-percentage-used'),
            percentage = $('.library-percentage-bar').data('percentage')+'%';

        $bar.width(percentage);
    }

    // NEW UPLOAD //
    // $('#library_upload_form').fileupload({
    //     change: function (e, data) {
    //         $.each(data.files, function (index, file) {
    //             alert('Selected file: ' + file.name);
    //         });
    //     }
    // });

    // $('#library_upload_form').fileupload({
    //     dataType: 'json',
    //     add: function (e, data) {
    //         data.context = $('<p/>').text('Uploading...').appendTo(document.body);
    //         data.submit();
    //     },
    //     done: function (e, data) {
    //         $.each(data.result.files, function (index, file) {
    //             $('<p/>').text(file.name).appendTo(document.body);
    //         });
    //     },
    //     progressall: function (e, data) {
    //         var progress = parseInt(data.loaded / data.total * 100, 10);
    //         $('#progress .bar').css(
    //             'width',
    //             progress + '%'
    //         );
    //     }
    // });
});



    // // Initialize the jQuery File Upload widget:
    // $('#fileupload').fileupload({
    //     // Uncomment the following to send cross-domain cookies:
    //     //xhrFields: {withCredentials: true},
    // });

    // $('#fileupload').bind('fileuploadadd', function (e, data) {
    //     console.log(e, data);
    // });

    // // Enable iframe cross-domain access via redirect option:
    // $('#fileupload').fileupload(
    //     'option',
    //     'redirect',
    //     window.location.href.replace(
    //         /\/[^\/]*$/,
    //         '/cors/result.html?%s'
    //     )
    // );

    // if (window.location.hostname === 'blueimp.github.io') {
    //     // Demo settings:
    //     $('#fileupload').fileupload('option', {
    //         url: '//jquery-file-upload.appspot.com/',
    //         // Enable image resizing, except for Android and Opera,
    //         // which actually support image resizing, but fail to
    //         // send Blob objects via XHR requests:
    //         disableImageResize: /Android(?!.*Chrome)|Opera/
    //             .test(window.navigator.userAgent),
    //         maxFileSize: 5000000,
    //         acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
    //     });
    //     // Upload server status check for browsers with CORS support:
    //     if ($.support.cors) {
    //         $.ajax({
    //             url: '//jquery-file-upload.appspot.com/',
    //             type: 'HEAD'
    //         }).fail(function () {
    //             $('<div class="alert alert-danger"/>')
    //                 .text('Upload server currently unavailable - ' +
    //                         new Date())
    //                 .appendTo('#fileupload');
    //         });
    //     }
    // } else {
    //     // Load existing files:
    //     $('#fileupload').addClass('fileupload-processing');
    //     $.ajax({
    //         // Uncomment the following to send cross-domain cookies:
    //         //xhrFields: {withCredentials: true},
    //         url: $('#fileupload').fileupload('option', 'url'),
    //         dataType: 'json',
    //         context: $('#fileupload')[0]
    //     }).always(function () {
    //         $(this).removeClass('fileupload-processing');
    //     }).done(function (result) {
    //         $(this).fileupload('option', 'done')
    //             .call(this, $.Event('done'), {result: result});
    //     });
    // }