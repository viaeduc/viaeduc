$(document).ready(function(){
	$('.filtre').change(function(){

        var href = $(this).find('option:selected').attr('data-href');

        $.ajax({
            url: href
        }).done(function(html){
            contentDiv.html(html)
        }).fail(function(){
            console.log('failed')
        })
    })
})