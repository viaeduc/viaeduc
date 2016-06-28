CKEDITOR.plugins.add( 'belin', {
    init: function( editor ) {

         //editor.addCommand( 'belinDialog', new CKEDITOR.dialogCommand( 'belinDialog' ) );

         //CKEDITOR.dialog.add( 'belinDialog', this.path + 'dialogs/belin.js' );

        editor.addCommand( 'belinDialog', {
		    exec: function( editor ) {
		        //$('#belinWidgetModal').modal('show');
		        $('#belinWidgetModal').modal('show').on("hidden.bs.modal", function (e) {
				  $("body").removeClass("modal-open")
				});;
		        $('.modal-backdrop').css({"z-index": "9"});
		        myFunction = function(obj){

                 /**
                  * Documentation:
                  * http://docs.ckeditor.com/#!/api/CKEDITOR.dom.element
                  * Add class/
                  */

                        var bloc        = editor.document.createElement('div'),
                            content     = editor.document.createElement( 'div' ),
                            blocImage   = editor.document.createElement( 'p' ),
                            title       = editor.document.createElement( 'p' ),
                            titleLink   = editor.document.createElement( 'a' ),
                            description = editor.document.createElement( 'p' ),
                            mention     = editor.document.createElement('span'),
                            image       = editor.document.createElement( 'img' );
                            url         = encodeURIComponent(obj.url);

                        mention.setText("Document issu d’un manuel numérique Lib’ des Editions Belin – © consultation sous réserve de droits");
                        mention.addClass('preview-text');
                        titleLink.setAttribute( 'href', 'http://' + window.location.hostname + '/search/belin?url='+url );
                        titleLink.setText(obj.description);
                        title.append(titleLink);
                        description.setText(obj.name);
                        image.setAttribute('src', obj.image);
                        blocImage.append(image);



                        //Construct content element
                        content.append(title);
                        content.append(description);

                        bloc.addClass('belinPlugin');
                        blocImage.addClass('blocImage');

                        bloc.setStyles( {
	 		                backgroundColor: '#37485f',
	 		                margin: '0 0 5px 0',
	 		                padding: '10px',
	 		                color:'#FFFFFF',
	 		                overflow: 'hidden'
	 	                } );
	 	                title.setStyles({
	 	                	    textTransform: 'uppercase',
	 	                	    fontSize: '19px'
	 	                });
	 	                description.setStyles({
	 	                	fontSize: '15px'
	 	                });
	 	                image.setStyles({
                            width: '100%',
                            minHeight: '100%'
	 	                });
	 	                blocImage.setStyles({
	 	                	display: 'block',
                            float: 'left',
                            paddingRight: '10px',
                            width: '100px',
                            height: '100px',
                            overflow: 'hidden'
	 	                });
	 	                content.setStyles({
                            overflow: 'hidden',
                            float: 'left',
                            width: '65%'
	 	                });
                        mention.setStyles({
                            display: 'inline-block',
                            verticalAlign: 'top',
                            width: '100%'
                        })

	 	                bloc.append( blocImage );
	 	                bloc.append( content );
	 	                bloc.append(mention);
                    	editor.insertElement( bloc );
                    	$('#belinWidgetModal').modal('hide');
                    	$('body').removeClass('modal-open');
                        // $('.preview-text').css({
                        //     'display': 'inline-block',
                        //     'vertical-align': 'top',
                        //     'width': '100%'
                        // });
                }
		    }
		});

		editor.ui.addButton( 'Belin', {
		    label: 'Belin',
		    command: 'belinDialog',
		    toolbar: 'insert',
		    icon: this.path + 'icons/belin.png'
		});
    }
});

/*<div class="timeline-content-main timeline-ressource">
        <a href="/publication/385" class="link-wrapper">
          <div class="timeline-ressource-image">
                <img alt="" src="/medias/default/115_0/f9382a38c69691893eb006eacf864339.png" class="">
          </div><!--
       --><div class="timeline-ressource-content">
             <h4 class="ressource-title">Test</h4>
             <p class="ressource-text">eeee</p>
             </div>
        </a>
</div>*/