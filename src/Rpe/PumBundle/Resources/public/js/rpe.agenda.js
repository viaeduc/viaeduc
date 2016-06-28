(function($, window, document){
    'use strict';

    $(document).ready(function(){
        function createDivs(){
            // Vars
            var agendaRow   = '<div class="agenda-row"></div>',
                $wrapper    = $('.event-wrapper'),
                $cards      = $('.month-cards'),
                $card       = $('.agenda-click'),
                $lastCard   = $('.agenda-click:last-child'),
                $fourthCard = $('.agenda-click:nth-child(4n)'),
                cardTotal   = $wrapper.find($card).length;

            // --------------------------------------------- //
            //          Injecting agenda-row divs            //
            // This is done once after ajax finished loading //
            // --------------------------------------------- //
            //console.log(cardTotal); // Number of cards
            if($('.agenda-row').length){
                // console.log('Agenda row exists, not doing anything')
                return false;
            } else {
                // console.log('No agenda row, lets create it')
                if(cardTotal <= 4) {
                    // console.log('under or equal to 4, adding agendaRow after last child');
                    $lastCard.after(agendaRow);
                } else if (cardTotal % 4 === 0){
                    // console.log('Dividable by 4, adding only after each 4n');
                    $cards.addClass('more-rows'); // For nth-child fix
                    $fourthCard.after(agendaRow);
                } else if (cardTotal > 4){
                    // console.log('Over 4 cards, injecting agendaRow after every 4 cards and than after last card');
                    $cards.addClass('more-rows'); // For nth-child fix
                    $fourthCard.after(agendaRow);
                    $lastCard.after(agendaRow);
                } else {
                    // console.log('No cards, not injecting agendaRow');
                    return false;
                }
            }

            $('.event-wrapper').find('.agenda-click.autodeploy').click();
        }

        // --- //
        // NEW //
        // --- //

        // ------------------ //
        // Change month click //
        // ------------------ //

        $(document).on('click', '.next-month', function(e){
            // console.log('clicked on next month')
            // $.when($.ajax()).done(function(){
            //     createDivs();
            // })
            e.preventDefault();
        })

        $.agendaPage = {
            openRow : false
        };

        // ----------- //
        // Event click //
        // ----------- //

        $('.event-wrapper').on('click', '.agenda-click', function(e){
            var $el            = $(this),
                $agendaClick   = $('.agenda-click'),
                $nextContainer = $el.nextAll('.agenda-row:first'),
                $allContainers = $('.agenda-row');

            // console.log($.agendaPage.openRow)

            if($.agendaPage.openRow === false){
                //console.log('opening')
                $el.addClass('active');                     // Adding this active class

                $.ajax({
                    type: "GET",
                    url: $el.data('href')
                }).done(function(html) {
                    // Starting opening
                    $.agendaPage.openRow = true;                // Updating var
                    $agendaClick.not($el).css('opacity','0.5'); // Hiding all other agenda-click
                    $nextContainer.addClass('open');            // Opening next container of el
                    $nextContainer.append(html);
                    $nextContainer.find('.js-autoload').autoLoad();
                });

            } else if ($.agendaPage.openRow === true && $el.hasClass('active')) {
                // console.log('Same class (active), closing and emptying.')
                $nextContainer.empty();
                $.agendaPage.openRow = false; // Updating var

                $el.removeClass('active'); // Removing this active class
                $allContainers.removeClass('open'); // Closing all containers
                $agendaClick.css('opacity','1'); // Putting back all agenda-click to original state

            } else if ($.agendaPage.openRow === true) {
                // console.log('Same class (active), closing and emptying.')
                $nextContainer.empty();
                $.agendaPage.openRow = false; // Updating var

                $agendaClick.removeClass('active'); // Removing this active class
                $allContainers.removeClass('open'); // Closing all containers
                $agendaClick.css('opacity','1'); // Putting back all agenda-click to original state

                $el.addClass('active');                     // Adding this active class

                $.ajax({
                    type: "GET",
                    url: $el.data('href')
                }).done(function(html) {
                    // Starting opening
                    $.agendaPage.openRow = true;                // Updating var
                    $agendaClick.not($el).css('opacity','0.5'); // Hiding all other agenda-click
                    $nextContainer.addClass('open');            // Opening next container of el
                    $nextContainer.append(html);
                    $nextContainer.find('.js-autoload').autoLoad();
                });
            } else {
                console.log('error');
            }

            // console.log('Now row is : '+$.agendaPage.openRow)
            e.preventDefault();
        });

        // When Ajax is done (js-autoload)
        $(document).on("ajaxStop", function() {
            createDivs();
        });





        var cal = $('#calendar');
        $.getJSON( cal.data('load'), function( data ) {
            var correction = 10;

            cal.fullCalendar({
                firstDay: 1,
                timeFormat: 'HH:mm',
                axisFormat: 'HH:mm',
                minTime: '07:00:00',
                maxTime: '22:00:00',
                header: {left:'today',center:'prev,title,next',right:'month,agendaWeek,agendaDay'},
                columnFormat: {month:'dddd',week:'dddd D',day:'dddd'},
                titleFormat: {month:'MMMM YYYY',week:'D MMMM YYYY',day:'dddd D MMMM YYYY'},
                dayPopoverFormat : 'dddd D MMMM YYYY',
                monthNames: data.translate.monthnames,
                dayNames: data.translate.daynames,
                buttonText: data.translate.buttontext,
                eventLimitText: data.translate.more,
                height: 'auto',
                fixedWeekCount: false,
                allDaySlot: false,
                eventLimit: 2,

                //view
                viewRender: function(view, element) {
                    $('.fc-toolbar',cal).removeClass('month agendaWeek agendaDay');
                    $('.fc-toolbar',cal).addClass(view.name);
                    if(view.name == 'month'){
                        $('.fc-day-number',cal).on('click',function(){
                            cal.fullCalendar( 'gotoDate', $(this).data('date') );
                            cal.fullCalendar( 'changeView', 'agendaDay' );
                        });
                    }
                },
                //eventslist
                events: data.events,
                //generation event
                eventRender: function(event, element, view) {
                    eventGen(event, element, false);
                },
                //popover
                eventLimitClick: function(cellInfo, jsEvent) {
                    var container = $('.fc-day-grid-container',cal);
                    var popover = $('<div class="fc-popover">'+
                                        '<div class="fc-header">'+
                                            '<span class="fc-close"></span>'+
                                            '<span class="fc-title">'+cellInfo.date.format('dddd D MMMM YYYY')+'</span>'+
                                            '<div class="fc-clear"></div>'+
                                        '</div>'+
                                        '<div class="fc-body"><div class="fc-event-container">'+
                                        '</div></div>'+
                                    '</div>');
                    $('.fc-popover',container).remove();
                    container.append(popover).on('click','.fc-close',function(){
                        $('.fc-popover',container).remove();
                    });
                    //position
                    var top  = Math.round( cellInfo.dayEl.offset().top  - container.offset().top ),
                        left = Math.round( cellInfo.dayEl.offset().left - container.offset().left);
                    if( (top+popover.height()) > container.height())popover.css('top',container.height()-popover.height()+'px');
                    else popover.css('top',top+'px');
                    if( (left+popover.width()) > container.width())popover.css('left',container.width()-popover.width()-correction+'px');
                    else popover.css('left',left+correction/2+'px');
                    //events
                    $.each(cellInfo.segs, function( index, value ) {
                        var element = $('<a class="fc-day-grid-event fc-event fc-start fc-end">'+
                                            '<div class="fc-content">'+
                                                '<span class="fc-time"></span><span class="fc-title"></span>'+
                                            '</div>'+
                                        '</a>');
                        eventGen(value.event, element, cellInfo.dayEl);
                        popover.find('.fc-event-container').append(element);
                    });
                }
            });



            //fontion de generation
            function eventGen(event, element, popoverElm){
                //event content
                if(event.start_base){
                    var start = $.fullCalendar.moment(event.start_base).format('D MMM YYYY HH:mm'),
                        end = $.fullCalendar.moment(event.end_base).format('D MMM YYYY HH:mm');
                    element.find('.fc-time').html( '<span>'+data.translate.from+' '+start+'</span><span>'+data.translate.to+' '+end+'</span>' );
                }
                else{
                    element.find('.fc-time').text( event.start.format('HH:mm')+' - '+event.end.format('HH:mm') );
                }
                element.find('.fc-title').text( event.title ).prepend($('<img src="'+event.img_user+'" />') );
                //color
                element.addClass(event.status);
                //popup
                element.on('click',function(){
                    var container = $('.fc-day-grid-container,.fc-time-grid-container',cal);
                    $.ajax({
                        type: "GET",
                        url: event.ajx_url
                    }).done(function(html) {
                        var popup = $('<div class="popup"><div class="close"></div></div>');
                        $('.popup',container).remove();
                        container.append(popup).on('click','.close',function(){
                            $('.popup',container).remove();
                        });
                        popup.append(html);
                        popup.find('.js-autoload').autoLoad();
                        //position popup
                        if(popoverElm == false){
                            var top  = Math.round( $(element.context.offsetParent).offset().top  - container.offset().top ),
                                left = Math.round( element.offset().left - container.offset().left);
                            if( (top + popup.height()) > container.height())popup.css('top',container.height()-popup.height()+'px');
                            else popup.css('top',top+'px');
                            if( (left + popup.width()) > container.width())popup.css('left',container.width()-popup.width()-correction+'px');
                            else popup.css('left',left+'px');
                        }
                        else{
                            var top  = Math.round( popoverElm.offset().top  - container.offset().top ),
                                left = Math.round( popoverElm.offset().left - container.offset().left);
                            if( (top + popup.height()) > container.height())popup.css('top',container.height()-popup.height()+'px');
                            else popup.css('top',top+'px');
                            if( (left + popup.width()) > container.width())popup.css('left',container.width()-popup.width()-correction+'px');
                            else popup.css('left',left+(correction/2)+'px');
                        }
                    });
                });
            }

            //select private/public
            $(document.body).on('change', '.js-select-href', function(ev){
                if($(this).hasClass('unactive')){
                    var select = $('option:selected',$(this)).attr('rel');
                    cal.fullCalendar( 'removeEventSource', data.events );
                    cal.fullCalendar( 'addEventSource', data.events );
                    if(select == 1)cal.fullCalendar('removeEvents',function(e){return e.state=='private';});
                    else if(select == 2)cal.fullCalendar('removeEvents',function(e){return e.state=='public';});
                }
            });

            //loader
            cal.find('.loader').hide();
        });

        //switch top
        $('#big').on('click',function(event){
        	localStorage.setItem("agenda", "big");
            $('.event-wrapper-big').addClass('active');
            $('.event-wrapper-small').removeClass('active');
            $('select.js-select-href').removeClass('unactive');
        });
        $('#small').on('click',function(event){
        	localStorage.setItem("agenda", "small");
            $('.event-wrapper-big').removeClass('active');
            $('.event-wrapper-small').addClass('active');
            $('select.js-select-href').addClass('unactive');
            cal.fullCalendar('render');
        });
        if (localStorage.getItem("agenda") == null){
            localStorage.setItem("card", "big");
        }
        if (localStorage.getItem("agenda") == 'big') {
            $('#big').trigger('click');
        } else  {
            $('#small').trigger('click');
        }
    });

    $("img.lazyload").lazyload({
        effect : "fadeIn"
    });

    // --- //
    // OLD //
    // --- //

    // $(document).ready(function() {
    //     // Vars
    //     var rowOpen = false;

    //     // ---------- //
    //     // OPEN EVENT //
    //     // ---------- //

    //     $('.event-wrapper').on('click', '.agenda-click', {}, function(event){
    //         console.log('clicked on agenda-click')

    //         // Vars
    //         var thisCard       = $(this),
    //             cardsByRow     = 4,
    //             animationSpeed = 500;

    //         var rowIndex = Math.floor(thisCard.index() / cardsByRow) + 1;
    //         var rowLastCard = $('.month-date-card:eq('+((rowIndex * cardsByRow) - 1)+')');

    //         if(!rowLastCard.length) {
    //             rowLastCard = $('.month-date-card:last-child');
    //             console.log('Last child')
    //         }

    //         var thisRow = rowLastCard.next('.agenda-row');

    //         // Checking state
    //         if (thisCard.hasClass('active')) {
    //             console.log('this card has active class')

    //             thisRow.stop().animate({
    //                 'height'  : '0'
    //             }, animationSpeed, function() {
    //                 thisRow.removeClass('open');
    //                 thisCard.removeClass('active');
    //                 rowOpen = false;
    //                 console.log('closing this card')
    //             });
    //             return false;
    //         }
    //         else if (rowOpen === true) {
    //             var openedRow = $('.agenda-row.open');
    //             console.log('Row is open')

    //             openedRow.stop().animate({
    //                 'height'  : '0'
    //             }, animationSpeed, function() {
    //                 openedRow.removeClass('open');
    //                 $('.month-date-card.active').removeClass('active');
    //                 console.log('closed row')
    //                 rowOpen = false;
    //             });
    //         }

    //         // OPEN EVENT AJAX (load event information here)
    //         $.ajax({
    //             type: "GET",
    //             url: thisCard.data('href')
    //         }).done(function(html) {
    //             console.log('loading everything')
    //             // Starting opening
    //             rowOpen = true;

    //             thisCard.addClass('active');

    //             if(!thisRow.length) {
    //                 thisRow = $('<div class="agenda-row"></div>');
    //                 rowLastCard.after(thisRow);
    //             }

    //             thisRow.append(html);
    //             thisRow.find('.month-date-event').addClass('open');
    //             thisRow.find('.js-autoload').autoLoad();

    //             thisRow.stop().animate({
    //                 'height'  : '395px'
    //             }, animationSpeed, function() {
    //                 thisRow.addClass('open');
    //             });
    //         });

    //         event.preventDefault();
    //     });
    // });
})(window.jQuery, window, document);