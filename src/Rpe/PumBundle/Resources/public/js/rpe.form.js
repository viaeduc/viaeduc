// $(document).ready(function(){
//     // Turning on select styles with Bootstrap Select
//     $('select').selectpicker();

//     // Select sub form
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
//         }
//     });

//     // Validator Methods
//     $.validator.addMethod("valueNotEquals", function(value, element, arg){
//         return arg != value;
//     });

//     // Validator
//     $('form').validate({
//         errorPlacement: function(error, element) {
//             if (element.attr("name") == "uploadInput")
//                 error.insertAfter(".btn-file");
//             else if (element.attr("name") == "radioInput")
//                 error.insertAfter(".radio-buttons:last-child()");
//             else if (element.attr("name") == "checkInput")
//                 error.insertAfter(".checkFirst");
//             else if (element.attr("name") == "selectInput")
//                 error.insertAfter(".first-select");
//             else if (element.attr("name") == "selectplusInput")
//                 error.insertAfter(".second-select");
//             else if (element.attr("name") == "selectspecialInput")
//                 error.insertAfter(".third-select");
//             else if (element.attr("name") == "inlineFirst")
//                 error.insertAfter(".first-inline");
//             else if (element.attr("name") == "inlineSecond")
//                 error.insertAfter(".second-inline");
//             else
//                 error.insertAfter(element);
//         },
//         invalidHandler: function(event, validator) {
//             var errors = validator.numberOfInvalids();
//             if (errors) {
//                 var message = 'Des erreurs sont survenus lors de la saisie du formulaire, veuillez corriger les champs entourés de orange avant de soumettre votre formulaire';
//                 $(".main-error").html(message);
//                 $(".main-error").show();
//             } else {
//                 $(".main-error").hide();
//             }
//         },
//         ignore: ".ignore",
//         errorElement: 'div',
//         errorClass: 'invalid',
//         rules: {
//             radioSexe: {
//                 required: true
//             },
//             nom: {
//                 required: true
//             },
//             prenom: {
//                 required: true
//             },
//             placeholderInput: {
//                 required: true
//             },
//             emailInput: {
//                 required: true,
//                 email: true
//             },
//             emailagainInput: {
//                 required: true,
//                 equalTo: '#emailInput'
//             },
//             selectInput: {
//                 required: true,
//                 valueNotEquals: "default"
//             },
//             selectplusInput: {
//                 required: true,
//                 valueNotEquals: "default"
//             },
//             selectspecialInput: {
//                 required: true,
//                 valueNotEquals: "default"
//             },
//             inlineFirst: {
//                 required: true
//             },
//             inlineSecond: {
//                 required: true
//             },
//             textareaInput: {
//                 required: true
//             },
//             datepickerInput: {
//                 required: true
//             },
//             uploadInput: {
//                 required: true
//             }
//         },
//         messages: {
//             radioSexe: "Merci de choisir votre sexe",
//             nom: "Merci de rentrer votre nom",
//             prenom: "Merci de rentrer votre textInput",
//             placeholderInput: "Merci de rentrer votre placeholderInput",
//             emailInput: {
//                 required: "Merci de rentrer votre adresse mail",
//                 mail: "Votre adresse doit avoir un format valide (exemple: contact@rpe.fr)"
//             },
//             emailagainInput: "Merci de confirmer votre adresse mail",
//             selectInput: "Merci de choisir votre selectInput",
//             selectplusInput: "Merci de choisir vos selectplusInput",
//             selectspecialInput: "Merci de choisir vos selectspecialInput",
//             textareaInput: "Merci de rentrer votre texte",
//             datepickerInput: "Merci de choisir la date",
//             uploadInput: "Merci de choisir un fichier sur votre ordinateur"
//         }
//     })
// });