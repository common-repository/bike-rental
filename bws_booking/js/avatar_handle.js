( function( $ ){
	$( document ).ready( function() {

		/**
		 * Functionality to handle avatar selection
		 * In separate file because will be used in both front and back end
		 */

        var avatar_btn = $( '#bws_bkng_avatar_upload_btn' );
		if ( avatar_btn.length ) {
			var wrap = $( avatar_btn ).parents( '.bws-bkng-avatar-wrap' ),
				hidden_input = wrap.find( 'input.bws-bkng-att-id' ),
				img = wrap.find( 'img.bws-bkng-gravatar-img' );

			avatar_btn.click( function( e ) {
				e.preventDefault();

				var $this = $( this );

				var frame = wp.media( {
					multiple: false,
					button: {
						text: $this.data( 'button-title' ),
						close: false
					},
					states: [
						new wp.media.controller.Library( {
							title:     $this.data( 'title' ),
							library:   wp.media.query( { type: 'image' } ),
							multiple:  false,
							date:      false,
							priority:  20,
							suggestedWidth: 300,
							suggestedHeight: 300
						} ),
						new wp.media.controller.Cropper( {
							imgSelectOptions: calculateImageSelect
						} )
					]
				} );

				frame.on( 'select', function() {
					frame.setState( 'cropper' );
				} );

				frame.on( 'cropped', function( croppedImage ) {
					var url = croppedImage.url,
						attachmentId = croppedImage.attachment_id,
						w = croppedImage.width,
						h = croppedImage.height;
					setImageFromURL( url, attachmentId, w, h );
				} );

				frame.on( 'skippedcrop', function( selection ) {
					var url = selection.get( 'url' ),
						w = selection.get( 'width' ),
						h = selection.get( 'height' );
					setImageFromURL( url, selection.id, w, h );
				} );

				frame.open();
			} );

			wrap.delegate( '#bws_bkng_avatar_delete_btn', 'click', function( e ) {
				e.preventDefault();
				img.attr( 'src', bws_bkng.default_url );
				hidden_input.val( 0 );
			} );

			function calculateImageSelect( attachment, controller ) {
				var xInit = 300,
					yInit = 300,
					ratio, xImg, yImg, realHeight, realWidth,
					imgSelectOptions;
	
				realWidth = attachment.get( 'width' );
				realHeight = attachment.get( 'height' );

				this.headerImage = new wp.customize.HeaderTool.ImageModel();
				this.headerImage.set( {
					themeWidth: xInit,
					themeHeight: yInit,
					imageWidth: realWidth,
					imageHeight: realHeight
				} );
	
				controller.set( 'canSkipCrop', ! this.headerImage.shouldBeCropped() );
	
				ratio = xInit / yInit;
				xImg = realWidth;
				yImg = realHeight;
	
				if ( xImg / yImg > ratio ) {
					yInit = yImg;
					xInit = yInit * ratio;
				} else {
					xInit = xImg;
					yInit = xInit / ratio;
				}
	
				imgSelectOptions = {
					aspectRatio: '1:1',
					handles: true,
					keys: true,
					instance: true,
					persistent: true,
					imageWidth: realWidth,
					imageHeight: realHeight,
					x1: 0,
					y1: 0,
					x2: xInit,
					y2: yInit
				};

				return imgSelectOptions;
			}
	
			function setImageFromURL( url, attachmentId, width, height ) {
				hidden_input.val( attachmentId );
				img.attr( 'src', url );
			}
		}

    } );
} )( jQuery );