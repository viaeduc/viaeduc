// ------ //
// README //
// ------ //

// Charts :
// Use data attributes to define the data & type of charts.
// To use this script, simply usethe following attributes :
//      data-chart-type   : Choose the style of the chart (doughnut, bar, line).
//      data-chart-data   : The link to the data json of this chart.
//      data-chart-height : The height of the canvas.
//      data-chart-width  : The width of the canvas.
//      data-chart-name   : Name of the chart.

// Percentage calculator :
// Use data attributes to define the percentages.
// To use the percentage calculator, simply use these attributs on the container div :
//      data-elements    : The number of stats (2 or 3)
//      data-stat-first  : First percentage value (applies to first child div).
//      data-stat-second : Second percentage value (applies to second child div).
//      data-stat-third  : Third percentage value (applies to thirs child div).
$(document).ready(function(){
    // Getting items
    var $chartCanvas       = $('.rpe-chart'),
        $percentageCounter = $('.percentage-counter');

    $chartCanvas.each( function () {
        // Getting setup from data attributes
        var $this        = $(this),
            $chartType   = $this.data('chart-type'),
            $chartData   = $this.data('chart-data'),
            $ctx         = $this.get(0).getContext("2d"),
            chartHeight  = $this.data('chart-height'),
            chartWidth   = $this.data('chart-width'),
            chartName    = $this.data('chart-name'),
            stepWidth    = $this.data('step-width'),
            options;

        // Setting height & width
        $ctx.canvas.width  = chartHeight;
        $ctx.canvas.height = chartWidth;


        switch($chartType) {
	        case 'doughnut':
	            options = {
	                segmentShowStroke     : false,
	                percentageInnerCutout : 80
	            };
	            new Chart($ctx).Doughnut($chartData, options);
	            break;

	        case 'line':
                options = {
                    scaleOverride : true,
                    scaleSteps : 5,
                    scaleStepWidth : stepWidth,
                    scaleStartValue : 0,
                    scaleShowHorizontalLines: true
                };
	            new Chart($ctx).Line($chartData, options);
	            break;
	        case 'bar':
                options = {
                    scaleOverride : true,
                    scaleSteps : 5,
                    scaleStepWidth : stepWidth,
                    scaleStartValue : 0
                };
	            new Chart($ctx).Bar($chartData, options);
	            break;
	    }

    });

    // Percentage counter
    $percentageCounter.each( function () {
        var $this        = $(this),
            dataElements = $this.data('elements'),
            firstStat    = $this.data('stat-first'),
            secondStat   = $this.data('stat-second'),
            firstStat    = firstStat+'%',
            secondStat   = secondStat+'%';

        if (dataElements == 3) {
            var thirdStat = $this.data('stat-third'),
                thirdStat = thirdStat+'%';
        }

        //console.log($dataElements);
        //console.log($firstStat);
        //console.log($secondStat);

        /*if ($dataElements == 3) {
             console.log($thirdStat);
         }*/

        // Setting width
        $this.find('.percentage-bar:nth-child(1)').css('width', firstStat);
        $this.find('.percentage-bar:nth-child(2)').css('width', secondStat);
        if (dataElements == 3) {
            $this.find('.percentage-bar:nth-child(3)').css('width', thirdStat);
        }

        $this.parent().find('.percentage-bar-labels-inner:nth-child(1) .percentage-number').html(firstStat);
        $this.parent().find('.percentage-bar-labels-inner:nth-child(2) .percentage-number').html(secondStat);
        if (dataElements == 3) {
            $this.parent().find('.percentage-bar-labels-inner:nth-child(3) .percentage-number').append(thirdStat);
        }

        // // Placing percentage number
        // $this.find('.percentage-bar:nth-child(1)').find('.percentage-number').append(firstStat);
        // $this.find('.percentage-bar:nth-child(2)').find('.percentage-number').append(secondStat);
        // if (dataElements == 3) {
        //     $this.find('.percentage-bar:nth-child(3)').find('.percentage-number').append(thirdStat);
        // }
    });

    // Menu stuff
    var $statsMenuLink = $('[data-stats-link]');

    $statsMenuLink.on('click', function(e){
        var $this        = $(this),
            $contentDivs = $('[data-stats]'),
            type         = $this.data('stats-link');

        // Fixing menu links
        $statsMenuLink.removeClass('active');
        $this.addClass('active')

        // $contentDivs.addClass('hidden');

        // Switching the divs
        $contentDivs.each(function(){
            if($(this).data('stats') == type){
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
            }
        })

        // if($contentDivs.data('stats') == type){
        //     console.log('found one');
        //     $contentDivs.removeClass('hidden');
        // }


        e.preventDefault();
    })
});