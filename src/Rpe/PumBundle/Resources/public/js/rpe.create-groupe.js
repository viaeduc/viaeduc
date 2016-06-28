$(document).ready(function(){

    // Extra functions

    $('.discipline, .niveau, .interest').on('keydown',function(e){
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            $(this).val('');
            e.preventDefault(e);
        }
    });

    $(document).on('change', '.input-upload' , function(){
        var $textDiv = $(this).parent().find('.button-text'),
            $button  = $(this).parent();

        if($(this).val() != '') {
            $textDiv.text('Image téléchargée')
            $button.removeClass('dark-grey');
            $button.addClass('green');
        } else{
            $textDiv.text('Télécharge une image')
            $button.removeClass('green');
            $button.addClass('dark-grey');
        }
    });

    // ----------
    // VALIDATION
    // ----------

    // var validator = $('.create-form').validate({
    //     errorPlacement: function(error, element) {
    //         if (element.attr("name") == "group[accesstype]")
    //             error.addClass('special-error'),
    //             error.insertAfter(".type-error");
    //         else if (element.attr("name") == "checkInput")
    //             error.insertAfter(".check-error");
    //         else
    //             error.insertAfter(element);
    //     },

    //     // Specific error divs
    //     ignore: ".ignore",
    //     errorElement: 'div',
    //     errorClass: 'invalid',

    //     // Error rules
    //     rules: {
    //         group_name: {
    //             required: true
    //         },
    //         'group[accesstype]': {
    //             required: true
    //         }
    //     },

    //     // Error messages
    //     messages: {
    //         group_name: "Merci de choisir un nom de groupe",
    //         'group[accesstype]': "Mercide choisir un type d'accès"
    //     }
    // })

    // $('.input-upload').change(function(){
    //     console.log('change');
    //     if($(this).hasClass('valid')){
    //         return false;
    //     } else {
    //         $('#check-one').rules("add", {
    //             required: true,
    //             message: "Merci de cocher la case"
    //         });
    //     }
    // })
})

// ===========
// TAG MANAGER
// ===========
// Creating var
// var tagFirstInput = jQuery('.discipline').tagsManager({
//     tagsContainer: '.first-tag-list',
//     hiddenContainerId: 'pum_ajax_object_instructedDisciplines',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });
// console.log(tagFirstInput);

//Starting typeahead with tagmanager
// $('.discipline').typeahead({
//     name: 'id',
//     limit: 15,
//     prefetch: $('.discipline').attr('data-ajax-url')
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
// var tagSecondinput = jQuery('.niveau').tagsManager({
//     tagsContainer: '.second-tag-list',
//     hiddenContainerId: 'pum_ajax_object_teachingLevels',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });

// $('.niveau').typeahead({
//     name: 'niveau',
//     limit: 15,
//     prefetch: $('.niveau').attr('data-ajax-url')
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

// INTEREST
// --
// var tagThirdinput = jQuery('.interest').tagsManager({
//     tagsContainer: '.third-tag-list',
//     hiddenContainerId: 'pum_ajax_object_interests',
//     backspace: [],
//     onlyTagList: true,
//     tagList: null
// });

// $('.interest').typeahead({
//     name: 'interest',
//     limit: 15,
//     prefetch: $('.interest').attr('data-ajax-url')
// }).on('typeahead:selected',function (e, d){

//     tagThirdinput.tagsManager('pushTag',d.value);

// }).on('typeahead:initialized ',function (e){
//     var hiddenContainerId = tagThirdinput.tagsManager().data().opts.hiddenContainerId;

//     $('#'+hiddenContainerId+' .item').each( function( index, element ){
//         var item = {
//             id:$(element).attr('item-id'),
//             value: $(element).attr('item-value'),
//         };

//         tagThirdinput.tagsManager('pushTag', item.value);
//     });

// });