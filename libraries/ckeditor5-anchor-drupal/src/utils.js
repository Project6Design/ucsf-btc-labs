/**
 * @license Copyright (c) 2003-2020, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module anchor/utils
 */

import { upperFirst } from 'es-toolkit/string';
import { toWidget } from "ckeditor5/src/widget";

const ATTRIBUTE_WHITESPACES = /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205f\u3000]/g; // eslint-disable-line no-control-regex
const SAFE_URL = /^(?:(?:https?|ftps?|mailto):|[^a-z]|[a-z+.-]+(?:[^a-z+.:-]|$))/i;

// Simplified email test - should be run over previously found URL.
const EMAIL_REG_EXP = /^[\S]+@((?![-_])(?:[-\w\u00a1-\uffff]{0,63}[^-_]\.))+(?:[a-z\u00a1-\uffff]{2,})$/i;

// The regex checks for the protocol syntax ('xxxx://' or 'xxxx:')
// or non-word characters at the beginning of the anchor ('/', '#' etc.).
const PROTOCOL_REG_EXP = /^((\w+:(\/{2,})?)|(\W))/i;

/**
 * A keystroke used by the {@link module:anchor/anchorui~AnchorUI anchor UI feature}.
 */
export const LINK_KEYSTROKE = 'Ctrl+M';

/**
 * Returns `true` if a given view node is the anchor element.
 *
 * @param {module:engine/view/node~Node} node
 * @returns {Boolean}
 */
export function isAnchorElement( node ) {
	return (
        node.is('attributeElement') && !!node.getCustomProperty( 'anchor' )
    );
}

/**
 * Creates a anchor {@link module:engine/view/attributeelement~AttributeElement} with the provided `id` attribute.
 *
 * @param {String} id
 * @param {module:engine/conversion/downcastdispatcher~DowncastConversionApi} conversionApi
 * @returns {module:engine/view/attributeelement~AttributeElement}
 */
export function createAnchorElement( id, { writer } ) {
	// Priority 5 - https://github.com/ckeditor/ckeditor5-anchor/issues/121.
	const anchorElement = writer.createAttributeElement( 'a', { id }, { priority: 5 } );
	writer.addClass("ck-anchor", anchorElement);
	writer.setCustomProperty( 'anchor', true, anchorElement );

	return anchorElement;
}

/**
 * Creates an empty anchor {@link module:engine/view/emptyelement~EmptyElement} with the provided `id` attribute.
 *
 * @param {String} id
 * @param {module:engine/conversion/downcastdispatcher~DowncastConversionApi} conversionApi
 * @returns {module:engine/view/emptyelement~EmptyElement}
 */
export function createEmptyAnchorElement( id, { writer } ) {
	let anchorElement = null;
	anchorElement = writer.createEmptyElement( 'a', { id });

	writer.addClass("ck-anchor", anchorElement);
	writer.setCustomProperty( 'anchor', true, anchorElement );

	return anchorElement;
}

/**
 * Creates an SVG placeholder {@link module:engine/view/emptyelement~EmptyElement} with the provided `id` attribute.
 *
 * @param {String} anchorId
 * @param {module:engine/conversion/downcastdispatcher~DowncastConversionApi} conversionApi
 * @returns {module:engine/view/emptyelement~EmptyElement}
 */
export function createEmptyPlaceholderAnchorElement( anchorId, { writer } ) {
	const anchorElement = writer.createContainerElement('span', {
		class: 'ck-anchor-placeholder',
	}, [writer.createText(`[INVISIBLE ANCHOR: ${anchorId}]`)]);
	return toWidget(anchorElement, writer );
}

/**
 * Returns a safe URL based on a given value.
 *
 * A URL is considered safe if it is safe for the user (does not contain any malicious code).
 *
 * If a URL is considered unsafe, a simple `"#"` is returned.
 *
 * @protected
 * @param {*} url
 * @returns {String} Safe URL.
 */
export function ensureSafeUrl( url ) {
	url = String( url );

	return isSafeUrl( url ) ? url : '#';
}

// Checks whether the given URL is safe for the user (does not contain any malicious code).
//
// @param {String} url URL to check.
function isSafeUrl( url ) {
	const normalizedUrl = url.replace( ATTRIBUTE_WHITESPACES, '' );

	return normalizedUrl.match( SAFE_URL );
}

/**
 * Returns the {@link module:anchor/anchor~AnchorConfig#decorators `config.anchor.decorators`} configuration processed
 * to respect the locale of the editor, i.e. to display the {@link module:anchor/anchor~AnchorDecoratorManualDefinition label}
 * in the correct language.
 *
 * **Note**: Only the few most commonly used labels are translated automatically. Other labels should be manually
 * translated in the {@link module:anchor/anchor~AnchorConfig#decorators `config.anchor.decorators`} configuration.
 *
 * @param {module:utils/locale~Locale#t} t shorthand for {@link module:utils/locale~Locale#t Locale#t}
 * @param {Array.<module:anchor/anchor~AnchorDecoratorDefinition>} The decorator reference
 * where the label values should be localized.
 * @returns {Array.<module:anchor/anchor~AnchorDecoratorDefinition>}
 */
export function getLocalizedDecorators( t, decorators ) {
	const localizedDecoratorsLabels = {};

	decorators.forEach( decorator => {
		if ( decorator.label && localizedDecoratorsLabels[ decorator.label ] ) {
			decorator.label = localizedDecoratorsLabels[ decorator.label ];
		}
		return decorator;
	} );

	return decorators;
}

/**
 * Converts an object with defined decorators to a normalized array of decorators. The `id` key is added for each decorator and
 * is used as the attribute's name in the model.
 *
 * @param {Object.<String, module:anchor/anchor~AnchorDecoratorDefinition>} decorators
 * @returns {Array.<module:anchor/anchor~AnchorDecoratorDefinition>}
 */
export function normalizeDecorators( decorators ) {
	const retArray = [];

	if ( decorators ) {
		for ( const [ key, value ] of Object.entries( decorators ) ) {
			const decorator = Object.assign(
				{},
				value,
				{ id: `anchor${ upperFirst( key ) }` }
			);
			retArray.push( decorator );
		}
	}

	return retArray;
}

/**
 * Returns `true` if the specified `element` is an image and it can be anchored (the element allows having the `anchorId` attribute).
 *
 * @params {module:engine/model/element~Element|null} element
 * @params {module:engine/model/schema~Schema} schema
 * @returns {Boolean}
 */
export function isImageAllowed( element, schema ) {
	if ( !element ) {
		return false;
	}

	return element.is( 'element', 'image' ) && schema.checkAttribute( 'image', 'anchorId' );
}

/**
 * Returns `true` if the specified `value` is an email.
 *
 * @params {String} value
 * @returns {Boolean}
 */
export function isEmail( value ) {
	return EMAIL_REG_EXP.test( value );
}

/**
 * Adds the protocol prefix to the specified `anchor` when:
 *
 * * it does not contain it already, and there is a {@link module:anchor/anchor~AnchorConfig#defaultProtocol `defaultProtocol` }
 * configuration value provided,
 * * or the anchor is an email address.
 *
 *
 * @params {String} anchor
 * @params {String} defaultProtocol
 * @returns {Boolean}
 */
export function addAnchorProtocolIfApplicable( anchor, defaultProtocol ) {
	const protocol = isEmail( anchor ) ? 'mailto:' : defaultProtocol;
	const isProtocolNeeded = !!protocol && !PROTOCOL_REG_EXP.test( anchor );

	return anchor && isProtocolNeeded ? protocol + anchor : anchor;
}
