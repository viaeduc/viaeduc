$(document).ready(function(){

    // console.log('Dom Ready');

    //message list side
    var container           = $('.messages-list .swiper-container'),
        scrollbar_container = $('.messages-list .swiper-scrollbar'),
        btn_more            = $('.messages-list .more-messages'),
        msg_swiper          = null;

    scrollbar_container.hide();

//    var setScroll = function() {
//        //container.hide();
//        var elements = $('.messages-list .swiper-container ul li'),
//            nb_elements = elements.length;
//
//        if ( msg_swiper == null ){
//            if(nb_elements > 3){
//                //container.show();
//                scrollbar_container.show();
//
//                msg_swiper = new Swiper(container[0],{
//                    mode             : 'vertical',
//                    scrollContainer  : true,
//                    mousewheelControl: true,
//                    scrollbar        : {
//                        container: scrollbar_container[0],
//                        hide     : false,
//                        draggable: true
//                    }
//                });
//            }
//        }
//    }

    // $('.respond-message').on('click',function(event){
    //     $.ajax({
    //         url: formURL,
    //         type: 'POST',
    //         data:  formData
    //     }).done(function(html) {

    //     }).fail(function() {

    //     });

    //     event.preventDefault();
    // })

    if (typeof window.history.replaceState === 'function') {
        // console.log('test => typeof window.history.replaceState');

        $('.list-general-box').on("click", "a.js-loadmore", function(event){
            window.history.replaceState(null, null, $(this).data('url'));
        });
    }

    if(window.location.href.indexOf("?action=newMessage") > -1) {
        // console.log('test => href.?action=newMessage');

        $('.new-message-form').on('submit',function(){
            // console.log('New message form submit');

            var el                 = $(this),
                contactList        = el.find('.tag-list'),
                errorMessage       = 'Merci de choisir au mois un déstinataire',
                errorMessageObject = $('.new-message-error'),
                errorMessageDiv    = $('.messages-top');

            if (contactList.is(':empty')){
                if(el.find(errorMessageObject).hasClass('active')){
                    return false;
                } else {
                    errorMessageObject.html(errorMessage);
                    errorMessageObject.addClass('active');
                    return false;
                }
            }
        })
    }

//    $("ul.list-general-box").bind("DOMSubtreeModified", function() {
//        setScroll();
//    });

    $(document.body).on("submit", ".discution-form", function(ev){
        // console.log('submiting form');

        ev.preventDefault();

        var self        = $(this),
            formObj     = $(this),
            formURL     = formObj.attr("action");

        self.find('textarea, button').attr('readonly', true);

        if( self.hasClass('active') ){
            // console.log('has active class, stopping');
            return false;
        } else {
            // console.log('not active, starting');
            self.addClass('active');

            // check if value text empty
            var content = $.trim($('#message_content').val());
            if(content != ""){
            	$.ajax({
                    url: formURL,
                    type: 'POST',
                    data:  self.serialize(),
                    cache: false
                }).done(function(html) {
                    // console.log('done, not success');

                    self.find('input[type=text]').val('');
                    self.parent().before($(html).hide().fadeIn(450));
                    self.find('textarea').val('').html('').css('height', '').blur().change();
                    self.removeClass('active');
                    self.find('textarea, button').attr('readonly', false);
                }).fail(function() {
                });
            }else{
            	self.find('textarea').val('').html('').css('height', '').blur();
                self.removeClass('active');
                self.find('textarea, button').attr('readonly', false);
            }
        }
    });
    $(document.body).on("keydown", ".discution-form", function(ev){
        // console.log('keydown on discution-form');
        if (ev.keyCode == '13' && ev.ctrlKey) {
            ev.preventDefault();
            $(this).trigger('submit');
        }
    });

    var formFeedback = function() {
        // console.log('starting formFeedback();');

        $('.new-message-form').on('submit',function(){
            // console.log('Submitted new message form in formFeedback();');

            var el                 = $(this),
                contactList        = el.find('.tag-list'),
                errorMessage       = 'Merci de choisir au moins un destinataire',
                errorMessageObject = $('.new-message-error'),
                errorMessageDiv    = $('.messages-top');

            if (contactList.is(':empty')){
                // console.log('ContactList is empty');

                if(el.find(errorMessageObject).hasClass('active')){
                    // console.log('active class in contactList stopping');

                    return false;
                } else {
                    // console.log('does not have active class in contactList');

                    errorMessageObject.html(errorMessage);
                    errorMessageObject.addClass('active');
                    return false;
                }
            }
        })
        return false;
    };

    $(document.body).on('js_loadmore_xhr_complete', '.load-new-message-form', formFeedback);
    $(document.body).on('js_autoload_xhr_success', '.messages-main-container', formFeedback);
});