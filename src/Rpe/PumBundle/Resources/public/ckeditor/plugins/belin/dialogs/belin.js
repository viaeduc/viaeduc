CKEDITOR.dialog.add( 'belinDialog', function( editor ) {
			return {
				title:			'Test Dialog',
				resizable:		CKEDITOR.DIALOG_RESIZE_BOTH,
				minWidth:		500,
				minHeight:		400,
				contents: [
					{
 						id:			'Element1',
 						label:		'First Element',
 						title:		'Belin',
						accessKey:	'Q',
 						elements: [
				            {
				                type: 'html',
				                html: '<div id="dialogBelin">Test test test</div>',
				            }
						]
 					}
 				],
 				onShow: function() {
                    
                    belinWs = $('#modal-body').html();
                    //$('#dialogBelin').append(belinWs);
                }

			};
 } );


