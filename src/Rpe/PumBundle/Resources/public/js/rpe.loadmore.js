;(function(){
        // Global scope var so we can store the data-target attribute
        window.target = '';
        window.post   = '';

        // ===========================================================================================
        // HOW DOES IT WORKS ?  =>  It's magic! Nah, not really ...
        // ===========================================================================================

        // ============
        // Mandatory
        // ============

        // class='js-loadmore'  =>  Bind the event to the desired element
        // data-href            =>  Link to the template to insert (not mandatory if the main
        // element is a link <a href="http://www.link.go"> then "href" is used)


        // ===========
        // Options
        // ===========

        // By attributes:
        //
        // data-loadtarget      =>  Selector of the reference element if it's not the current one
        // data-confirm         =>  String message to display before the ajax is send

        // By classes:

        // 'js-once' / 'js-infinite'            =>  Define if the event must be triggered once or multiple times
        // 'js-redirect'                        =>  Define if there is a redirection instead of insert
        // 'js-load-before' / 'js-load-inner' / =>  Define where the response will be inserted
        // 'js-load-replace' / 'js-remove'          (insert-after by default)

        // ============================================================================================

        // $(document).on('js_autoload_xhr_error', function(){
        //     console.log('error');
        // });
        // $(document).on('js_autoload_xhr_success', function(){
        //     console.log('success');
        // });

        $(document).ready(function(){
                $(document.body).on('click', '.js-loadmore', function(event) {
//                	console.log('clicked on loadmore');
                	event.stopPropagation();
                    var el           = $(this),
                            href         = el.data('href') || el.attr('href'),
                            post         = el.data('post') || null,
                            target       = el.data('loadtarget') || el.data('target') || $(this),
                            confirmMsg   = el.data('confirm') || null,
                            disableMsg   = el.data('disable') || null,
                            deleteLink   = el.data('delete-link') || null,
                            confirmModal = $('.confirm-modal') || null,
                            deleteDiv    = el.data('remove-div') || null,
                            replaceEl	 = el.data('replace-el') || null,
                            title        = el.data('statetitle') || el.text(),
                            loadingClass = el.data('loadclass') || 'loading',
                            refreshZone  = el.data('refresh') || null,
                            reload = el.data('reload') || null;

                    /*console.log('el', el);*/
                    // console.log('Href   : '+href);
                    // console.log('Post   : '+post);
                    // console.log('Target : '+target);

                    var animate_A = {
                            height : 0
                        },
                        animate_B = {
                            opacity: 0
                        };

                 // Est-ce que l'event doit être attaché à nouveau au bouton après la réponse AJAX ?
                    if (el.hasClass('js-infinite') || el.hasClass('js-once')) {
                            animate_A = animate_B = {};
                    }

                    if (el.attr('data-delete-link') != null) {
                            var targetLink = el.closest('li').find('span').first().attr('data-loadtarget');
                            if ($(targetLink).find('li[data-dropurl]').length <= 0) {
                                el.removeClass('disable-delete');
                            }
                    }

                    if ( el.hasClass(loadingClass) ){
                            return false;
                    } else {
                            if (el.hasClass('js-confirm') && null !== confirmMsg) {
                                // var mainDiv = el.parent().parent();
                                if (!confirm(confirmMsg)) {
                                        return false;
                                }

                                // console.log(deleteLink);
                                // console.log(target);
                                // console.log(href);

                                $.ajax({
                                        type: "POST",
                                        data: post,
                                        url: deleteLink,
                                        cache: false
                                }).success(function() {

                                        if (deleteDiv !== null) {
                                            // console.log('deleteDiv exists');

                                            // if ($subComments !== null){
                                            //     console.log('sub comments exists');

                                            //     console.log($subComments);
                                            //     $subComments.each(function(){
                                            //         console.log($(this));
                                            //     })
                                            //     $subComments.remove();
                                            // }

                                            // console.log(deleteDiv);

                                            var $nextDivs = $(deleteDiv).next('.sub-comments-wrapper');

                                            if($nextDivs !== null){
                                                    // console.log('haz class sub-comment removing');
                                                    $nextDivs.remove();
                                            }
                                            $(deleteDiv).remove();
                                        }
                                        // mainDiv.remove();
                                });
                                event.preventDefault();
                                return false;
                            }

                            if (el.hasClass('js-confirm-modal') && null !== confirmMsg) {

                                $('.confirm-modal').modal('show');
                                $('.confirm-modal').find('.confirm-modal-message').attr('href', deleteLink);
                                $('.confirm-modal').find('.data-confirm-message').html(confirmMsg);
                                if(null !== title) {
                                        $('.confirm-modal').find('.modal-title').html(title);
                                }

                                if(el.hasClass('disable-delete')){
                                        $('.confirm-modal').find('.data-confirm-message').html(disableMsg);
                                        $('.confirm-modal').find('.modal-footer').css('display', 'none');
                                } else {
                                        $('.confirm-modal').find('.modal-footer').css('display', 'block');
                                        $('.confirm-modal-message').off( "click" );
                                        $('.confirm-modal-message').on('click', function(){
                                            $.ajax({
                                                    type: "POST",
                                                    data: post,
                                                    url: deleteLink,
                                                    cache: false
                                            }).success(function(response) {
                                                    // console.log('ajax sucess');
                                                    // console.log(deleteDiv);

                                                    // var subComments  = deleteDiv.replace('comment','subcomment'),
                                                // $subComments  = $('div[id^="'+subComments+'"]');

                                                    // console.log(subComments);
                                                    // console.log($(subComments));

                                                    if (el.hasClass('js-redirect')) {
                                                        //target = $(target);
                                                        window.location.replace(target);
                                                        el.trigger('js_loadmore_xhr_success', [target, el]);
                                                    }

                                                    if (deleteDiv !== null) {
                                                        // console.log('deleteDiv exists');

                                                        // if ($subComments !== null){
                                                        //     console.log('sub comments exists');

                                                        //     console.log($subComments);
                                                        //     $subComments.each(function(){
                                                        //         console.log($(this));
                                                        //     })
                                                        //     $subComments.remove();
                                                        // }

                                                        // console.log(deleteDiv);

                                                        var $nextDivs = $(deleteDiv).next('.sub-comments-wrapper');

                                                        if($nextDivs !== null){
                                                                // console.log('haz class sub-comment removing');
                                                                $nextDivs.remove();
                                                        }
                                                        $(deleteDiv).remove();
                                                    }
                                                    
                                                    if (replaceEl !== null) {
                                                    	$(replaceEl).replaceWith(response);
                                                    }
                                            }).complete(function(){
                                                if (refreshZone !== null) {
                                                        $(refreshZone).removeClass('loading').autoLoad();
                                                    }
                                            });

                                            $('.confirm-modal').modal('hide');

                                            if($(document.body).hasClass('modal-open')){
                                                    $(this).removeClass('modal-open');
                                            }

                                            return false;
                                        });
                                }
                                return false;
                            }

                            // Disabling button & adding loader
                            el.addClass(loadingClass); // Adding class to stop click

                            if (el.hasClass('js-pushstate') && $('html').hasClass('history')) {
                                history.pushState(title, '', href);
                            }

                            el.stop().animate(animate_A, 250,function(){
                                el.children('a').html('');
                                el.children('a').addClass('loader'); // Changer la class pour ajouter celle du loader quand elle est prête

                                // Running AJAX
                                $.ajax({
                                        type: "POST",
                                        data: post,
                                        url: href,
                                        cache: false
                                }).fail(function(){
                                        el.trigger('js_loadmore_xhr_fail', [target, el]);
                                }).success(function(html) {

                                        if (reload !== null) {
                                            $.ajax({
                                                    type: "POST",
                                            }).success(function(response){
                                                    $(reload).html($(response).find(reload).html());
                                                    if (reload == '.sidebar') {
                                                        $('.sidebar').find('.connections-componant.relation-js, .ressource-wrapper').css({
                                                                'opacity': 1,
                                                                'right': 0,
                                                                'left': 0
                                                        });
                                                    }
                                            });
                                        }

                                        // Creating jQuery target selector
                                        if (!el.hasClass('js-redirect')) {
                                            target = $(target);
                                        }

                                        // Animating js-loadmore
                                        el.stop().animate(animate_B, 450,function(){
                                            // Removing js-loadmore
                                            if (el.hasClass('js-once')) {
                                                    // console.log('has class once');
                                                    el.removeClass('js-loadmore js-once ' + loadingClass);   // Delete classes that bind the event
                                            } else if (el.hasClass('js-infinite')) {
                                                    el.removeClass(loadingClass);                   // Remove "loading“ class so the button can be clicked again
                                            } else if (el.hasClass('js-redirect')) {
                                                    window.location.replace(target);                // Redirect
                                            } else if (!el.hasClass('js-load-replace')) {
                                                    el.remove();                                    // Delete element
                                            }

                                            // Loading href into target
                                            if ( el.hasClass('js-load-before') ) {
                                                    target.prepend(html);                           // Insert response before the current element
                                            } else if( el.hasClass(('js-load-inner')) ) {
                                                    target.html(html);                              // Insert response inside (at the end) of the current element
                                            } else if( el.hasClass('js-load-replace') ) {
                                                    if (html !== '' && html !== 'ERROR') {          // Replace the current element by the response
                                                        target.replaceWith(html);
                                                    }
                                            } else if ( el.hasClass('js-remove')) {
                                                    target.remove();                                // Remove the current element
                                            } else {
                                                    target.append(html);                            // Insert response after the current element
                                            }
                                        });

                                        var new_input = $(html).find('.tm-input').length;
                                        if ( new_input > 0 ){
                                            initTagManagers();
                                        }

                                        el.trigger('js_loadmore_xhr_success', [target, el]);
                                }).complete(function() {
                                        // console.log('complete ajax');
                                        // console.log(refreshZone);

                                        if (el.hasClass('start-upload')){ // Add this class too autoload if symfony created form has add-file
                                            if ($( '.library-left li.selected' ).length > 0) {
                                                    var selected = $( '.library-left li.selected' );
                                                    $('#media_folder_id').val(selected.attr('data-entityid'));
                                            }
                                            $('.rpe-upload').addUpload();
                                        }

                                        if (refreshZone !== null) {
                                            // console.log('refreshZone =/= null');
                                            $(refreshZone).removeClass('loading').autoLoad();
                                        }

                                        if(el.hasClass('small-cards')){// Special small cards case
                                            setTimeout(function(){
                                                $('.grid').find('.card').addClass('card-small');
                                        },500);
                                    }
                                    el.trigger('js_loadmore_xhr_complete', [target, el]);

                            });
                        });
                };
                event.preventDefault();
        });
});

$.fn.autoLoad = function() {
        // console.log('launching autoload')
        $.each(this, function(i, item){
        // Vars
        var el           = $(item),
            href         = el.data('autoload'),
                target       = el,
                async        = el.data('async'),
                delay        = el.data('delay'),
                loadingClass = el.data('loadclass') || 'loading';

                /*console.log('el', el);*/

                if (el.hasClass('timeline-post-comment')) {
                        // console.log('haz timeline-post-comment')
                }

                if ( el.hasClass(loadingClass) ) {
                        return false;
                } else {
                        el.addClass(loadingClass);

                        // Ajax
                        if (typeof delay == 'undefined') {
                            delay = 0;
                        }

                        if (typeof async == 'undefined') {
                            async = true;
                        } else {
                            async = (async === "true");
                        }

                        setTimeout(function(){
                            $.ajax({
                                    async: async,
                                    type: "POST",
                                    url: href,
                                    cache: false
                            }).success(function(html) {
                                    // console.log('success');
                                    el.html('');
                                    $(target).html(html);
                                    $(target).find('.js-autoload').autoLoad();

                                    var new_input = $(target).find('.tm-input').length;
                                    if ( new_input > 0 ){
                                        initTagManagers();
                                    }

                                    el.trigger('js_autoload_xhr_success', [target, el]);
                            }).error(function(){
                                    el.trigger('js_autoload_xhr_error', [target, el]);
                            });
                        }, delay);
                }
        });
}
})(jQuery);