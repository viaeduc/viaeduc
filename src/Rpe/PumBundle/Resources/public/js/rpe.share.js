$(document).ready(function () {

	   // Type
	   // 2 : Type Wall
	   //4 : Type Group
	   var type = "profil",
	       valueType = "",
	       typeShare = $('.choiceTypeShare'),
	       res = $('#sendShare').attr("href");
	       type = "2";


	    typeShare.on('click', 'li', function(){

	    	var num = $(this).attr("rel");
	    	valueType = $("#selection option").eq(num).val();
	    	type = $("#selection option").eq(num).attr("data-type");
	    	if(type == "2"){

	    		valueType = "0";
	    	}

	    });

	    $('#sendShare').on('click', function(event){

	    	event.preventDefault();
	    	location.href=res+"/"+valueType+window.location.search+"&type="+type;
	    })
});