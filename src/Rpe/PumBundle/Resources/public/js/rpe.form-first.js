$(document).ready(function(){
    // Turning on select styles with Bootstrap Select
    $('select').selectpicker();

    // $('#popin_warning').modal('show');

    $('.accept-warning').on('click',function(event){
        $('#popin_warning').modal('hide');
        event.preventDefault();
    })

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
        }
    });

    // // Validator Methods
    // $.validator.addMethod("valueNotEquals", function(value, element, arg){
    //     return arg != value;
    // });

    // Form validation
    // $('form').validate({
    //     // Special placements
    //     errorPlacement: function(error, element) {
    //         if (element.attr("name") == "sex")
    //             error.insertAfter(".form-error-sex");
    //         else if (element.attr("name") == "occupation")
    //             error.insertBefore(".occupation-error");
    //         else if (element.attr("name") == "academy")
    //             error.insertAfter(".academy-error");
    //         else if (element.attr("name") == "cgu")
    //             error.insertAfter(".general-conditions");
    //         else if (element.attr("name") == "charte")
    //             error.insertAfter(".convention");
    //         else
    //             error.insertAfter(element);
    //     },

    //     // Main error box, not used here
    //     invalidHandler: function(event, validator) {
    //         var errors = validator.numberOfInvalids();
    //         if (errors) {
    //             var message = 'Des erreurs sont survenus lors de la saisie du formulaire, veuillez corriger les champs entourés de orange avant de soumettre votre formulaire';
    //             $(".main-error").html(message);
    //             $(".main-error").show();
    //         } else {
    //             $(".main-error").hide();
    //         }
    //     },

    //     // Specific error divs
    //     ignore: ".ignore",
    //     errorElement: 'div',
    //     errorClass: 'invalid',

    //     // Error rules
    //     rules: {
    //         sex: {
    //             required: true
    //         },
    //         lastname: {
    //             required: true
    //         },
    //         firstname: {
    //             required: true
    //         },
    //         occupation: {
    //             required: true,
    //             valueNotEquals: "default"
    //         },
    //         academy: {
    //             required: true
    //         },
    //         inlineSecond: {
    //             required: true
    //         },
    //         academie: {
    //             required: true,
    //             valueNotEquals: "default"
    //         },
    //         emailPro: {
    //             required: true,
    //             email: true
    //         },
    //         'password[single]': {
    //             required: true
    //         },
    //         cgu: {
    //             required: true
    //         },
    //         charte: {
    //             required: true
    //         }
    //     },

    //     // Error messages
    //     messages: {
    //         sex: "Merci de choisir votre sexe",
    //         lastname: "Merci d'inserez votre nom",
    //         firstname: "Merci d'inserez votre prenom",
    //         occupation: "Merci de choisir votre activité",
    //         academy: "Merci de choisir votre académie",
    //         emailPro: {
    //             required: "Merci d'inserez votre email académique",
    //             mail: "Votre adresse doit avoir un format valide (exemple: contact@rpe.fr)"
    //         },
    //         passwordRule: {
    //             required: "Merci d'inserez votre mot de passe",
    //             minlength: "Merci d'inserez un mot de passe avec au moins 8 caractères"
    //         },
    //         cgu: "Vous devez accepter les conditions générales",
    //         charte: "Vous devez accepter la charte de la plateforme"
    //     }

    // })

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

    // Select Hiding Errors

    // Not really nice, I know, need to be re-done
    $('#occupation').change(function(){
        $(this).parent().parent().parent().find('.invalid').css('display','none');
    });

    $('#academy').change(function(){
        $(this).parent().parent().parent().find('.invalid').css('display','none');
    });

    $('.first-letter-upper').on('keyup', function() {
        if (this.value[0] != this.value[0].toUpperCase())
             this.value = this.value[0].toUpperCase() + this.value.substring(1);
    });

});