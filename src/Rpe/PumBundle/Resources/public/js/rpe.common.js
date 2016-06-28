// JS COMMON //
;(function(){
    initTagManagers = function() {
        // ===================
        //  READ ME
        // ===================

        //  What does it do ?
        // -------------------
        //  It initiates TagsManager [TM] and (if wanted) TypeAhead [TA] elements

        //  Initiation class      => '.tm-input'
        //  For TagManager
        // ----------------
        //  data-ajax-target      => [string] Selector of the hidden tags wrapper needed for form submition / validation ( ex: '#resource_pajaxobj_disciplines' )
        //  data-ajax-target-list => [string] Selector of the visible tags wrapper needed for deletion ( ex: '#resource_pajaxobj_disciplines_second' )

        //  For Typeahead
        // ---------------
        //  data-ajax-url         => [string] Url to the prefetched json list
        //  data-storename        => [string] Unique name
        //  data-ttl              => [number or boolean] possible values: true/default => 86400000ms (1day) | false => 0ms | number 80 => 80ms

        // =======
        //  TO DO
        // =======
        //  Check which data-attribute is available
        //  Initiate [TM] or [TA] or both, depending on the previous data-attributes
        //  Currently it's only both


        if (typeof(tagManagerVars) === 'undefined') {
            var tagManagerVars = Array();
        }
        $('.tm-input').each( function(index, item){
        if (!$(item).hasClass('initialized')) {
// console.log('typeahead NOT init, initing');
            var self              = $(item),
                hiddenContainerId = self.data('ajax-target'),
                tagListId         = self.data('ajax-target-list'),
                prefetchId        = self.data('ajax-target-list').substr(1),
                storeName         = self.data('storename'),
                prefetchUrl       = self.data('ajax-url'),
                cacheTtl          = 1800,
                parentDiv         = self.parent().parent().parent().find('info-bubble-link'),
                moreBtn           = parentDiv.selector;

                // console.log('self in tag :', self);

// console.log('----- VARS ------');
// console.log('Self : '+self);
// console.log($(self));
// console.log('--')

// console.log('hiddenContainerId : '+hiddenContainerId);
// console.log($(hiddenContainerId));
// console.log('--')

// console.log('tagListId : '+tagListId);
// console.log($(tagListId));
// console.log('--')

// console.log('prefetchId : '+prefetchId);
// console.log($(prefetchId));
// console.log('-----')

// console.log('storeName : '+storeName);

// console.log('prefetchUrl : '+prefetchUrl);


// console.log('----- ENDVARS ------');

            var bloodhoundData = new Bloodhound({
                datumTokenizer: function(d) {
                    return Bloodhound.tokenizers.whitespace(d.value);
                },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url : prefetchUrl,
                    ttl : cacheTtl
                }
            });

            var promise = bloodhoundData.initialize();

            tagManagerVars[index] = self.tagsManager({
                tagsContainer        : tagListId,
                hiddenContainerId    : hiddenContainerId,
                backspace            : [],
                onlyTagList          : true,
                tagList              : null,
                fillInputOnTagRemove : false,
                maxParallelRequests  : 1,
                storeName            : storeName
            });

            self.data('bloodhoundData', bloodhoundData);

            self.typeahead(null, {
                name      : prefetchId,
                source    : bloodhoundData.ttAdapter(),
                minLength : 0
            }).on('typeahead:opened',function (){
// console.log('Typeahead opened');
            }).on('typeahead:selected',function (e, d){
// console.log('Typeahead selected');
// console.log(d);
                self.tagsManager('pushTag', d);
            }).on("click", function () {
// console.log('Typeahead cliked');
                $(this).typeahead( 'open');
            });
            // console.log($( hiddenContainerId+' .item'));
            promise.done(function(){

// console.log('----- Promis.done -----');
// console.log(this);
// console.log('storeName : '+storeName);
// debugger;
// console.log('prefetchUrl : '+prefetchUrl);

                $( hiddenContainerId+' .item').each( function( index, element ){
                    var item = {
                        id    : $(element).attr('item-id'),
                        value : $(element).attr('item-value')
                    };

                    self.tagsManager('pushTag', item);
                });
            });

            // Avoid Typeahead behavior that set the selected value on blur
            // self.on('blur', function() { $(this).val(''); });

            self.addClass('initialized');

            // $(moreBtn).on("click", function(){
            //     console.log('clicked on btn');

            //     var modal = $(document.body).find('modal-info-list');

            //     if (modal.hasClass('in')){
            //         console.log('modal open');
            //     } else {
            //         console.log('modal closed');
            //     }
            // })
        }
        });



        return tagManagerVars;
    }

    // ------------ //
    // INIT PLUGINS //
    // ------------ //

    rpeInitPlugins = function(container) {
        if (typeof container === 'undefined') {
            container = $(document.body);
        }




        var acceptCookie = $.cookie('accept_cookies_rpe');

        if(acceptCookie == null){
            $('.cookie-notice').addClass('active');
        }

        $(document.body).on('click', '.cookie-notice-close', function(event){
            $.cookie('accept_cookies_rpe', '1', { expires: 365, path: '/' });
            $('.cookie-notice').removeClass('active');

            event.preventDefault();
        });

        // CUSTOM SELECT
        // -------------
        container.find('select').selectpicker({
            noneSelectedText : 'Aucune sélection',
            countSelectedText: ' ',
        });

        // DATEPICKERS
        // -----------
        container.find('.calendar-datepicker input, input.calendar-datepicker').datetimepicker({
            weekStart: 1,
            autoclose: true,
            minView: 2,
            language: 'fr',
            // format: 'dd-mm-yyyy'
        });

        var birthdayPickerEnd = new Date();
        birthdayPickerEnd.setFullYear(birthdayPickerEnd.getFullYear() - 16);
        container.find('.calendar-birthdaypicker input, input.calendar-birthdaypicker').datetimepicker({
            weekStart: 1,
            autoclose: true,
            minView: 2,
            startView: 4,
            language: 'fr'
            // format: 'dd-mm-yyyy'
        });

        container.find('.calendar-datetimepicker-single input, input.calendar-datetimepicker-single').datetimepicker({
            weekStart: 1,
            autoclose: true,
            language: 'fr'
        });

        container.find('.calendar-datetimepicker input, input.calendar-datetimepicker').datetimepicker({
            weekStart: 1,
            autoclose: true,
            language: 'fr'
        }).on('changeDate', function(ev){
            var i     = $(ev.target),
                date  = i.val(),
                tgt   = $(i.data('date-constraint-element')),
                type  = i.data('date-constraint-type');

            if ('end' === type) {
                method = 'setEndDate';
            } else if ('start' === type) {
                method = 'setStartDate';
                tgt.val('');
                tgt.datetimepicker('update');
            }
            if (typeof method !== undefined) {
                tgt.datetimepicker(method, date);
            }
        });

        // File upload
        container.find('.rpe-upload').addUpload();
        container.find('.remove-upload').removeUpload();
    }

    // ------ //
    // COMMON //
    // ------ //

    $(document).ready(function ($) {
        // console.log(window.location.host);

        // Check for Error 401, if so, redirect
        $( document ).ajaxError(function( event, jqxhr, settings, exception ) {
            if ( jqxhr.status== 401 ) {
                window.location.replace(window.location.host);
            }
        });
        // -------------------------------- //
        // MOBILE (Portrait) & IE DETECTION //
        // -------------------------------- //
        var isIE = (navigator.userAgent.indexOf("MSIE") != -1);
        if (isIE){


        } else {
            if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
                // Orientation change functions
                function portraitScreen(){
                    var portraitDiv       = '<div class="mobile-portrait">' +
                                                '<div class="mobile-center">' +
                                                    '<img src="/bundles/rpepum/images/logo.svg" class="turnaround-logo">' +
                                                    '<img src="/bundles/rpepum/images/turnaround.png">' +
                                                    '<h3 class="turnaround-title">Merci de retourner votre support</h3>' +
                                                '</div>' +
                                            '</div>',
                        $portraitDiv      = $('.mobile-portrait'),
                        portraitDivHeight = $(document).height();

                    $(".global-wrapper").prepend(portraitDiv);
                    $portraitDiv.css({
                        'height'      : portraitDivHeight,
                        'line-height' : portraitDivHeight
                    });
                }

                function landscapeScreen(){
                    var $portraitDiv = $('.mobile-portrait');

                    if($('body').find($portraitDiv)){
                        $portraitDiv.remove();
                    } else {

                    }
                }

                // Orientation change event
                window.addEventListener('orientationchange', handleOrientation, false);

                // Orientation change options
                function handleOrientation() {
                    if (orientation == 0) { // Portrait
                        portraitScreen();
                    }
                    else if (orientation == 90) { // Landscape
                        landscapeScreen()
                    }
                    else if (orientation == -90) { // Landscape
                        landscapeScreen()
                    }
                    else if (orientation == 180) { // Portrait
                        portraitScreen();
                    }
                    else {

                    }
                }

                // Mobile Check
                if (window.matchMedia("(orientation: portrait)").matches) {
                    portraitScreen();
                }
            }
        }

        // ---------------------------------------------------------------------------------- //
        //                                   LIKE HOVER                                       //
        // To use it :                                                                        //
        //    - Add the class 'recomended-users' to the div / a                               //
        //    - Add "data-users" attribute to div / a (link to users who recomended the post) //
        // Styled in "common.scss"                                                            //
        // ---------------------------------------------------------------------------------- //

        $(function() {
            var timeoutId;

            $(".ajax-list-users").hover(function() {
                var $this     = $(this),
                    likedList = '<ul class="liked-list"></ul>',
                    dataUsers = $this.data('users') || 'none'; // Use attribute "data-users" to add link for load

                if(dataUsers == 'none'){
                    return false;
                }

                if (!timeoutId) {
                    timeoutId = window.setTimeout(function() {
                        // Updating var
                        timeoutId = null;

                        // Loading
                        $.ajax({
                            type: "POST",
                            url: dataUsers,
                            cache: false
                        }).success(function(html) {
                            // console.log(html.length);

                            if(html.length == 11){ // If length is 11, no data
                                return false;
                            }

                            // Apending
                            $this.append(likedList);

                            // Positioning
                            var $list     = $this.find('.liked-list');

                            // Injecting HTML
                            $list.html(html);

                            var divHeight = $list.height(),
                            divHeight = divHeight+20; // +20px for padding

                            $list.css({
                                'top' : '-'+divHeight+'px'
                            });
                        }).error(function(){
                            console.log('Error');
                        });
                   }, 1000);
                }
            }, function () {
                var $this = $(this),
                    $list = $this.find('.liked-list');

                if (timeoutId) {
                    window.clearTimeout(timeoutId);
                    timeoutId = null;
                }
                else {
                    $list.remove(); // Completly removes this list
                }
            });
        });

        // Allows ajax form in bootstrap modals
        $(document.body).on('submit', 'form[data-async]', {}, function(event) {
            var $form = $(this);
            var $target = $($form.attr('data-target'));

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serialize(),

                success: function(data, status) {
                    $target.html(data);
                    $target.addClass('sent-success');
                }
            });

            event.preventDefault();
        });

        // ===============
        // Dates relatives
        // ===============
        $(document).ajaxComplete(function() {
            $.timeago.settings.strings = {
               prefixAgo: "il y a",
               prefixFromNow: "d'ici",
               seconds: "moins d'une minute",
               minute: "une minute",
               minutes: "%d minutes",
               hour: "une heure",
               hours: "%d heures",
               day: "un jour",
               days: "%d jours",
               month: "un mois",
               months: "%d mois",
               year: "un an",
               years: "%d ans"
            };
            $(".timeago").timeago();
        });
        // ===============

        $(document).on('ready', function(){
            if($(document.body).find('.owl-carousel')){
                var $slider = $('.owl-carousel'),
                    nbSlide = $('.slide').length,
                    isLoop = true;

                if (nbSlide == 1) {
                    isLoop = false;
                    $slider.addClass('one-item');
                }

                $slider.owlCarousel({
                    items              : 1,
                    nav                : true,
                    center             : true,
                    mousedrag          : false,
                    touchDrag          : true,
                    freeDrag           : false,
                    pullDrag           : false,
                    loop               : isLoop,
                    autoPlay           : true,
                    autoplayTimeout    : sliderTimeout,
                    autoplayHoverPause : true,
                    navText            : [
                        '&lt;',
                        '&gt;'
                    ]
                });
            }

            if($(document.body).find('textarea').not('.ckeditor')){
                $('textarea').expanding();
            }
        });

        $(document).ajaxComplete(function() {
            if($(document.body).find('textarea').not('.ckeditor')){
                $('textarea').expanding();
            }

            // if($(document.body).find('.owl-carousel')){
            //     console.log('found after ajax');

            //     var $slider = $('.owl-carousel');

            //     $slider.owlCarousel({
            //         items              : 1,
            //         nav                : true,
            //         center             : true,
            //         mousedrag          : false,
            //         navText            : [
            //                                 '<',
            //                                 '>'
            //                             ],
            //         loop               : true,
            //         autoPlay           : true,
            //         autoplayTimeout    : 2000,
            //         autoplayHoverPause : true
            //     });
            // }

        });

        $("body").on('focus', 'textarea', function() {
            firstEnter = false;
        });

        $("body").on('focus', 'textarea' ,function(event) {
            // console.log('keyup');
            if($(this).parent().parent().find('.mini-btn')){
                 var el         = $(this).parent().parent().parent().find('.mini-btn');
                     firstEnter = true;

                if(el.hasClass('bigger-btn')){
                    // console.log('already has bigger btn');
                    return false;
                } else {
                    // console.log('no btn changing');
                    // console.log(el);
                    el.addClass('bigger-btn');
                    el.html('Envoyer');
                }

            }
        });

        $("body").on('blur', 'textarea' , function(event) {
            var el           = $(this),
                commentValue = el.val(),
                parentForm   = el.closest('.comment-form'),
                questionBtn  = parentForm.find('.mini-btn');

            if (commentValue == '') {
                questionBtn.removeClass('bigger-btn');

                if (parentForm.hasClass('question-form')) {
                    questionBtn.text('');
                }
            }
        });

        /*================================
        =            CKEDITOR            =
        ================================*/
        // Helper function to get parameters from the query string.
        function getUrlParam( paramName ) {
            var reParam = new RegExp( '(?:[\?&]|&)' + paramName + '=([^&]+)', 'i' ) ;
            var match = window.location.search.match(reParam) ;

            return ( match && match.length > 1 ) ? match[ 1 ] : null ;
        }

        $(document.body).on('click', '.js-ckeditor-callfunction', function(ev){
            var el         = $(this),
                funcNum    = getUrlParam( 'CKEditorFuncNum' ),
                fileUrl    = el.attr('data-ckeditor-fileurl') || el.attr('href');

            window.opener.CKEDITOR.tools.callFunction( funcNum, fileUrl );
            window.close();
        });

        var ckeditorTextarea = $('.ckeditor');

        if(ckeditorTextarea.length) {
            CKEDITOR.on('currentInstance', function(e) {
                CKEDITOR.instances.resource_content.updateElement();
            });
        }

        // ---------- //
        // VALIDATION //
        // ---------- //
        $(document.body).on('beforeValidation', '.ckeditor', function(e){
            if (ck = CKEDITOR.instances[$(this).attr('id')]) {
                ck.updateElement();
            }
        });
        // $(document.body).on('beforeValidation', '#resource_disciplines', function(e){
        //     var $disciplines = $(this);
        //     var $tagDiv = $disciplines.parent().parent().parent().find('.tag-list');
        //     if($tagDiv.children().length < 1) {
        //         $disciplines.attr('data-validation', 'required');
        //     }
        // });
        $.validate({
            modules : 'security',
            validateOnBlur : false,

            onValidate : function(f) {
                // Vars
                var $groupImage  = $('#group_picture_file');
                    $disciplines = $(f).find('#resource_disciplines'),
                    $teaching    = $(f).find('#resource_teachingLevels');

                // ----------------------- //
                // Create group file error //
                // ----------------------- //
                if($groupImage.length){
                    if($groupImage.val()){
                        var $checkbox = $groupImage.parent().parent().find('#check-one');

                        if ($checkbox.prop('checked') !== true){
                            return {
                                element : $checkbox,
                                message : 'Merci de vérifier que votre image est libre de droit.'
                            }
                        }
                    }
                }
            },

            // Error special JS //
            onError : function(f) {
                console.log('ERROR')

                // Vars
                var $selectInput = $(f).find('select'),
                    $radioInput  = $(f).find('.radio-buttons'),
                    $ckInputs    = $(f).find('.ckeditor');

                if ($(f).attr('class') == 'new-form-styles professional-experiences-form has-validation-callback'){ // Special actions for professional experiences form errors
                    var originalHeight = $(f).height();
                        newHeight      = originalHeight+20,
                        $timeDiv       = $(f).find('.date-picker-wrapper');

                    $(f).parent().css({
                        height : newHeight
                    });


                    if($timeDiv.find('.has-error').length){
                        $timeDiv.css({
                            'margin' : '0 0 30px 0'
                        });
                    }
                }

                if ($(f).attr('class') == 'new-form-styles my-training-form has-validation-callback'){
                    var originalHeight = $(f).height();
                        newHeight      = originalHeight+20,
                        $timeDiv        = $(f).find('.date-picker-wrapper');

                    $(f).parent().css({
                        height : newHeight
                    });


                    if($timeDiv.find('.has-error').length){
                        $timeDiv.css({
                            'margin' : '0 0 30px 0'
                        });
                    }
                }

                if ($(f).attr('class') == 'new-form-styles ressource-edit-form-wrapper event-form has-validation-callback'){
                    var $timeDiv        = $(f).find('.calendar-datetimepicker');

                    if($timeDiv.has('.has-error')){
                        $timeDiv.css({
                            'margin' : '0 0 20px 0'
                        });
                    }
                }

                // ----------------------- //
                // Bootstrap select errors //
                // ----------------------- //
                if ($selectInput.length){
                    $selectInput.each(function(){
                        if ($(this).hasClass('error')){
                            var $this      = $(this),
                                $dropdown  = $this.next('.btn-group').find('.dropdown-toggle'),
                                $attrStyle = $this.attr('style'),
                                $attrError = $this.attr('current-error')

                            if (typeof $attrStyle !== 'undefined' && $attrStyle !== false) {
                                $dropdown.css('border','1px solid red');

                                $this.on('change', function(){
                                    $dropdown.css('border','1px solid #acacac');
                                })
                            }
                        }
                        if ($(this).hasClass('valid')) {
                            var $this      = $(this),
                                $dropdown  = $this.next('.btn-group').find('.dropdown-toggle')

                            $dropdown.css('border','1px solid #acacac');
                        }
                    });
                }

                // --------------- //
                // CKEditor errors //
                // --------------- //
                if ($ckInputs.length){
                    $.each($ckInputs, function(i, ckInput){
                        var $ckInput = $(ckInput);
                            $this = $ckInput.parent();

                        if ($this.hasClass('has-error')){
                            $ckInput.next('.cke').css('border', '1px solid red')
                        } else {
                            $ckInput.next('.cke').css('border', '1px solid #acacac');
                        }
                    });
                }

                // ----------------- //
                // Reposition errors //
                // ----------------- //
                if ($radioInput.length){
                    var $this        = $radioInput.find('.form-error'),
                        $countErrors = $this.parent().parent().find('.form-error').length;

                    if ($countErrors == 1){
                        if ($this.length) {
                            $this.clone().appendTo($radioInput.parent());
                            $this.remove();
                        }
                    } else {
                        // removing the error message added by plugin, since we moved it in the DOM
                        $this.remove();
                    }
                }
            },
            form : '.fav-discussion-form, .register-form, .first-form, .create-group-form, .ressource-form, .question-form, .event-form, .professional-experiences-form, .professional-experiences-edit-form, .my-training-form, .my-training-edit-form, .invitation-form, .create-editor-form, .password-reset-form'
        });

        // --------------------------- //
        //     TAG MANAGER GENERAL     //
        // --------------------------- //
        var initTags = initTagManagers();

        // -----------------
        // Change card style
        // -----------------
       $('#grid').on('js_autoload_xhr_success', function(){

            if (localStorage.getItem("card") == null){
                localStorage.setItem("card", "big");
            }

            if (localStorage.getItem("card") == 'big') {
                $('#big').trigger('click');
            } else  {
                $('#small').trigger('click');
            }
        });

        // Big button, remove small class from cards
        $('#big').on('click',function(event){
            localStorage.setItem("card", "big");

            if ($('.picto-small').hasClass('opac')){
                return false;
            } else {
                // // Temporary if
                // if ($('.card').hasClass('question')){
                //     $('a.title').replaceWith('<a href="#" class="title" title="">Quel est le meilleur livre pour connaître le monde ouvrier, le monde ouvrier, le droit syndicalet les droits des femmes ?</a>');
                // }
                $(this).removeClass('opac');
                $('.picto-small').addClass('opac');
                $('.grid').find('.card').not('.admin-card').removeClass('card-small');
                $('.grid').find('.page-loadmore').removeClass('small-cards');
                $('.grid').find('.card').not('.admin-card').find('.card-title').removeClass('small-card-style');
                $('.grid').find('.card').not('.admin-card').find('.card-published').find('a').show();
            }
            event.preventDefault();
        });

        // Small button, add small class on cards
        $('#small').on('click',function(event){
            localStorage.setItem("card", "small");

            if ($('.picto-big').hasClass('opac')){
                return false;
            } else {
                // // Temporary if
                // if ($('.card').hasClass('question')){
                //     $('a.title').replaceWith('<a href="#" class="title" title="">Quel est le meilleur livre pour connaître le monde ouvrier...</a>');
                // }
                $(this).removeClass('opac');
                $('.picto-big').addClass('opac');
                $('.grid').find('.card').not('.admin-card').addClass('card-small');
                $('.grid').find('.page-loadmore').addClass('small-cards');
                $('.grid').find('.card').not('.admin-card').find('.card-title').addClass('small-card-style');
                $('.grid').find('.card').not('.admin-card').find('.card-published').find('a').hide();
            }
            event.preventDefault();
        });

        $('#home_big').on('click',function(event){
            localStorage.setItem("card", "big");
        });

        $('#home_small').on('click',function(event){
            localStorage.setItem("card", "small");
        });

        // ------------ //
        // LINK PREVIEW //
        // ------------ //
        var linkPreviewDelay,
            linkPreviewUrlRegex = /(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/,
            linkPreviewBusy     = false;

        var getFirstUrlFromText = function(text) {
            var words = text.split(/\n|\r\n|\s|,| /),
                found = false;

            $.each(words, function(index, value) {
                if(false === found && linkPreviewUrlRegex.test(value)) {
                   found = value;
                }
            });

            return found;
        }

        var linkPreview = function(url, el) {
            $.ajax({
                url: el.attr('data-link-ajax'),
                data: {url: url},
                error: function(xhr, statusText) {
                    cleanPreviewLinkPreview(el);
                },
                success: function(data) {
                    if (typeof data.result == 'string' && data.result == 'OK' && typeof data.html == 'string') {
                        decorateLinkPreview(data, el);
                    } else {
                        cleanPreviewLinkPreview(el);
                    }
                },
                dataType: 'json'
            });
        }

        var cleanPreviewLinkPreview = function(el) {
            el.attr('data-linkpreview', null);

            if (el.next().hasClass('link_preview')) {
                el.next().remove();
            }

            fillInputLinkPreview(null, el);
        }

        var decorateLinkPreview = function(data, el) {
            if (el.next().hasClass('link_preview')) {
                el.next().remove();
            }

            fillInputLinkPreview(data.id, el);

            el.after(data.html);
        }

        var fillInputLinkPreview = function(id, el) {
            var form  = el.closest("form"),
                input = form.find('input[name*="link_preview_id"]'),
                found = (input.length == 1) ? true : false;

            if (found) {
                input.val(id);
            }
        }

        $(document.body).on('keyup', 'form input[type="text"].linkpreview, form textarea.linkpreview', function(e){
            clearTimeout(linkPreviewDelay);

            var el         = $(this),
                text       = el.val(),
                currentUrl = el.attr('data-linkpreview');

            linkPreviewDelay = setTimeout(function() {
                if (linkPreviewBusy === false && linkPreviewUrlRegex.test(text)) {
                    var urlFromText = getFirstUrlFromText(text);

                    if (currentUrl !== urlFromText) {
                        el.attr('data-linkpreview', urlFromText);
                        fillInputLinkPreview(null, el);
                        linkPreview(urlFromText, el);
                    }
                } else {
                    cleanPreviewLinkPreview(el);
                }
            }, 500);
        });

        $(document.body).on('submit', 'form', function(e) {
            var input = $(this).find('input[name*="link_preview_id"]'),
                found = (input.length == 1) ? true : false;

            if (found) {
                input.val(null);
            }

            clearTimeout(linkPreviewDelay);

            $(this).find('div.link_preview').remove();
        });


        // ----- //
        // OTHER //
        // ----- //

        // Ask Relationship
        $(document.body).on('click', '.relation_ask',function(event){
            var el = $(this);
            $.ajax({
                url: el.attr('data-url'),
                success: function(response){
                    if (response.result){
                        el.replaceWith('<span>Demande envoyée</span>');
                    }
                }
            });

            event.preventDefault();
        });

        $(document.body).on('change', '.js-select-href', function(ev){
            if(!$(this).hasClass('unactive')){
                window.location.href = this.value;
            }
        });


        $(document.body).on('click', 'input + .icon-calendar', function(){
            $(this).prev('input').trigger('focus');
        });

        // Autoloads
        if ($('.js-autoload').length > 0){
            $('.js-autoload').autoLoad();
        }


        // Delete the notification badge on click
        $('header').on('click', '.notif-icon.icon-bell', function() {
            $(this).next('.badge').text('');
            $.ajax( $(this).attr('data-href') );
        });
        $('header').on('click', '.notif-icon.icon-mail', function() {
            $(this).next('.badge').text('');
            $.ajax( $(this).attr('data-href') );
        });
        $('header').on('click', '.notifs-all .notif-list .notif-item .close', function(e) {
            $(this).closest('.notif-item').remove();
            $.ajax( $(this).attr('href') );

            e.preventDefault();
        });

        // CONFIRM links
        $(document.body).on('click', '.js-confirm-link', function(ev){
            var el         = $(this),
                confirmMsg = el.data('confirm') || null;

            if (null !== confirmMsg && !confirm(confirmMsg)) {
                ev.preventDefault();
                return false;
            }
        });

        // INIT PLUGINS
        // ------------
        rpeInitPlugins();
        $(document.body).on('js_loadmore_xhr_success', function(ev, target){
            rpeInitPlugins(target);
        });
        $(document.body).on('js_autoload_xhr_success', function(ev, target){
            rpeInitPlugins(target);
        });

        // Permit modal reloading
        $(document.body).on('hidden.bs.modal', '.modal', function() {
            if($(this).hasClass('no-reload')){
                $('body').removeClass('modal-open');
                return false;
            }
            $(this).find('.modal-content').html('<span class="loader"></span>');
            $(this).removeData('bs.modal');

        });
    });
})(jQuery);

// function imagePreview(file, imageFileInput, imagePreview) {
//     var imageDiv  = imageFileInput.prev('img');
//     var reader    = new FileReader();
//     var image     = new Image();

//     if(imagePreview == true){
//         var imageDiv = imageFileInput.parent().parent().find('.uploaded-files-wrapper').find('.rpe-upload');
//     }

//     console.log(imageDiv.attr('class'))

//     reader.readAsDataURL(file);
//     reader.onload = function(_file) {
//         image.onload = function() {
//             var imgWidth  = this.width,
//                 imgHeight = this.height,
//                 imgType   = file.type,
//                 imgName   = file.name,
//                 imageSize = ~~(file.size/1024) +'KB';

//             if(coverImage == 1) {
//                 if(imgWidth < 837) {
//                     errorVar = 1;
//                     console.log(errorVar)
//                     alert('Merci de choisir une image avec des dimensions minimales de : 837px x 300px');
//                     return false;
//                 }

//                 if(imgHeight < 300) {
//                     errorVar = 1;
//                     console.log(errorVar)
//                     alert('Merci de choisir une image avec des dimensions minimales de : 837px x 300px');
//                     return false;
//                 }
//             } else {
//                 console.log('profil image')

//                 if(imgWidth < 117) {
//                     errorVar = 1;
//                     console.log(errorVar)
//                     alert.log('in  function error var = '+errorVar)
//                     alert('Merci de choisir une image avec des dimensions minimales de : 117px x 117px');
//                     return false;
//                 }

//                 if (imgHeight < 117) {
//                     errorVar = 1;
//                     console.log(errorVar)
//                     alert.log('in  function error var = '+errorVar)
//                     alert('Merci de choisir une image avec des dimensions minimales de : 117px x 117px');
//                     return false;
//                 }
//             }
//         };

//         if (errorVar == 0) {
//             console.log('no errors')
//             image.src    = _file.target.result; // url.createObjectURL(file);
//             imageDiv.attr('src', image.src);
//         } else {
//             return false;
//         }
//     };
// };