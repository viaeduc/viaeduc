$(function () {
    if(!$('.tm-search').hasClass('initialised')){

        var self        = $('.tm-search'),
            prefetchUrl = self.data('ajax-url') || null,
            cacheTtl    = 1800;

        if (prefetchUrl != null){
            // Create engine
            var bloodhoundData = new Bloodhound({
                datumTokenizer: function(d) {
                    return Bloodhound.tokenizers.whitespace(d.title);
                },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url : prefetchUrl,
                    wildcard: '__QUERY__',
                    ttl : cacheTtl
                }
            });

            // Init engine
            var promise = bloodhoundData.initialize();

            // Init Typeahead
            self.typeahead(null, {
                name      : 'q',
                source    : bloodhoundData.ttAdapter(),
                minLength : 0,
                templates: {
                    // header: [
                    //     '<ul class="home-search-box">'
                    // ],
                    suggestion: Handlebars.compile('<div class="home-search-result"><a href="{{href}}"><div class="home-search-result-info"><img src="{{image}}" /><p>{{title}}</strong><span><span>{{description}}</span></span></p></div></a></div>'),
                    footer: [
                        '<div class="home-search-result more-result">',
                        '<a href="#">',
                        'Voir tous les résultats',
                        '</a>',
                        '</div>'
                        // '</ul>'
                    ].join('\n'),
                    empty: [
                        '<div class="home-search-result">',
                            '<div class="home-search-result-info">',
                                '<p>Aucun résultat</p>',
                            '</div>',
                        '</div>'
                    ].join('\n')
                }
            }).on('typeahead:opened',function (){
                // console.log('search opened');
            }).on('typeahead:closed',function (){
                // console.log('search closed');
            });
        } else {
            self.typeahead();
        }

        self.addClass('initialized');

        // ----------------- //
        // Testing purposes  :
        // ----------------- //

        // var substringMatcher = function(strs) {
        //     return function findMatches(q, cb) {
        //         var matches, substrRegex;

        //         // an array that will be populated with substring matches
        //         matches = [];

        //         // regex used to determine if a string contains the substring `q`
        //         substrRegex = new RegExp(q, 'i');

        //         // iterate through the pool of strings and for any string that
        //         // contains the substring `q`, add it to the `matches` array
        //         $.each(strs, function(i, str) {
        //             if (substrRegex.test(str)) {
        //             // the typeahead jQuery plugin expects suggestions to a
        //             // JavaScript object, refer to typeahead docs for more info
        //             matches.push({ value: str });
        //         }
        //     });

        //     cb(matches);
        //     };
        // };

        // var states = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California',
        //   'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii',
        //   'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
        //   'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
        //   'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
        //   'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota',
        //   'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
        //   'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
        //   'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'
        // ];
    }



    // ---------------------------------------------------------- //
    // TEMP MODAL FOR SEARCH, REMOVE BELOW WHEN SEARCH IS WORKING //
    // ---------------------------------------------------------- //

    $('.temp-search').on('click',function(event){
        var el = $(this);

        if ( el.hasClass('active') ) {
            el.removeClass('active');
            $('.modal-warning').modal('show');
            event.preventDefault();
        } else {
            el.submit();
        }
    })

    $('.accept-warning').on('click',function(event){
        $('.form-search').submit();
        event.preventDefault();
    });

    if($('.search-filter-links').length){
        $('.search-filter-links').each(function(key, item){
            var $this      = $(item),
                linkNumber = $this.find('a').length;

            console.log('found', $this, 'number of links', linkNumber);

            if(linkNumber >= 10){
                console.log('over 10!');
                $this.find('a').slice(10).hide();
                $this.append('<a href="#" class="load-more-items">Afficher plus</a>');
            }
        });

        $('.load-more-items').on('click', function(e){
            var $this   = $(this),
                $parent = $(this).parent('.search-filter-links');

            $parent.find('a').show();
            $this.hide();

            e.preventDefault();
        });
    }

    if($('.swiper-container').length){
        $('.swiper-container').bind('scroll', function() {

            if($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                $(this).find('.read-other').parent().trigger('click'); // trigger loadmore
                // $(this).find('.read-other').parent().hide();
            }
        });
    }
});

        $