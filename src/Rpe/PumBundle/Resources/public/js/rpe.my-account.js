$(document).ready(function(){

    // Emptying on enter
    $('.teaching, .interest, .academy').on('keydown',function(e){
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            $(this).val('');
            e.preventDefault(e);
        }
    });

    // ERROR IF EMAIL ALREADY USED
    if ($('.error_list').length > 0) {
        $('#pum_object_emailPro').addClass('email-error');
    }

    // -----------------
    // Extra info script
    // -----------------

    // Show & Close functions, 2 different vars used to not mix up closing and opening animations
    function showTip(){
        showTipDiv.css('display','block');
        showTipDiv.stop().animate({
            'bottom'  : '0px',
            'opacity' : '1'
        },500)
    }

    function hideTip(){
        hideTipDiv.stop().animate({
            'bottom'  : '-20px',
            'opacity' : '0'
        },500,function(){
            hideTipDiv.css('display','none');
        })
    }

    // On focus, used for input / textarea

    $('.extra-info-input').on('focus', function(event){
        showTipDiv = $(this).closest('li').find('.extra');

        if(showTipDiv+':hidden'){
            showTip();
        } else {

        }
        event.preventDefault();
    })

    // On blur, used for input / textarea

    $('.extra-info-input').on('blur', function(event){
        hideTipDiv = $(this).closest('li').find('.extra');

        if(hideTipDiv+':visible'){
            hideTip();
        } else {

        }
        event.preventDefault();
    })

    // Special for bootstrap select

    $('.extra-info-input-bootstrap').on('shown.bs.dropdown', function(event){
        showTipDiv = $(this).closest('li').find('.extra');

        if(showTipDiv+':hidden'){
            showTip();
        } else {

        }
        event.preventDefault();
    })

    // On bootstrap select close

    $('.extra-info-input-bootstrap').on('hidden.bs.dropdown', function(event){
        hideTipDiv = $(this).closest('li').find('.extra');

        if(hideTipDiv+':visible'){
            hideTip();
        } else {

        }
        event.preventDefault();
    })

    // Show email input
    $('#email_check').change(function(){
        showTipDiv = $(this).closest('li').find('.extra'),
        hideTipDiv = $(this).closest('li').find('.extra');

        if(showTipDiv.is(':visible')){
            hideTip();
        } else {
            showTip();
        }
    })

    // -------------
    // MODAL OPENING
    // -------------
    var checkImage = function(){

        var minWidth   = 300,
            thisWidth = $('.image-crop').find('img').width();

            console.log(thisWidth);

            console.log($('.image-crop'));

            if(minWidth <= thisWidth){
                $('.image-crop').Jcrop({
                    jcrop_api   : this,
                    setSelect   : [ 0, 0, 64, 64 ],
                    aspectRatio : 1,
                    maxSize     : [300,300],
                    minSize     : [64,64],
                    boxWidth    : 300,
                    onSelect    : updateCoords
                });
            } else {
                $('.fileupload-preview').html('<div class="image-error">Votre image est trop petite, elle doit faire au minimum 300px x 300px</div>');
                $('#newuploadit').val('');
            }
    }

    var updateCoords = function(c) {
        $('#pum_object_x').val(c.x);
        $('#pum_object_y').val(c.y);
        $('#pum_object_w').val(c.w);
        $('#pum_object_h').val(c.h);
    }

    $('.open-modal-image').on('click',function(event){
        $('.modal-image').modal('show');

         if($('.image-crop').hasClass('active')){
            $('.modal-first').modal('show');
            return false;
        } else {
            // No active class, showing modal, adding active class and Jcrop
            $('.modal-first').modal('show');
            $('.image-crop').addClass('active');
            $('.uploadit').on('change', function(){
                console.log('clicked');

                setTimeout(function(){
                    console.log('timeout done, checking image');
                    checkImage();
                }, 1000)
            })
        }
        event.preventDefault();
    })

    $('.close-modal, .accept-profil').on('click',function(event){
        $('.modal-image').modal('hide');
        event.preventDefault();
    });

    $('.accept-profil').on('click',function(event){
        var mainDiv      = $('.image-upload-details'),
            text         = mainDiv.find('.image-text'),
            image        = mainDiv.parent().find('img'),
            changeButton = mainDiv.find('.btn'),
            newImage     = '/bundles/rpepum/images/bg_upload.png',
            newtext      = "L'image sera visible une fois votre profil sauvegardé";

        changeButton.hide();
        text.html(newtext);
        image.attr('src', newImage);
        image.height(120);
        image.width(120);
    })

    // EDIT LINKS

    // ADD
    var addItemHeight = $('.add-item').height();
    $('.add-item').height(0);

    $('.edit-title').on('click',function(event){
        if($(this).hasClass('active')){
            $(this).removeClass('active');
            $('.add-item').stop().animate({
                height: 0
            }, 450,function(){
                $('.add-item').css('overflow','hidden');
            });
        } else {
            $(this).addClass('active');
            $('.add-item').stop().animate({
                height: addItemHeight
            }, 450, function(){
                $('.add-item').css('overflow','visible');
            });
        }

        event.preventDefault();
    })

    $(".edit-content").hide();

    // EDIT
    $('.edit-profil').on('click',function(event){
         if($(this).hasClass('active')){
            $(this).removeClass('active');
            $(this).parent().next(".edit-content").hide().removeClass('active');
        } else {
            $(this).addClass('active');
            $(this).parent().next(".edit-content").show().addClass('active');
        }
        event.preventDefault();
    });

    $('#verify-user-form').submit(function(event){
        var formObj  = $(this);
        var formURL  = formObj.attr("action");
        var formData = new FormData(this);
        var loader   = formObj.find('span.loader'),
            errorTxt = formObj.find('div.form-error-password')
        ;

        if( formObj.hasClass('active') ){
            return false;
        } else {
            // Adding active class to avoid multiple submits
            formObj.addClass('active');

            loader.removeClass('hidden');
            errorTxt.addClass('hidden');
            // Starting Ajax
            $.ajax({
                url         : formURL,
                type        : 'POST',
                data        : formData,
                contentType : false,
                cache       : false,
                processData : false
            }).done(function(msg) {
                if (msg == 'OK') {
                    $('#pum_object_password_current_single').val($('#verify_user_password').val());
                    $('button.close').click();
                    $('#my-account-form').submit();

                } else {
                    errorTxt.removeClass('hidden');
                    loader.addClass('hidden');
                    $('#verify_user_password').val('');
                }
                // Removing active class
                formObj.removeClass('active');
            });
        }

        event.preventDefault();
    });

    // CANCEL
    $('.cancel-edit').on('click',function(event){
        $(this).closest('.edit-content').prev().find('.edit-profil').removeClass('active').show();
        $(this).closest('.edit-content').removeClass('active').hide();

        event.preventDefault();
    });

    // DELETE
    // Delete Item Non edit mode
    $('.delete-profil').on('click',function(event){
        var self = this,
            id   = $(this).attr('data-id'),
            href = $(this).attr('href');

        $.ajax({
            type: "GET",
            url: href,
            data: { id: id }
        }).done(function(msg) {
            if (msg == 'OK') {
                $(self).parents('.profil-content').fadeOut("slow",function(){
                    $(this).next().remove();
                    $(this).remove();
                });
            }
        });

        event.preventDefault();
    })
    //Delete Item Edition Mode
    $('.delete-profil-btn, .delete-formation-btn').on('click',function(event){
        var self = this,
            id   = $(this).attr('data-id'),
            href = $(this).attr('href');

        $.ajax({
            type: "GET",
            url: href,
            data: { id: id }
        }).done(function(msg) {
            if (msg == 'OK') {
                $(self).parents('.edit-content').fadeOut("slow",function(){
                    $(this).prev().remove();
                    $(this).remove();
                });
            }
        });

        event.preventDefault();
    });

    $(document.body).on('click', '.general-form-edit-btn', function(event){
        var $this       = $(this),
            $originalEl = $this.parent().parent(),
            $newEl      = $originalEl.next('.general-form-edit');

        $originalEl.css('display', 'none');
        $newEl.css('display', 'block');


        event.preventDefault();
    });

    $(document.body).on('click', '.general-form-cancel-btn', function(event){
        var $this       = $(this),
            $originalEl = $this.parent().parent().parent().parent().parent(),
            $newEl      = $originalEl.prev('.extra-info');

        $originalEl.css('display', 'none');
        $newEl.css('display', 'block');


        event.preventDefault();
    });

    $('li.tab').each(function(){
        if($(this).hasClass('active')){
            $(this).find('a').addClass('active');
        }
    });

    // Password & email confirmation inputs //
    $('.password-first-input').on('keyup', function(){
        var $this         = $(this),
            $confirmInput = $('.password-confirm-input'),
            $confirmShow  = $('#pum_object_password_confirm');

//        console.log('keyup');
//        console.log($confirmShow1, $confirmInput1);

        if($confirmInput.hasClass('active')){
            console.log('has data');
        } else {
            console.log('no data, adding');
            $confirmInput.addClass('active');
            $confirmShow.addClass('active'); // Shows the input //
            $this.attr('data-validation','strength');
            $confirmInput.attr('data-validation','confirmation');
            // $confirmInput.data('validation','confirmation');
//            console.log($confirmInput.data('validation'));
        }

        return false;
    });


    // $(".formation-company").rules("add", {
    //     required: true,
    //     message : "Veuillez écrire le nom de votre entreprise, universite, ect..."
    // });

    // $(".formation-job").rules("add", {
    //     required: true,
    //     message : "Veuillez écrire le nom de votre poste"
    // });

    // $(".formation-date").rules("add", {
    //     required: true,
    //     message : "Choissisez vos dates de début et de fin."
    // });

    // $(".formation-description").rules("add", {
    //     required: true,
    //     message : "Déscription de votre poste."
    // });

    // $('[name^="formation_"]').validate();

    // $('.validate-invite').validate({
    //     rules : {
    //         "invitation[email]" : {
    //             required : true,
    //             email    : true
    //         },

    //         "invitation[content]" : {
    //             required : true
    //         }
    //     },
    //     messages : {
    //         "invitation[email]" : "Veuillez écrire une adresse mail valide",
    //         "invitation[content]" : "Veuillez écrire votre message."
    //     }
    // })

    // $('.my-account-form').validate({
    //     rules : {
    //         'pum_object_emailPrivate' : {
    //             email : true
    //         }
    //     },
    //     messages : {
    //         'pum_object_emailPrivate' : "Veuillez écrire une adresse mail valide (ex: mon-adresse@ac-versailles.fr)"
    //     }
    // })


});

// ===========
// TAG MANAGER
// ===========
// Creating var
// var tagFirstInput = jQuery('.teaching').tagsManager({
//     tagsContainer: '.first-tag-list',
//     hiddenContainerId: 'pum_ajax_object_instructedDisciplines',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });

// //Starting typeahead with tagmanager
// $('.teaching').typeahead({
//     name: 'id',
//     limit: 15,
//     prefetch: '?_pum_list=instructedDisciplines'
// }).on('typeahead:selected',function (e, d){

//     tagFirstInput.tagsManager('pushTag',d.value);

// }).on('typeahead:initialized ',function (e){

//     var hiddenContainerId = tagFirstInput.tagsManager().data().opts.hiddenContainerId;

//     $('#'+hiddenContainerId+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagFirstInput.tagsManager('pushTag', item.value);
//     });
// });

// INTEREST
// --
// var tagSecondinput = jQuery('.interest').tagsManager({
//     tagsContainer: '.second-tag-list',
//     hiddenContainerId: 'pum_ajax_object_interests',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });

// $('.interest').typeahead({
//     name: 'interest',
//     limit: 15,
//     prefetch: '?_pum_list=interests'
// }).on('typeahead:selected',function (e, d){

//     tagSecondinput.tagsManager('pushTag',d.value);

// }).on('typeahead:initialized ',function (e){
//     var hiddenContainerId = tagSecondinput.tagsManager().data().opts.hiddenContainerId;

//     $('#'+hiddenContainerId+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagSecondinput.tagsManager('pushTag', item.value);
//     });

// });

// ============
// Academy
// ============
// Creating var
// var tagThirdInput = jQuery('.academy').tagsManager({
//     tagsContainer: '.third-tag-list',
//     hiddenContainerId: 'pum_ajax_object_academy',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });

// //Starting typeahead with tagmanager
// $('.academy').typeahead({
//     name: 'id',
//     limit: 1,
//     prefetch: '?_pum_list=academy'
// }).on('typeahead:selected',function (e, d){

//     $(this).typeahead('setQuery', '')
//     tagThirdInput.tagsManager('pushTag',d.value);
// }).on('typeahead:initialized ',function (e){

//     var hiddenContainerId = tagSecondinput.tagsManager().data().opts.hiddenContainerId;

//     $('#'+hiddenContainerId+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagThirdInput.tagsManager('pushTag', item.value);
//     });
// });

// EXTRA COMMANDS
// Emptying input when tag has been choosen and resize input
/*$('.teaching').on('typeahead:selected',function(){
    $(this).typeahead('setQuery', '')
});

$('.interest').on('typeahead:selected',function(){
    $(this).typeahead('setQuery', '')
});

$('.academy').on('typeahead:selected',function(){
    $(this).typeahead('setQuery', '')
});*/