$(document).ready(function(){

    // ----------
    // Home video
    // ----------
    // console.log('domready');

    var myPlayer = videojs('welcome_video', { "controls": true, "autoplay": false, "preload": "auto" , "width" : 567 , "height" : 327 });

    videojs("welcome_video").ready(function(){

    });

    $('.play').on('click', function () {
        $('#welcome_video').toggleClass('active');
        $('.video-launcher').toggleClass('active');
        $('.shadow').toggleClass('active');
        // @TODO Put a REAL autoplay
    });

    // $('#welcome_video').on('fullScreenChange', function(){
    //     console.log('fullscreen change');
    // });


    // $('#').on('keyup',function(){
    //     if ($(this).val()=='jpb@gmail.com'){
    //         $('.error-home').css('display','block')
    //         return false;
    //     } else {
    //         if($('.error-home').is(':visible')){
    //             $('.error-home').css('display','none');
    //             return false;
    //         } else {
    //             return false;
    //         }
    //     }
    // })

    // $('.login-form').validate({
    //     // Specific error divs
    //     ignore: ".ignore",
    //     errorElement: 'div',
    //     errorClass: 'invalid',

    //     // Error rules
    //     rules: {
    //         email: {
    //             required: true,
    //             email: true
    //         },
    //         password: {
    //             required: true,
    //             minlength: 8
    //         }
    //     },

    //     // Error messages
    //     messages: {
    //         email: {
    //             required: "Merci d'inserez votre email académique",
    //             mail: "Votre adresse doit avoir un format valide (exemple: contact@rpe.fr)"
    //         },
    //         password: {
    //             required: "Merci d'inserez votre mot de passe"
    //         },
    //     }
    // })

    if(window.location.hash) {
        if (window.location.hash.substring(1) == 'popin_forget_password') {
            $('a.forget').click();
            window.location.hash = '';
        } else {
            return false;
        }
    }

    var formObj;

    // GOOD //
    $.validate({
        validateOnBlur : false,
        onSuccess : function(f){
            var formObj   = f,
                formURL   = formObj.attr("action"),
                formData  = f.serialize(),
                userEmail = formObj.find('.small-input').val();

            // console.log('formObj', formObj);
            // console.log('formURL', formURL);
            // console.log('formData', formData);
            // console.log('userEmail', userEmail);

            if(!formObj.hasClass('active')) {
                formObj.addClass('active');

                $.ajax({
                    url         : formURL,
                    type        : 'POST',
                    data        : formData
                }).done(function(html) {
                    // console.log(html);

                    // @todo : old code, needs to be redone
                    if(html == "ok"){
                        // console.log('ok');
                        formObj.find('.body-gray').html("Un courriel a été envoyé à l'adresse que vous avez indiquée. Veuillez vous y reporter pour poursuivre la procédure de réinitialisation de votre mot de passe.");
                        formObj.find('.button-password').html('');
                    } else if(html == "noexist"){
                        // console.log('noexist');
                        formObj.find('div.form-error-email').html("L'email saisi n'est pas valide ou n'existe pas sur le réseau ViaEduc.");
                        formObj.removeClass('active');
                    } else if(html == "inactive"){
                        // console.log('inactive');
                        formObj.find('div.form-error-email').html("Cet email est déjà enregistré mais n’a pas été activé.");
                        formObj.removeClass('active');
                    } else {
                        // console.log('something else');
                    }
                }).fail(function() {
                    // console.log('failed ajax');
                });
            }

            return false;
        },
        submitHandler : function(form){
            return false;
        },
        form : '.pass-forgotten-form'
    });
});