/**
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

( function() {
	CKEDITOR.plugins.add( 'unlink', {
		requires: 'fakeobjects',
		// jscs:disable maximumLineLength
		lang: 'af,ar,az,bg,bn,bs,ca,cs,cy,da,de,de-ch,el,en,en-au,en-ca,en-gb,eo,es,es-mx,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,oc,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,tt,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%
		// jscs:enable maximumLineLength
		icons: 'unlink', // %REMOVE_LINE_CORE%
		hidpi: true, // %REMOVE_LINE_CORE%
		onLoad: function() {

			var template = '.%2 a.cke_anchor,' +
				'.%2 a.cke_anchor_empty' +
				',.cke_editable.%2 a[name]' +
				',.cke_editable.%2 a[data-cke-saved-name]' +
				'{' +
					baseStyle +
					'padding-%1:18px;' +
					// Show the arrow cursor for the anchor image (FF at least).
					'cursor:auto;' +
				'}' +
				'.%2 img.cke_anchor' +
				'{' +
					baseStyle +
					'width:16px;' +
					'min-height:15px;' +
					// The default line-height on IE.
					'height:1.15em;' +
					// Opera works better with "middle" (even if not perfect)
					'vertical-align:text-bottom;' +
				'}';

			// Styles with contents direction awareness.
			function cssWithDir( dir ) {
				return template.replace( /%1/g, dir == 'rtl' ? 'right' : 'left' ).replace( /%2/g, 'cke_contents_' + dir );
			}

			CKEDITOR.addCss( cssWithDir( 'ltr' ) + cssWithDir( 'rtl' ) );
		},

		init: function( editor ) {
			var allowed = 'a[!href]',
				required = 'a[href]';

			editor.addCommand( 'unlink', new CKEDITOR.unlinkCommand() );

			if ( editor.ui.addButton ) {
				editor.ui.addButton( 'Unlink', {
					label: editor.lang.unlink.unlink,
					command: 'unlink',
					toolbar: 'links,20'
				} );
			}


			// If the "menu" plugin is loaded, register the menu items.
			if ( editor.addMenuItems ) {
				editor.addMenuItems( {
					unlink: {
						label: editor.lang.unlink.unlink,
						command: 'unlink',
						group: 'link',
						order: 5
					}
				} );
			}

			this.compiledProtectionFunction = getCompiledProtectionFunction( editor );
		},

	} );

	// Loads the parameters in a selected link to the link dialog fields.
	var javascriptProtocolRegex = /^javascript:/,
		emailRegex = /^(?:mailto)(?:(?!\?(subject|body)=).)+/i,
		emailSubjectRegex = /subject=([^;?:@&=$,\/]*)/i,
		emailBodyRegex = /body=([^;?:@&=$,\/]*)/i,
		anchorRegex = /^#(.*)$/,
		urlRegex = /^((?:http|https|ftp|news):\/\/)?(.*)$/,
		selectableTargets = /^(_(?:self|top|parent|blank))$/,
		encodedEmailLinkRegex = /^javascript:void\(location\.href='mailto:'\+String\.fromCharCode\(([^)]+)\)(?:\+'(.*)')?\)$/,
		functionCallProtectedEmailLinkRegex = /^javascript:([^(]+)\(([^)]+)\)$/,
		popupRegex = /\s*window.open\(\s*this\.href\s*,\s*(?:'([^']*)'|null)\s*,\s*'([^']*)'\s*\)\s*;\s*return\s*false;*\s*/,
		popupFeaturesRegex = /(?:^|,)([^=]+)=(\d+|yes|no)/gi,
		telRegex = /^tel:(.*)$/;

	var advAttrNames = {
		id: 'advId',
		dir: 'advLangDir',
		accessKey: 'advAccessKey',
		// 'data-cke-saved-name': 'advName',
		name: 'advName',
		lang: 'advLangCode',
		tabindex: 'advTabIndex',
		title: 'advTitle',
		type: 'advContentType',
		'class': 'advCSSClasses',
		charset: 'advCharset',
		style: 'advStyles',
		rel: 'advRel'
	};

	function unescapeSingleQuote( str ) {
		return str.replace( /\\'/g, '\'' );
	}

	function escapeSingleQuote( str ) {
		return str.replace( /'/g, '\\$&' );
	}

	function protectEmailAddressAsEncodedString( address ) {
		var length = address.length,
			encodedChars = [],
			charCode;

		for ( var i = 0; i < length; i++ ) {
			charCode = address.charCodeAt( i );
			encodedChars.push( charCode );
		}

		return 'String.fromCharCode(' + encodedChars.join( ',' ) + ')';
	}

	function protectEmailLinkAsFunction( editor, email ) {
		var plugin = editor.plugins.link,
			name = plugin.compiledProtectionFunction.name,
			params = plugin.compiledProtectionFunction.params,
			retval = [ name, '(' ],
			paramName,
			paramValue;

		for ( var i = 0; i < params.length; i++ ) {
			paramName = params[ i ].toLowerCase();
			paramValue = email[ paramName ];

			i > 0 && retval.push( ',' );
			retval.push( '\'', paramValue ? escapeSingleQuote( encodeURIComponent( email[ paramName ] ) ) : '', '\'' );
		}
		retval.push( ')' );
		return retval.join( '' );
	}

	function getCompiledProtectionFunction( editor ) {
		var emailProtection = editor.config.emailProtection || '',
			compiledProtectionFunction;

		// Compile the protection function pattern.
		if ( emailProtection && emailProtection != 'encode' ) {
			compiledProtectionFunction = {};

			emailProtection.replace( /^([^(]+)\(([^)]+)\)$/, function( match, funcName, params ) {
				compiledProtectionFunction.name = funcName;
				compiledProtectionFunction.params = [];
				params.replace( /[^,\s]+/g, function( param ) {
					compiledProtectionFunction.params.push( param );
				} );
			} );
		}

		return compiledProtectionFunction;
	}

	/**
	 * Set of Link plugin helpers.
	 *
	 * @class
	 * @singleton
	 */
	CKEDITOR.plugins.unlink = {
		/**
		 * Get the surrounding link element of the current selection.
		 *
		 *		CKEDITOR.plugins.unlink.getSelectedLink( editor );
		 *
		 *		// The following selections will all return the link element.
		 *
		 *		<a href="#">li^nk</a>
		 *		<a href="#">[link]</a>
		 *		text[<a href="#">link]</a>
		 *		<a href="#">li[nk</a>]
		 *		[<b><a href="#">li]nk</a></b>]
		 *		[<a href="#"><b>li]nk</b></a>
		 *
		 * @since 3.2.1
		 * @param {CKEDITOR.editor} editor
		 * @param {Boolean} [returnMultiple=false] Indicates whether the function should return only the first selected link or all of them.
		 * @returns {CKEDITOR.dom.element/CKEDITOR.dom.element[]/null} A single link element or an array of link
		 * elements relevant to the current selection.
		 */
		getSelectedLink: function( editor, returnMultiple ) {
			var selection = editor.getSelection(),
				selectedElement = selection.getSelectedElement(),
				ranges = selection.getRanges(),
				links = [],
				link,
				range;

			if ( !returnMultiple && selectedElement && selectedElement.is( 'a' ) ) {
				return selectedElement;
			}

			for ( var i = 0; i < ranges.length; i++ ) {
				range = selection.getRanges()[ i ];

				// Skip bogus to cover cases of multiple selection inside tables (#tp2245).
				// Shrink to element to prevent losing anchor (#859).
				range.shrink( CKEDITOR.SHRINK_ELEMENT, true, { skipBogus: true } );
				link = editor.elementPath( range.getCommonAncestor() ).contains( 'a', 1 );

				if ( link && returnMultiple ) {
					links.push( link );
				} else if ( link ) {
					return link;
				}
			}

			return returnMultiple ? links : null;
		},

		/**
		 * Determines whether an element should have a "Display Text" field in the Link dialog.
		 *
		 * @since 4.5.11
		 * @param {CKEDITOR.dom.element/null} element Selected element, `null` if none selected or if a ranged selection
		 * is made.
		 * @param {CKEDITOR.editor} editor The editor instance for which the check is performed.
		 * @returns {Boolean}
		 */
		showDisplayTextForElement: function( element, editor ) {
			var undesiredElements = {
					img: 1,
					table: 1,
					tbody: 1,
					thead: 1,
					tfoot: 1,
					input: 1,
					select: 1,
					textarea: 1
				},
				selection = editor.getSelection();

			// Widget duck typing, we don't want to show display text for widgets.
			if ( editor.widgets && editor.widgets.focused ) {
				return false;
			}

			if ( selection && selection.getRanges().length > 1 ) {
				return false;
			}

			return !element || !element.getName || !element.is( undesiredElements );
		}
	};

	CKEDITOR.unlinkCommand = function() {};
	CKEDITOR.unlinkCommand.prototype = {
		exec: function( editor ) {
			// IE/Edge removes link from selection while executing "unlink" command when cursor
			// is right before/after link's text. Therefore whole link must be selected and the
			// position of cursor must be restored to its initial state after unlinking. (https://dev.ckeditor.com/ticket/13062)
			if ( CKEDITOR.env.ie ) {
				var range = editor.getSelection().getRanges()[ 0 ],
					link = ( range.getPreviousEditableNode() && range.getPreviousEditableNode().getAscendant( 'a', true ) ) ||
						( range.getNextEditableNode() && range.getNextEditableNode().getAscendant( 'a', true ) ),
					bookmark;

				if ( range.collapsed && link ) {
					bookmark = range.createBookmark();
					range.selectNodeContents( link );
					range.select();
				}
			}

			var style = new CKEDITOR.style( { element: 'a', type: CKEDITOR.STYLE_INLINE, alwaysRemoveElement: 1 } );
			editor.removeStyle( style );

			if ( bookmark ) {
				range.moveToBookmark( bookmark );
				range.select();
			}
		},

		refresh: function( editor, path ) {
			// Despite our initial hope, document.queryCommandEnabled() does not work
			// for this in Firefox. So we must detect the state by element paths.

			var element = path.lastElement && path.lastElement.getAscendant( 'a', true );

			if ( element && element.getName() == 'a' && element.getAttribute( 'href' ) && element.getChildCount() ) {
				this.setState( CKEDITOR.TRISTATE_OFF );
			} else {
				this.setState( CKEDITOR.TRISTATE_DISABLED );
			}
		},

		contextSensitive: 1,
		startDisabled: 1,
		requiredContent: 'a[href]',
		editorFocus: 1
	};

} )();
