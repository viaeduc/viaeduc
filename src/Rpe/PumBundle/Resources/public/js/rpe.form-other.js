// $(document).ready(function(){
//     // Turning on select styles with Bootstrap Select
//     $('select').selectpicker();

//     // Special input
//     $('#specialInput').change(function(event) {
//         event.preventDefault();
//         var val = $(this).val();
//         if(val === 'option1') {
//             $('.first-inline').show();
//             $('.first-inline').find('input').removeClass('ignore');
//             $('.second-inline').hide();
//             $('.second-inline').find('input').addClass('ignore');
//         }
//         else if(val === 'option2') {
//             $('.second-inline').show();
//             $('.second-inline').find('input').removeClass('ignore');
//             $('.first-inline').hide();
//             $('.first-inline').find('input').addClass('ignore');
//         };
//     });

//     // ---------
//     // FORM AJAX
//     // ---------

//     // Form vars

//     var form      = $('form');

//     // Hiding elements that need to be hidded

//     $('.back-button').hide();
//     $('#second-data, #third-data').css({
//         'opacity' : '0',
//         'display' : 'none'
//     });

//     // Form functions

//     $('.social a').on('click',function(event){

//         $(this).css('opacity','1');
//         innerSocial = $(this).parent('.inner-social');
//         thisForm = innerSocial.parent('.form-right');

//         if(innerSocial.hasClass('active')){
//             innerSocial.prependTo(thisForm);
//             innerSocial.css('width','auto');
//             innerSocial.find('input').css('display','none');
//             innerSocial.removeClass('active');
//             return false;
//         } else {
//             innerSocial.appendTo('.social-selected');
//             innerSocial.css({'width':'100%','float':'left'});
//             innerSocial.find('input').css('display','block');
//             innerSocial.addClass('active');
//         }
//         event.preventDefault();
//     });

//     firstSubmit = function(){

//         $('body,html').animate({
//             scrollTop: 0
//         }, 500);

//         // Changing class on form
//         form.removeClass('first-form');
//         form.addClass('second-form');

//         // Animating
//         form.find('#first-data').animate({
//             'opacity':'0',
//             'top':'20px'
//         },800,function(){
//             form.find('#first-data').css('display','none');
//             // Showing second form
//             form.find('#second-data').css({
//                 'display':'block'
//             });
//             form.find('#second-data').animate({
//                 'opacity':'1',
//                 'top':'0'
//             }, 800);
//         });

//         // Changing active state
//         $('.nav-register').children('li:first-child').removeClass('active');
//         $('.nav-register').children('li:nth-child(2)').addClass('active');

//         // Changing header
//         $('.content-step').load('includes/form-header-second.php');

//         // Showing back button
//         $('.back-button').show();
//     };

//     secondSubmit = function(){
//         $('body,html').animate({
//             scrollTop: 0
//         }, 500);

//         // Changing class on form
//         form.removeClass('second-form');
//         form.addClass('third-form');

//         // Animating
//         form.find('#second-data').animate({
//             'opacity':'0',
//             'top':'20px'
//         },800,function(){
//             form.find('#second-data').css('display','none');
//             // Showing second form
//             form.find('#third-data').css({
//                 'display':'block'
//             });
//             form.find('#third-data').animate({
//                 'opacity':'1',
//                 'top':'0'
//             }, 800)
//         });

//         // Changing active state
//         $('.nav-register').children('li:nth-child(2)').removeClass('active');
//         $('.nav-register').children('li:last-child').addClass('active');

//         // Changing title text
//         $('.form-black-title').empty();
//         $('.form-black-title').append('<span class="big line">pour en</span>savoir plus');

//         // Changing header
//         $('.content-step').load('includes/form-header-third.php');
//     };

//     thirdSubmit = function(){
//         form.submit();
//     };

//     ignoreLink = function(){
//         if ($('form').attr('class') == 'first-form'){
//             firstSubmit();
//         } else if ($('form').attr('class') == 'second-form'){
//             secondSubmit();
//         } else if ($('form').attr('class') == 'third-form'){
//             alert('sucess');
//         } else {
//             console.log('something went wrong');
//         }
//     };

//     backLink = function(){
//         $('body,html').animate({
//             scrollTop: 0
//         }, 500);

//         if ($('form').attr('class') == 'second-form'){

//             form.removeClass('second-form');
//             form.addClass('first-form');

//             // Animating
//             form.find('#second-data').animate({
//                 'opacity':'0',
//                 'top':'20px'
//             },800,function(){
//                 form.find('#second-data').css('display','none');
//                 // Showing second form
//                 form.find('#first-data').css({
//                     'display':'block'
//                 });
//                 form.find('#first-data').animate({
//                     'opacity':'1',
//                     'top':'0'
//                 }, 800)
//             });

//             // Changing active state
//             $('.nav-register').children('li:nth-child(2)').removeClass('active');
//             $('.nav-register').children('li:first-child').addClass('active');

//             // Changing header
//             $('.content-step').load('includes/form-header-first.php');

//             $('.back-button').hide();

//         } else if ($('form').attr('class') == 'third-form'){

//             form.removeClass('third-form');
//             form.addClass('second-form');

//             // Animating
//             form.find('#third-data').animate({
//                 'opacity':'0',
//                 'top':'20px'
//             },800,function(){
//                 form.find('#third-data').css('display','none');
//                 // Showing second form
//                 form.find('#second-data').css({
//                     'display':'block'
//                 });
//                 form.find('#second-data').animate({
//                     'opacity':'1',
//                     'top':'0'
//                 }, 800)
//             });

//             // Changing active state
//             $('.nav-register').children('li:last-child').removeClass('active');
//             $('.nav-register').children('li:nth-child(2)').addClass('active');

//             // Changing title text
//             $('.form-black-title').empty();
//             $('.form-black-title').append('<span class="big">informations</span>professionnelles');

//             // Changing header
//             $('.content-step').load('includes/form-header-second.php');

//         } else {
//             console.log('something went wrong');
//         }
//     };

//     // Form events

//     $('form').validate({
//         // Specific error divs
//         ignore: ".ignore",
//         errorElement: 'div',
//         errorClass: 'invalid',

//         // Error rules
//         rules: {
//             email: {
//                 email: true
//             }
//         },

//         // Error messages
//         messages: {
//             email: {
//                 mail: "Votre adresse doit avoir un format valide (exemple: contact@rpe.fr)"
//             }
//         },

//         submitHandler: function() {

//             $.ajax({
//                 data: form.serialize(),
//                 success: function(html) {
//                     // debugging
//                     if ($('form').attr('class') == 'first-form'){
//                         firstSubmit();
//                     } else if ($('form').attr('class') == 'second-form'){
//                         secondSubmit();
//                     } else if ($('form').attr('class') == 'third-form'){
//                         window.location.href = "index.php";
//                     } else {
//                         console.log('something went wrong');
//                     }
//                 }
//             });
//         }
//     });

//     // Links
//     $('.ignore-button').on('click', function(e){
//         ignoreLink();
//         event.preventDefault(e);
//     });

//     $('.back-button').on('click', function(e){
//         backLink();
//         event.preventDefault(e);
//     });
// });