$(document).ready(function(){

    // Trigger tabs if loaction.hash
    var hash = location.hash || null;

    if(hash != null){
        $('.tab').each(function(){
            var $this = $(this),
                $btn  = $this.find('a');

            if($btn.attr('href') == hash){
                $btn.trigger('click');
            }
        })
    }

    // add to friend button
    $('.add-to-friend').on('click',function(event){
        var self = this,
            id   = $(this).attr('data-id'),
            href = $(this).attr('href'),
            sent = $(this).attr('data-sent');

        if (sent == 0) {
            $(this).attr('data-sent', 1);

            $.ajax({
                type: "GET",
                url: href,
                data: { id: id }
            }).done(function(msg) {
                if (msg == 'OK') {
                    $(self).html("Demande d'ami envoyée");
                }
            });
        }

        event.preventDefault();
    });

    // add to friend button
    $('.respond-to-friend').on('click',function(event){
        var self = this,
            id   = $(this).attr('data-id'),
            href = $(this).attr('href'),
            sent = $(this).attr('data-sent');

        if (sent == 0) {
            $.ajax({
                type: "GET",
                url: href,
                data: { id: id }
            }).done(function(msg) {
                if (msg == 'OK') {
                    $(self).remove();
                }
            });
        }

        event.preventDefault();
    });

    if(window.FormData !== undefined)  // for HTML5 browsers
    {
        $('#simple-post-form').submit(function(event){
            var formObj  = $(this);
            var formURL  = formObj.attr("action");
            var formData = new FormData(this);


            // Checking for active class
            if ( formObj.hasClass('active') ){
                return false;
            } else {
                // Adding active class to avoid multiple submits
                formObj.addClass('active');

                // check if value text empty
                var content = $.trim($('#post_content').val());
                if(content != ""){
                    // Starting Ajax
                    $.ajax({
                        url         : formURL,
                        type        : 'POST',
                        data        : formData,
                        mimeType    : "multipart/form-data",
                        contentType : false,
                        cache       : false,
                        processData : false
                    }).done(function(html) {
                        // Sending data
                        // console.log('sent the form on profil page');
                        // console.log(html)

                        $('.uploaded-files-wrapper').html('');
                        $('.uploaded-files-wrapper').hide();
                        $('#post_file_file').val('');
                        $('#post_content').val('');
                        $('#posts-content').prepend($(html).hide().fadeIn(450));
                        $('#posts-content .publication-box').trigger('getForm');
                        // Removing active class
                        formObj.removeClass('active');
                        $('.post-file-btn').css('display','block');
                    }).fail(function() {

                    });
                }else{
                    formObj.removeClass('active');
                }
            }

            event.preventDefault();
        });
    }

    // -------------- //
    // PREVIEW & CROP //
    //
    // Taille image profil : 117x117
    // Taille image cover  : 837x300
    // -------------- //

    // Image Preview Function (Needs IE9 Ajax fallback)
    function imagePreview(file, imageFileInput) {
        var imageDiv  = imageFileInput.prev('img');
        var reader    = new FileReader();
        var image     = new Image();

        reader.readAsDataURL(file);
        reader.onload = function(_file) {
            image.onload = function() {
                var imgWidth  = this.width,
                    imgHeight = this.height,
                    imgType   = file.type,
                    imgName   = file.name,
                    imageSize = ~~(file.size/1024) +'KB';

                if(coverImage == 1) {
                    if(imgWidth < 837) {
                        errorVar = 1;
                        // console.log(errorVar)
                        alert('Merci de choisir une image avec des dimensions minimales de : 837px x 300px');
                        return false;
                    }

                    if(imgHeight < 300) {
                        errorVar = 1;
                        // console.log(errorVar)
                        alert('Merci de choisir une image avec des dimensions minimales de : 837px x 300px');
                        return false;
                    }
                } else {
                    // console.log('profil image')

                    if(imgWidth < 117) {
                        errorVar = 1;
                        // console.log(errorVar)
                        // alert.log('in  function error var = '+errorVar)
                        alert('Merci de choisir une image avec des dimensions minimales de : 117px x 117px');
                        return false;
                    }

                    if (imgHeight < 117) {
                        errorVar = 1;
                        // console.log(errorVar)
                        // alert.log('in  function error var = '+errorVar)
                        alert('Merci de choisir une image avec des dimensions minimales de : 117px x 117px');
                        return false;
                    }
                }
            };

            if (errorVar == 0) {
                // console.log('no errors')
                image.src    = _file.target.result;              // url.createObjectURL(file);
                imageDiv.attr('src', image.src);
            } else {
                return false;
            }
        };
    };

    // Starting on link click
    $('.edit-profil-link').on('click',function(event){
        // Vars
        var imageFileInput = $(this).parent().find('.profil-image-input'),
            image          = imageFileInput.prev('img'),
            cropbox        = $(this).parent().find('.crop-box'),
            cropwidth      = cropbox.data('width'),
            cropheight     = cropbox.data('height'),
            results        = cropbox.parent().find('.results'),
            x              = $('.cropX', results),
            y              = $('.cropY', results),
            w              = $('.cropW', results),
            h              = $('.cropH', results),
            form           = image.parent('form');


        errorVar   = 0;
        coverImage = 0;

        // console.log(errorVar)

        // Triggering click on hidden file input fiel
        imageFileInput.trigger('click');

        // On file input change
        imageFileInput.on('change',function(event){

            var F     = this.files,
                ext   = imageFileInput.val().split('.').pop().toLowerCase();

            if($.inArray(ext, ['bmp','png','jpg','jpeg']) == -1) {
                errorVar = 1;
                // console.log(errorVar)
                alert('Merci de choisir un fichier image (png, jpg, jpeg, bmp)');
                return false;
            }

             if (image.hasClass('cover-image')){
                // console.log('has class')
                coverImage = 1;
            } else {
                coverImage = 0;
            }


            // Starting imagePreview funtcion, on change of input, show picture
            if(F && F[0]) for(var i=0; i<F.length; i++) imagePreview( F[i], imageFileInput );
            image.css('opacity','0'); // Hiding for esthetic reasons

            setTimeout(function(){
                // console.log('timeout error Var = '+errorVar)
                if(errorVar == 0) {
                    form.addClass('active')

                    image.animate({'opacity':'1'},500); // Animation showing "uploaded" image, needs to be done in CSS3
                    cropbox.cropbox({ // Launching cropbox
                        width: cropwidth,
                        height: cropheight,
                        showControls: 'auto'
                    })
                    .on('cropbox', function( event, results, img ) { // Updating hidden results val
                        x.val( results.cropX );
                        y.val( results.cropY );
                        w.val( results.cropW );
                        h.val( results.cropH );
                    });
                } else {
                    errorVar = 0;
                    return false;
                }
            },400)
            return false;
            event.preventDefault();
        });

        $('.confirm-profil-image-change').on('click',function(){
            // var cropX = form.find('.cropX').val(),
            //     cropY = form.find('.cropY').val(),
            //     cropH = form.find('.cropH').val(),
            //     cropW = form.find('.cropW').val(),
            //     href  = form.data('href');
            // console.log('submitting it')
            // console.log(form.attr('class'))
            form.trigger('submit') // Submitting form on button click

            // $.ajax({
            //     type: "POST",
            //     data: post,
            //     url: href
            // }).done(function(html) {
            //     form.removeClass('active');
            // });

            form.removeClass('active');
            event.preventDefault();
        })

    });

    // -------- //
    // TUTORIAL //
    // -------- //

});