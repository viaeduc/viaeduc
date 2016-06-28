$(document).ready(function(){
    // ------------- //
    // CREATE EDITOR //
    // ------------- //
    $(document).on('change', '.input-upload' , function(){
        var $textDiv = $(this).parent().find('.button-text'),
            $button  = $(this).parent();

        if($(this).val() != '') {
            $textDiv.text('Image téléchargée')
            $button.removeClass('dark-grey');
            $button.addClass('green');
        } else{
            $textDiv.text('Télécharge une image')
            $button.removeClass('green');
            $button.addClass('dark-grey');
        }
    });

    // ----------- //
    // ADD PRODUCT //
    // ----------- //
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
//        console.log(e.target)
        var addItemHeight = $('.editor-add').height();
//        console.log(addItemHeight)

        $('.editor-add').height(0);

        $('.editor-title').on('click',function(event){
            if($(this).hasClass('active')){
                $(this).removeClass('active');
                $('.editor-add').stop().animate({
                    height: 0
                }, 450,function(){
                    $('.editor-add').css('overflow','hidden');
                });

            } else {
                $(this).addClass('active');
                $('.editor-add').stop().animate({
                    height: addItemHeight+50
                }, 450, function(){
                    $('.editor-add').css('overflow','visible');
                });
            }

            event.preventDefault();
        })
    })
});