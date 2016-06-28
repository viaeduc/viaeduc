'use strict';
$(document).ready(function(){
    // ---------
    // Animation
    // ---------

    // Vars
    var canvas    = $('#canvas_draw'),
        section   = $('.content'),
        divHeight = window.innerHeight-170,
        divWidth  = window.innerWidth;

    // Setting sizes for fullscreen
    section.height(divHeight)
           .width(divWidth);

    // Intro animation
    $('.canvas-text').css({
        'opacity' : '1'
    });

    $('.image-kid').css({
        'bottom' : '0px'
    });

    $('.back-btn').css({
        'width'   : 'auto',
        'opacity' : '1',
        'padding' : '10px 150px 10px 20px'
    });

    // ------
    // Canvas
    // ------

    // Vars
    var canvasId  = document.getElementById('canvas_draw'),
        context   = canvasId.getContext('2d'),
        radius    = 2,
        dragging  = false;

    // Width & Height
    canvasId.width      = window.innerWidth;
    canvasId.height     = window.innerHeight-220;
    context.lineWidth   = radius*2;
    context.strokeStyle = '#ffffff';
    context.fillStyle   = '#ffffff';

    // Functons
    var engage = function(el){
        dragging = true;
        putPoint(el);
        event.preventDefault();
    };

    var disengage = function(){
        dragging = false;
        context.beginPath();
    }

    var putPoint = function(el){
        var x      = el.offsetX==undefined?el.layerX:el.offsetX,
            y      = el.offsetY==undefined?el.layerY:el.offsetY;

        if(dragging){
            context.lineTo(x,y);
            context.stroke();
            context.beginPath();
            context.arc(x, y, radius, 0, Math.PI*2);
            context.fill();
            context.beginPath();
            context.moveTo(x,y);
        }
    };

    // Events
    canvasId.addEventListener('mousedown', engage)
    canvasId.addEventListener('mousemove', putPoint)
    canvasId.addEventListener('mouseup', disengage)
});

// Resize function
$(window).resize(function(){
    var canvas    = $('#canvas-draw'),
        section   = $('.content'),
        divHeight = window.innerHeight-220,
        divWidth  = window.innerWidth,
        canvasId  = document.getElementById('canvas_draw'),
        context   = canvasId.getContext('2d'),
        radius    = 2;

    // Setting sizes for fullscreen
    section.height(divHeight)
           .width(divWidth);

    // Canvas settings
    canvasId.width      = window.innerWidth;
    canvasId.height     = window.innerHeight-220;
    context.lineWidth   = radius*2;
    context.strokeStyle = '#ffffff';
    context.fillStyle   = '#ffffff';
})