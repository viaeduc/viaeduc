$(document).on('ready', function (){
    // Vars //
    var $menuItems   = $('.menu-list').children(),
        $profilCover = $('.profil-cover'),
        // Url vars for group sugestion & relation pending popin //
        url          = location.pathname,
        urlSplit     = url.split('/'),
        preLastUrl   = urlSplit[urlSplit.length - 2],
        lastUrl      = urlSplit[urlSplit.length - 1],
        fullUrl      = preLastUrl+'/'+lastUrl;

    // --------------------------------------- //
    //                  MENU                   //
    // These modals html are in home.html.twig //
    // --------------------------------------- //

    // Main //
    if (typeof main_tutorial != 'undefined' && main_tutorial == true) {
        $('.main-tutorial-1').modal('show');
    }

    // Profil //
    if (typeof tutorial_menu_profil_enabled != 'undefined' && tutorial_menu_profil_enabled == true) {
        // Adding class to use after click //
        $menuItems.eq(1).children('a').addClass('menu-profil-tutorial');

        // Click event //
        $('.menu-profil-tutorial').on('click', function (e){
            if($(this).hasClass('modal-opened')){
                return false;
            }

            $(this).addClass('modal-opened');
            $('.menu-profil-tutorial-modal-1').modal('show');
        });

        // Show event //
        $('.menu-profil-tutorial-modal-1, .menu-profil-tutorial-modal-2, .menu-profil-tutorial-modal-3').on('show.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.addClass('tutorial-show');
        }).on('hide.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.removeClass('tutorial-show');
        });

        // console.log('Menu profil tutorial is on');
    }

    if (typeof tutorial_menu_content_enabled != 'undefined' && tutorial_menu_content_enabled == true) {
        $menuItems.eq(2).children('a').addClass('menu-content-tutorial');

        $('.menu-content-tutorial').on('click', function (e){
            if($(this).hasClass('modal-opened')){
                return false;
            }

            $(this).addClass('modal-opened');
            $('.menu-content-tutorial-modal-1').modal('show');
        });

        $('.menu-content-tutorial-modal-1, .menu-content-tutorial-modal-2, .menu-content-tutorial-modal-3, .menu-content-tutorial-modal-4').on('show.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.addClass('tutorial-show');
        }).on('hide.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.removeClass('tutorial-show');
        });

        // console.log('Menu content tutorial is on');
    }

    if (typeof tutorial_menu_group_enabled != 'undefined' && tutorial_menu_group_enabled == true) {
        $menuItems.eq(3).children('a').addClass('menu-group-tutorial');

        $('.menu-group-tutorial').on('click', function (e){
            if($(this).hasClass('modal-opened')){
                return false;
            }

            $(this).addClass('modal-opened');
            $('.menu-group-tutorial-modal-1').modal('show');
        });

        $('.menu-group-tutorial-modal-1, .menu-group-tutorial-modal-2').on('show.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.addClass('tutorial-show');
        }).on('hide.bs.modal', function(){
            var $this = $(this),
                $header = $('header').find('.header-wrapper');

            $header.removeClass('tutorial-show');
        });

        // console.log('Menu group tutorial is on');
    }

    if (typeof tutorial_relation_enabled != 'undefined' && tutorial_relation_enabled == true) {
        setTimeout(function(){
            $('.menu-relation-tutorial-modal-1').modal('show');
        },1000);


        console.log('Relations page tutorial is on');
    }

    // ----- //
    // PAGES //
    // ----- //

    if (typeof profil_tutorial_enabled != 'undefined' && profil_tutorial_enabled == true) {
        setTimeout(function(){ // Timeout to let dom & bootstrap load, or else bugs
            $('.profil-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Profil tutorial is on')
    }

    if (typeof account_tutorial_enabled != 'undefined' && account_tutorial_enabled == true) {
        setTimeout(function(){
            $('.account-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Account tutorial is on')
    }

    if (typeof publish_tutorial_enabled != 'undefined' && publish_tutorial_enabled == true) {
        setTimeout(function(){
            $('.publish-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Publish tutorial is on')
    }

    if (typeof question_tutorial_enabled != 'undefined' && question_tutorial_enabled == true) {
        setTimeout(function(){
            $('.question-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Question tutorial is on')
    }

    if (typeof agenda_tutorial_enabled != 'undefined' && agenda_tutorial_enabled == true) {
        setTimeout(function(){
            $('.agenda-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Agenda tutorial is on')
    }

    if (typeof question_page_tutorial_modal != 'undefined' && question_page_tutorial_modal == true) {
        setTimeout(function(){
            $('.question-page-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Question page tutorial is on')
    }

    if (typeof media_tutorial_modal != 'undefined' && media_tutorial_modal == true) {
        setTimeout(function(){
            $('.media-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Media page tutorial is on')
    }

    if (typeof create_group_tutorial_modal != 'undefined' && create_group_tutorial_modal == true) {
        setTimeout(function(){
            $('.create-group-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Create group page tutorial is on')
    }

    if (typeof group_suggestion_tutorial_modal != 'undefined' && group_suggestion_tutorial_modal == true) {
        setTimeout(function(){
            if (fullUrl == 'groups/suggested') {
                $('.groups-tutorial-modal-1').modal('show');
            }
        },1000);

        // console.log('Group suggestions page tutorial is on')
    }

    if (typeof group_page_tutorial_modal != 'undefined' && group_page_tutorial_modal == true) {
        setTimeout(function(){
            $('.group-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Group page tutorial is on')
    }

    if (typeof relation_pending_tutorial_modal != 'undefined' && relation_pending_tutorial_modal == true) {
        setTimeout(function(){
            if (fullUrl == 'relation/pending') {
                $('.waiting-list-tutorial-modal-1').modal('show');
            }
        },1000);

        // console.log('Relation pending page tutorial is on')
    }

    if (typeof search_tutorial_modal != 'undefined' && search_tutorial_modal == true) {
        setTimeout(function(){
            $('.search-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Search page tutorial is on')
    }

    if (typeof publications_tutorial_enabled != 'undefined' && publications_tutorial_enabled == true) {
        setTimeout(function(){
            $('.publications-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Publications page tutorial is on')
    }

    if (typeof publications_draft_tutorial_enabled != 'undefined' && publications_draft_tutorial_enabled == true) {
        setTimeout(function(){
            $('.publications-draft-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Publications draft page tutorial is on')
    }

    if (typeof inbox_tutorial_enabled != 'undefined' && inbox_tutorial_enabled == true) {
        setTimeout(function(){
            $('.inbox-tutorial-modal-1').modal('show');
        },1000);

        // console.log('Publications draft page tutorial is on')
    }

    // ------------ //
    // MODAL EVENTS //
    // ------------ //

    // Main //
    $('.main-tutorial-2').on('show.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.header-wrapper');

        $header.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.header-wrapper');

        $header.removeClass('tutorial-show');
    });

    $('.main-tutorial-3').on('show.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.section-search');

        $header.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.section-search');

        $header.removeClass('tutorial-show');
    });

    $('.main-tutorial-4').on('show.bs.modal', function(){
        var $this   = $(this),
            $body   = $('.wrapper');

        $body.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $this   = $(this),
            $body   = $('.wrapper');

        $body.removeClass('tutorial-show');
    });

    // Menu - Profil //
    $('.menu-profil-tutorial-modal-1, .menu-profil-tutorial-modal-2, .menu-profil-tutorial-modal-3, .menu-content-tutorial-modal-1, .menu-content-tutorial-modal-2, .menu-content-tutorial-modal-3, .menu-content-tutorial-modal-4, .menu-group-tutorial-modal-1, .menu-group-tutorial-modal-2, .menu-relation-tutorial-modal-1').on('show.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.header-wrapper');

        $header.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $this   = $(this),
            $header = $('header').find('.header-wrapper');

        $header.removeClass('tutorial-show');
    });

    // Profil //
    $('.profil-tutorial-modal-1').on('show.bs.modal', function(){
        var $profilCover = $('.profil-cover');

        $profilCover.find('.cover-left').addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $profilCover = $('.profil-cover');

        $profilCover.find('.cover-left').removeClass('tutorial-show');
    });

    $('.profil-tutorial-modal-2').on('show.bs.modal', function(){
        var $relationBox = $('.sidebar').find('.side-componant:first-child');

        $relationBox.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $relationBox = $('.sidebar').find('.side-componant:first-child');

        $relationBox.removeClass('tutorial-show');
    });

    $('.profil-tutorial-modal-3').on('show.bs.modal', function(){
        var $relationBox = $('.sidebar').find('.side-componant:nth-child(2)');

        $relationBox.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $relationBox = $('.sidebar').find('.side-componant:nth-child(2)');

        $relationBox.removeClass('tutorial-show');
    });

    $('.profil-tutorial-modal-4').on('show.bs.modal', function(){
        var $publishBox = $('.form-publication');

        $publishBox.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $publishBox = $('.form-publication');

        $publishBox.removeClass('tutorial-show');
    });

    $('.profil-tutorial-modal-5').on('show.bs.modal', function(){
        var $profilLink = $('.nav-tabs');

        $profilLink.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $profilLink = $('.nav-tabs');

        $profilLink.removeClass('tutorial-show');
    });

    // Account //
    $('.account-tutorial-modal-1').on('show.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.addClass('tutorial-show');
        $tabs.find('.tab:nth-child(-n+3)').addClass('tutorial-show');
        $tabs.find('.tab:nth-child(n+4)').addClass('tutorial-hide');
    }).on('hide.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.removeClass('tutorial-show');
        $tabs.find('.tab:nth-child(-n+3)').removeClass('tutorial-show');
        $tabs.find('.tab:nth-child(n+4)').removeClass('tutorial-hide');
    });

    $('.account-tutorial-modal-2').on('show.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.addClass('tutorial-show');
        $tabs.find('.tab').addClass('tutorial-hide');
        $tabs.find('.tab:nth-child(1)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(4)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(4)').addClass('tutorial-show');

    }).on('hide.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-hide');
    });

    $('.account-tutorial-modal-3').on('show.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.addClass('tutorial-show');
        $tabs.find('.tab').addClass('tutorial-hide');
        $tabs.find('.tab:nth-child(1)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(5)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(5)').addClass('tutorial-show');

    }).on('hide.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-hide');
    });

    $('.account-tutorial-modal-4').on('show.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.addClass('tutorial-show');
        $tabs.find('.tab').addClass('tutorial-hide');
        $tabs.find('.tab:nth-child(1)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(6)').removeClass('tutorial-hide');
        $tabs.find('.tab:nth-child(6)').addClass('tutorial-show');

    }).on('hide.bs.modal', function(){
        var $tabs = $('.nav-tabs');

        $tabs.removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-show');
        $tabs.find('.tab').removeClass('tutorial-hide');
    });

    // Publish //
    $('.publish-tutorial-modal-1').on('show.bs.modal', function(){
        var $tabs = $('.publications-tab-wrapper');

        $tabs.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $tabs = $('.publications-tab-wrapper');

        $tabs.removeClass('tutorial-show');
    });

    $('.publish-tutorial-modal-2').on('show.bs.modal', function(){
        var $cke = $('.cke');

        $cke.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $cke = $('.cke');

        $cke.removeClass('tutorial-show');
    });

    $('.publish-tutorial-modal-3').on('show.bs.modal', function(){
        var $divs = $('.ressource-edit-wrapper');

        $("html, body").animate({ scrollTop: $(document).height() }, "slow");

        $divs.addClass('tutorial-show');
        $divs.find('li').addClass('tutorial-hide');
        $divs.find('li:nth-child(n+3)').removeClass('tutorial-hide');
        $divs.find('li:nth-child(n+3)').addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $divs = $('.ressource-edit-wrapper');

        $divs.removeClass('tutorial-show');
        $divs.find('li').removeClass('tutorial-hide');
        $divs.find('li:nth-child(n+3)').removeClass('tutorial-hide');
        $divs.find('li:nth-child(n+3)').removeClass('tutorial-show');
    });

    $('.publish-tutorial-modal-4').on('show.bs.modal', function(){
        var $index = $('.open-indexation');

        $index.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $index = $('.open-indexation');

        $index.removeClass('tutorial-show');
        $("html, body").animate({ scrollTop: 0 }, "slow");
    });

    // Search //
    $('.search-tutorial-modal-1').on('show.bs.modal', function(){
        var $index = $('.content').find('.search-tab-wrapper');

        $index.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $index = $('.content').find('.search-tab-wrapper');

        $index.removeClass('tutorial-show');
    });

    $('.search-tutorial-modal-2').on('show.bs.modal', function(){
        var $index = $('.sidebar');

        $index.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $index = $('.sidebar');

        $index.removeClass('tutorial-show');
    });

    $('.search-tutorial-modal-3').on('show.bs.modal', function(){
        var $index = $('.content').find('.tab-content');

        $index.addClass('tutorial-show');
    }).on('hide.bs.modal', function(){
        var $index = $('.content').find('.tab-content');

        $index.removeClass('tutorial-show');
    });

    // --------------- //
    // SPECIFIC CLICKS //
    // --------------- //

    // Menu //
    $(document.body).on('click', '.tutorial-modal-next, .tutorial-modal-prev', function(e){
        var $this  = $(this),
            $modal = $this.parent().parent().parent().parent();

        $modal.modal('hide');
    });

    $(document.body).on('click', '.tutorial-modal-close', function(e){
        var $this   = $(this);
            $modals = $this.parent().parent().parent().parent().parent().find('.modal');

        if($modals.find('.new-etherpad-input')){
            $('.new-etherpad-input').removeClass('error');
        }

        $modals.each(function(){
            $(this).modal('hide');
        })
    })

});