$(document).ready(function(){
    var cover = $('.cover-left');

    cover.find('.icon-star').attr('title','Ajouter à mes groupes favoris');
    cover.find('.icon-user').attr('title','Nombre de membres');
    cover.find('.icon-newspaper').attr('title','Nombre de ressources');
    cover.find('.icon-megaphone').attr('title','Signaler le groupe');

    $('.cover-badge').hover(function(){
        $(this).find('.subscribed').stop().animate({
            opacity: 0
        },500,function(){
            $(this).css('display','none');
            $('.unsubscribe').css('display','block');
            $('.unsubscribe').stop().animate({
                opacity: 1
            }, 500);
        })
    },function(){
        $('.unsubscribe').stop().animate({
            opacity: 0
        }, 500, function(){
            $(this).css('display','none');
            $('.subscribed').css('display','block');
            $('.subscribed').animate({
                opacity: 1
            }, 500)
        });
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
            }
            event.preventDefault();
        });
    }

    // ----------------------------------------------------- //
    // jQuery Filter, allowing users to use the search input //
    // ----------------------------------------------------- //

    $('.friend-filter').keyup(function(){
       var valThis = $(this).val().toLowerCase();

        $('.friend-list > li').each(function(){
            var text = $(this).text().toLowerCase();

            (text.indexOf(valThis) != -1) ? $(this).show() : $(this).hide();
        });
    });

    // --------------------------- //
    // Accept group refresh button //
    // --------------------------- //

    $('.accept-group-invit').on('click', function(e){
        location.reload();
    })

    // --------------------- //
    // MODULE ADMINISTRATION //
    // --------------------- //

    // ------- //
    // GENERAL //
    // ------- //


    $(document).ajaxComplete(function() {
        $.validate({
            modules : 'security',
            validateOnBlur : false,
                form : '.survey_publish-form'
        });
    });

    // Open Panel
    $('.module-config').on('click', function(event){
        var el      = $(this),
            content = el.parent().next('.module-content');

        if(el.hasClass('active')){
            el.removeClass('active');
            content.css({
                'display' : 'none'
            });
        } else {
            el.addClass('active');
            content.css({
                'display' : 'inline-block'
            });
        }

        event.preventDefault();
    })

    // ---- //
    // VOTE //
    // ---- //


    // Hiding toggles
    $('.new-form').toggle();
    $('.vote-result-wrapper').toggle();

    // New vote click
    $('.open-new').on('click',function(event){
        $(this).next('.new-form').toggle();
        event.preventDefault();
    });

    // New vote click
    $('.module-wrapper.survey .edit-link').on('click',function(event){
        $(this).closest('.vote-content').after($('.vote-edit-form'));
        $('.vote-edit-form').css('display', 'block');
        event.preventDefault();
    });

    // Add input
    $('.module-wrapper.survey .new-form').on('click', '.new-vote-add-answer', function(event){
        var el          = $(this),
            counterDivs = el.parent().parent().parent().find('.new-vote-answer'),
            counter     = 0;

        $(counterDivs).each(function(){
            counter++;
        });

        counter++; // Extra ++ since counter starts at 0 and not 1

        var formName = el.closest('form').data('name');

        var input = '<li class="new-vote-answer"><div class="form-left"><label for="'+formName+'_answer-'+counter+'">Réponse '+counter+'</label></div><div class="form-right"><span class="text-box"><input type="text" id="'+formName+'_answer-'+counter+'" name="answer['+counter+']"><a href="#" class="new-vote-remove-answer icon-cross"></a></span></div></li>';

        $(input).insertAfter($(counterDivs).last());

        event.preventDefault();
    });

    // Delete cross
    $('.module-wrapper.survey .new-form').on('click', '.new-vote-remove-answer', function(event){
        var voteAnswer = $(this).closest('.new-vote-answer');
        var voteAnswers = voteAnswer.parent().find('.new-vote-answer');

        if(voteAnswers.length > 2) {
            voteAnswer.remove();
        }

        event.preventDefault();
    });

    // Generating results from data-result
    $('.vote-result').each(function(){
        var el                = $(this),
            result            = el.data('result'),
            resultDiv         = el.find('.vote-result-text'),
            resultProgressDiv = el.find('.vote-result-progress');

        resultProgressDiv.css({
            'width' : result+'%'
        });
        resultDiv.html(result+'%');
    });

    // Open results
    $('.vote-question').on('click',function(event){
        var el     = $(this).parent(),
            result = el.find('.vote-result-wrapper');

        result.toggle();

        event.preventDefault();
    });

    // ----------------------- //
    // PROFIL AND COVER UPLOAD //
    // ----------------------- //

    //Image Preview Function (Needs IE9 Ajax fallback)
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
                    console.log('profil image')

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
        event.preventDefault();

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
            },1000)
            return false;
            event.preventDefault();
        });

        $('.confirm-profil-image-change').on('click',function(){
            var cropX = form.find('.cropX').val(),
                cropY = form.find('.cropY').val(),
                cropH = form.find('.cropH').val(),
                cropW = form.find('.cropW').val(),
                href  = form.data('href');

            form.trigger('submit') // Submitting form on button click

            $.ajax({
                type: "POST",
                data: post,
                url: href
            }).done(function(html) {
                form.removeClass('active');
            });

            form.removeClass('active');
            event.preventDefault();
        });
    });

    $('.modules-wrapper .module-wrapper').each(function() {
        if($(this).hasClass('enabled')) {
            $('.modules-activated').after($(this));
        }
    });

    $('.modules-wrapper .module-wrapper').on('click', '.action-btn.module-toggle', function() {
        if($(this).hasClass('enabled')) {
            $('.modules-activated').after($(this).closest('.module-wrapper'));
        }
        else {
            $('.modules-not-activated').after($(this).closest('.module-wrapper'));
        }
    });

    $('.modules-wrapper .module-wrapper').on('change', '.module-position', function() {
        $.ajax($(this).data('href'), {
            type: 'POST',
            data: {
                'position': $(this).val()
            }
        }).success(function(html) {
            var selects = $('select.module-position');
            selects.next().remove();

            selects.each(function() {
                var select = $(html);
                select.attr('data-href', $(this).data('href'));

                $(this).replaceWith(select);
            });

            var selects = $('select.module-position');

            selects.each(function() {
                var module = $(this).closest('.module-wrapper').data('module');
                var value = $(this).find('option[data-module='+module+']').val();

                $(this).val(value);
            });

            selects.dropdown();
        });
    });

    $(document.body).on('click', '.invit-dropdown-toggle', function(event){
        var $el       = $(this),
            $dropDown = $el.parent().find('.invit-dropdown-menu');

        // console.log('clicked');
        // console.log($el);
        // console.log($dropDown);

        if ($el.hasClass('active')){
            // console.log('i haz active klass :)');
            $el.removeClass('active');
            $dropDown.removeClass('active');
        } else {
            // console.log('I dont haz klass :(');
            $el.addClass('active');
            $dropDown.addClass('active');
        }

        event.preventDefault();
    });

    var ajaxRq = null;
    $(document.body).on('keyup', '.invite-members-search-input', function(){
        var $el      = $(this),
            href     = $el.data('href'),
            target   = $el.data('target'),
            valThis  = $(this).val().toLowerCase();

        if($el.hasClass('searching')){
            // console.log('already searching')
            if(ajaxRq != null){
                ajaxRq.abort();
            }
            ajaxRq = $.ajax({
                    url: href,
                    data:{search: valThis},
                    cache: false,
                    timeout: 20000
                }).success(function(html) {
                    // console.log('sent ajax data to page')
                    $el.removeClass('searching');
                    $(target).html(html);
                });
        } else {
            // console.log('starting search')
            $el.addClass('searching');
            ajaxRq = $.ajax({
                    url: href,
                    data:{search: valThis},
                    cache: false,
                    timeout: 5000
                }).success(function(html) {
                    // console.log('sent ajax data to page')
                    $el.removeClass('searching');
                    $(target).html(html);
                });
        }
    });

    // $(document.body).on('submit', '.invite-user-external-form', function(e){
    //     console.log('submitted');

    //     var $el      = $(this),
    //         href     = $el.data('href'),
    //         target   = $el.data('target');

    //     if($el.hasClass('searching')){
    //         return false
    //     } else {
    //         $el.addClass('searching');
    //         setTimeout(function(){
    //             $.ajax({
    //                 url: href,
    //                 cache: false,
    //                 timeout: 5000
    //             }).success(function(html) {
    //                 $el.removeClass('searching');

    //                 $(target).html(html);
    //             })
    //         }, 200);
    //     }

    //     e.preventDefault();
    // });


    $(document.body).on('click', '.administration-right .reject', function(event){
        $('.administration-right').html('');
        $('.administration-top a:nth-child(3)').click();
    });

    $(document.body).on('click', '.filter-btn.light-blue', function(event){
        $('.administration-right').html('');
    });

    // prevent modal from closing when form has error
    $('#submitPadPostBtn').on('click', function(e){
        e.preventDefault();
        $('#submitPadPostBtn').attr('clicked', 'true');
        $('#createPostPad').submit();
    });
    $('#cancelPadPostBtn').on('click', function(e){
        $('#submitPadPostBtn').attr('clicked', 'false');
    });
    $('#new-etherpad-modal').on('hide.bs.modal', function (e) {
        if ($('#submitPadPostBtn').attr('clicked') == 'true') {
            if ($('#createPostPad').find('.new-etherpad-input').hasClass('error')) {
                // modal remains displayed
                e.preventDefault();
            }
        }
    });

});