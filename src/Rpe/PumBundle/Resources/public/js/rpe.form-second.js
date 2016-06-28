$(document).ready(function(){

    // -------
    // GENERAL
    // -------

    // Special input
    $('#specialInput').change(function(event) {
        event.preventDefault();
        var val = $(this).val();
        if(val === 'option1') {
            $('.first-inline').show();
            $('.first-inline').find('input').removeClass('ignore');
            $('.second-inline').hide();
            $('.second-inline').find('input').addClass('ignore');
        }
        else if(val === 'option2') {
            $('.second-inline').show();
            $('.second-inline').find('input').removeClass('ignore');
            $('.first-inline').hide();
            $('.first-inline').find('input').addClass('ignore');
        };
    });

    // Add items
    var addItem = function(item_id, item) {
        var store_name = $('#'+item_id).attr('store-name');
        if (0 == $('#'+item_id+' input[item-id='+item.id+']').length) {
            $('#'+item_id).append('<input type="hidden" class="item" name="'+store_name+'" value="'+item.id+'" />')
        }
    }

    // Emptying on enter
    $('.teaching, .interest, .academy').on('keydown',function(e){
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            $(this).val('');
            e.preventDefault(e);
        }
    });

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

    // ---------
    // FORM AJAX
    // ---------

    // Form vars

    var form      = $('form');

    // Form events

    // $('form').validate({
    //     // Specific error divs
    //     ignore: ".ignore",
    //     errorElement: 'div',
    //     errorClass: 'invalid',

    //     // Error rules
    //     rules: {
    //         email: {
    //             email: true
    //         }
    //     },

    //     // Error messages
    //     messages: {
    //         email: {
    //             mail: "Votre adresse doit avoir un format valide (exemple: contact@rpe.fr)"
    //         }
    //     }
    // });

    // -------------
    // MODAL OPENING
    // -------------

    var updateCoords = function(c) {
        $('#pum_object_x').val(c.x);
        $('#pum_object_y').val(c.y);
        $('#pum_object_w').val(c.w);
        $('#pum_object_h').val(c.h);
    }

    var checkImage = function(){
        var minWidth   = 300,
            thisWidth = $('.image-crop').find('img').width();

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
                $('.fileupload-preview').html('<div class="image-error">Votre image est trop petite, elle doit faire au minimum 300px x 300px</div>')
            }
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
            $('.uploadit').on('change.bs.fileinput', function(){
                setTimeout(function(){
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

});

// TAG MANAGER

// Creating var
// var tagFirstInput = jQuery('.teaching').tagsManager({
//     tagsContainer: '.first-tag-list',
//     backspace: [],
//     onlyTagList: true
// });

//Starting typeahead with tagmanager
// var teaching_id = 'pum_ajax_object_instructedDisciplines';
// $('.teaching').typeahead({
//     name: 'id',
//     limit: 15,
//     prefetch: '?_pum_list=instructedDisciplines'
// }).on('typeahead:selected',function (e, d){
//     tagFirstInput.tagsManager('pushTag',d.value);
//     addItem(teaching_id, d);
// }).on('typeahead:initialized ',function (e){
//     $('#'+teaching_id+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagFirstInput.tagsManager('pushTag', item.value);
//         addItem(teaching_id, item);
//     });
// });

// INTEREST
// --
// var tagSecondinput = jQuery('.interest').tagsManager({
//     tagsContainer: '.second-tag-list',
//     backspace: [],
//     onlyTagList: true
// });

// var interest_id = 'pum_ajax_object_interests';
// $('.interest').typeahead({
//     name: 'interest',
//     limit: 15,
//     prefetch: '?_pum_list=interests'
// }).on('typeahead:selected',function (e, d){
//     tagSecondinput.tagsManager('pushTag',d.value);
//     addItem(interest_id, d);
// }).on('typeahead:initialized ',function (e){
//     $('#'+interest_id+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagSecondinput.tagsManager('pushTag', item.value);
//         addItem(interest_id, item);
//     });
// });

// Academy

// Creating var
// var tagThirdInput = jQuery('.academy').tagsManager({
//     tagsContainer: '.third-tag-list',
//     backspace: [],
//     onlyTagList: true
// });

//Starting typeahead with tagmanager
// var academy_id = 'pum_ajax_object_academy';
// $('.academy').typeahead({
//     name: 'id',
//     limit: 1,
//     prefetch: '?_pum_list=academy'
// }).on('typeahead:selected',function (e, d){
//     $(this).typeahead('setQuery', '')
//     tagThirdInput.tagsManager('pushTag',d.value);
//     addItem(academy_id, d);
// }).on('typeahead:initialized ',function (e){
//     $('#'+academy_id+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagThirdInput.tagsManager('pushTag', item.value);
//         addItem(academy_id, item);
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