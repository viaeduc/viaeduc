(function($, window, document) {
    'use strict';
    $(document).ready(function() {

        // var docHeight = window.innerHeight,
        //     docHeight = docHeight+'px',
        //     docWidth  = window.innerWidth,
        //     docWidth  = docWidth+'px';

// console.log('menu js ready');
        // ================
        // Common scripts
        // ================

        // Ajax Load Submenu Middle Part
        var loadSubMiddle = function(self) {
            var target_elt = self.next().find('.sub-middle'),
                target_url = target_elt.attr('data-clickload');

            if (target_url !== undefined) {
                $.ajax({
                    url: target_url,
                    success: function(response) {
                        $(response).hide().insertBefore(target_elt).fadeIn(300);
                        target_elt.remove();
                    }
                });
            }
        };

        // Icon search trigger

        $(document.body).on('click', '.btn-icon', function(){
            var $this    = $(this),
                $formBtn = $this.parent().find('input[type=submit]');

            var content = $.trim($this.parent().find('#search_query').val());
            if(content != ""){
                if($this.hasClass('active')){
                    return false;
                } else {
                    // console.log('not active firing submit');
                    $this.addClass('active');
                    $formBtn.trigger('click');
                }
            }
        });

        // ================
        // Desktop specific
        // ================
        if (!$('body').hasClass('mobile')) {
            // console.log('regular');
            // TEST

            var $menu_items = $('#main_nav .menu-item:not(:first-child) > a');

            // First navigation level click event => Submenu behavior
            $menu_items.on('click', function(e) {
                // console.log('in first instance :');
                // console.log('--clicked');
                e.stopPropagation();
                e.preventDefault();

                var $header     = $('header'),
                    target_name = $(this).attr('data-target'),
                    $target     = $('.sub-menu[data-menu="' + target_name + '"]'),
                    publishBtn  = $('.publish-btn'),
                    $this       = $(this);

                if (publishBtn.hasClass('active')) {
                    publishBtn.removeClass('active');
                    $('.form-publication').stop().slideToggle();
                }

                if (!$header.hasClass('active')) {
                    // Submenu closed => Open Submenu
                    $header.addClass('active');
                    $('#config_menu').removeClass('open');
                    $target.addClass('active');
                    loadSubMiddle($(this));
                } else {
                    // Submenu opened
                    var $active_submenu     = $('.sub-menu.active'),
                        active_submenu_name = $active_submenu.attr('data-menu');
                    loadSubMiddle($(this));

                    if (active_submenu_name == target_name) {
                        // => Close submenu
                        $target.removeClass('active');
                        $header.removeClass('active');
                    } else {
                        // => Switch to Submenu
                        $target.addClass('active');
                        $active_submenu.removeClass('active');
                    }
                }

                if($this.hasClass('active')){
                    $this.removeClass('active');
                } else {

                    if($menu_items.hasClass('active')){
                        $menu_items.removeClass('active');
                    }

                    $this.addClass('active');
                }
            });

            // END TEST

            $('.menu-item a').on('click', function(e){
                // console.log('clicked on menu item desktop specific');
            })

            // @TODO problem: loadmores are prevented with this script

            // Prevents the dropdown-menu to close when something inside it is clicked
            /*$('#notifs .dropdown-menu').on('click', function(e) {
                // e.preventDefault();
                e.stopPropagation();
            });*/

            // ------------------------------------------------------------------- //
            //                              DROPDOWN                               //
            // Manually done since conflict between bootstrap and swiper scrollbar //
            // ------------------------------------------------------------------- //
            // Vars
            var $close_btns = $('.close-submenu'),
                notifs_msg_swiper = null,
                notifs_all_swiper = null;

            // Event Handlers on submenu close buttons
            $close_btns.on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                $('.sub-menu, header').removeClass('active');
            });

            // On click
            $('.notifs-msg-link, .notifs-all-link').on('click', function (e){
                var $this         = $(this),
                    $dropdown     = $('.dropdown-menu'),
                    $thisDropdown = $this.parent().find('.dropdown-menu');

                // Checking if this has an active class
                if ( $this.hasClass('active-notif') ){ // If class, close
                    $this.removeClass('active-notif');
                    $thisDropdown.removeClass('active-notif');
                    $('.menu-click-wrapper').remove();
                    return false;
                } else { // No active class
                    // Checking if another dropdown is open, if it is, closing it
                    $dropdown.each(function(){
                        if ( $dropdown.hasClass('active-notif') ) {
                            $(this).removeClass('active-notif');
                            $(this).parent().find('a:first-child').removeClass('active-notif');
                            // console.log('removed active class')
                        }
                    });

                    $this.addClass('active-notif');
                    $thisDropdown.addClass('active-notif');

                    var clickWrapper = '<div class="menu-click-wrapper"></div>';

                    $('body').prepend(clickWrapper);

                    $('.menu-click-wrapper').on('click', function(){
                        // console.log('clicked on menu click wrapper !');
                        $this.trigger('click');
                    });

                    // Checking wich link has been clicked
                }
            });
        } else {
            // =========================
            // Mobile / tablet specific
            // =========================

            // $('.menu-link').on('click', function(){
            // 	console.log('clicked on menu-link');
            // });

            // Initialise sidebars
            // ===================
            // Main nav sidebar
            var Main_nav_sidebar = new mlPushMenu(document.getElementById('main_nav'),
                document.getElementById('logo'), {
                    backClass: 'mp-back',
                    pusherId: 'mp-pusher',
                    itemClass: 'menu-item',
                    position: 'left'
                });

            // Message notification sidebar
            var Msg_notifs_sidebar = new mlPushMenu(document.getElementById('notifs_msg_sidebar'),
                document.getElementById('notifs_all'), {
                    backClass: 'mp-back',
                    pusherId: 'mp-pusher',
                    position: 'right'
                });

            // Main notification sidebar
            var All_notifs_sidebar = new mlPushMenu(document.getElementById('notifs_all_sidebar'),
                document.getElementById('notifs_msg'), {
                    backClass: 'mp-back',
                    pusherId: 'mp-pusher',
                    position: 'right'
                });
        }
    });
})(window.jQuery, window, document);
