/**
 * @license Copyright (c) 2003-2020, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module anchor/ui/anchorformview
 */

import { View } from 'ckeditor5/src/ui';
import { ViewCollection } from 'ckeditor5/src/ui';

import { ButtonView } from 'ckeditor5/src/ui';
import { SwitchButtonView } from 'ckeditor5/src/ui';

import { LabeledFieldView } from 'ckeditor5/src/ui';
import { createLabeledInputText } from 'ckeditor5/src/ui';
import { injectCssTransitionDisabler } from 'ckeditor5/src/ui';

import { submitHandler } from 'ckeditor5/src/ui';
import { FocusTracker } from 'ckeditor5/src/utils';
import { FocusCycler } from 'ckeditor5/src/ui';
import { KeystrokeHandler } from 'ckeditor5/src/utils';

import { IconCancel, IconCheck } from '@ckeditor/ckeditor5-icons';
import '../../theme/anchorform.css';
import '@ckeditor/ckeditor5-ui/theme/components/responsive-form/responsiveform.css';

/**
 * The anchor form view controller class.
 *
 * See {@link module:anchor/ui/anchorformview~AnchorFormView}.
 *
 * @extends module:ui/view~View
 */
export default class AnchorFormView extends View {
	/**
	 * Creates an instance of the {@link module:anchor/ui/anchorformview~AnchorFormView} class.
	 *
	 * Also see {@link #render}.
	 *
	 * @param {module:utils/locale~Locale} [locale] The localization services instance.
	 * @param {module:anchor/anchorcommand~AnchorCommand} anchorCommand Reference to {@link module:anchor/anchorcommand~AnchorCommand}.
	 * @param {String} [protocol] A value of a protocol to be displayed in the input's placeholder.
	 */
	constructor( locale, anchorCommand ) {
		super( locale );

		const t = locale.t;

		/**
		 * Tracks information about DOM focus in the form.
		 *
		 * @readonly
		 * @member {module:utils/focustracker~FocusTracker}
		 */
		this.focusTracker = new FocusTracker();

		/**
		 * An instance of the {@link module:utils/keystrokehandler~KeystrokeHandler}.
		 *
		 * @readonly
		 * @member {module:utils/keystrokehandler~KeystrokeHandler}
		 */
		this.keystrokes = new KeystrokeHandler();

		/**
		 * The URL input view.
		 *
		 * @member {module:ui/labeledfield/labeledfieldview~LabeledFieldView}
		 */
		this.urlInputView = this._createUrlInput();

		/**
		 * The Save button view.
		 *
		 * @member {module:ui/button/buttonview~ButtonView}
		 */
		this.saveButtonView = this._createButton( t( 'Save' ), IconCheck, 'ck-button-save' );
		this.saveButtonView.type = 'submit';

		/**
		 * The Cancel button view.
		 *
		 * @member {module:ui/button/buttonview~ButtonView}
		 */
		this.cancelButtonView = this._createButton( t( 'Cancel' ), IconCancel, 'ck-button-cancel', 'cancel' );

		/**
		 * A collection of {@link module:ui/button/switchbuttonview~SwitchButtonView},
		 * which corresponds to {@link module:anchor/anchorcommand~AnchorCommand#manualDecorators manual decorators}
		 * configured in the editor.
		 *
		 * @private
		 * @readonly
		 * @type {module:ui/viewcollection~ViewCollection}
		 */
		this._manualDecoratorSwitches = this._createManualDecoratorSwitches( anchorCommand );

		/**
		 * A collection of child views in the form.
		 *
		 * @readonly
		 * @type {module:ui/viewcollection~ViewCollection}
		 */
		this.children = this._createFormChildren( anchorCommand.manualDecorators );

		/**
		 * A collection of views that can be focused in the form.
		 *
		 * @readonly
		 * @protected
		 * @member {module:ui/viewcollection~ViewCollection}
		 */
		this._focusables = new ViewCollection();

		/**
		 * Helps cycling over {@link #_focusables} in the form.
		 *
		 * @readonly
		 * @protected
		 * @member {module:ui/focuscycler~FocusCycler}
		 */
		this._focusCycler = new FocusCycler( {
			focusables: this._focusables,
			focusTracker: this.focusTracker,
			keystrokeHandler: this.keystrokes,
			actions: {
				// Navigate form fields backwards using the Shift + Tab keystroke.
				focusPrevious: 'shift + tab',

				// Navigate form fields forwards using the Tab key.
				focusNext: 'tab'
			}
		} );

		const classList = [ 'ck', 'ck-anchor-form', 'ck-responsive-form' ];

		if ( anchorCommand.manualDecorators.length ) {
			classList.push( 'ck-anchor-form_layout-vertical', 'ck-vertical-form' );
		}

		this.setTemplate( {
			tag: 'form',

			attributes: {
				class: classList,

				// https://github.com/ckeditor/ckeditor5-anchor/issues/90
				tabindex: '-1'
			},

			children: this.children
		} );

		injectCssTransitionDisabler( this );
	}

	/**
	 * Obtains the state of the {@link module:ui/button/switchbuttonview~SwitchButtonView switch buttons} representing
	 * {@link module:anchor/anchorcommand~AnchorCommand#manualDecorators manual anchor decorators}
	 * in the {@link module:anchor/ui/anchorformview~AnchorFormView}.
	 *
	 * @returns {Object.<String,Boolean>} Key-value pairs, where the key is the name of the decorator and the value is
	 * its state.
	 */
	getDecoratorSwitchesState() {
		return Array.from( this._manualDecoratorSwitches ).reduce( ( accumulator, switchButton ) => {
			accumulator[ switchButton.name ] = switchButton.isOn;
			return accumulator;
		}, {} );
	}

	/**
	 * @inheritDoc
	 */
	render() {
		super.render();

		submitHandler( {
			view: this
		} );

		const childViews = [
			this.urlInputView,
			...this._manualDecoratorSwitches,
			this.saveButtonView,
			this.cancelButtonView
		];

		childViews.forEach( v => {
			// Register the view as focusable.
			this._focusables.add( v );

			// Register the view in the focus tracker.
			this.focusTracker.add( v.element );
		} );

		// Start listening for the keystrokes coming from #element.
		this.keystrokes.listenTo( this.element );
	}

	/**
	 * Focuses the fist {@link #_focusables} in the form.
	 */
	focus() {
		this._focusCycler.focusFirst();
	}

	/**
	 * Creates a labeled input view.
	 *
	 * @private
	 * @returns {module:ui/labeledfield/labeledfieldview~LabeledFieldView} Labeled field view instance.
	 */
	_createUrlInput() {
		const t = this.locale.t;
		const labeledInput = new LabeledFieldView( this.locale, createLabeledInputText );

		labeledInput.label = t( 'Anchor name' );

		return labeledInput;
	}

	/**
	 * Creates a button view.
	 *
	 * @private
	 * @param {String} label The button label.
	 * @param {String} icon The button icon.
	 * @param {String} className The additional button CSS class name.
	 * @param {String} [eventName] An event name that the `ButtonView#execute` event will be delegated to.
	 * @returns {module:ui/button/buttonview~ButtonView} The button view instance.
	 */
	_createButton( label, icon, className, eventName ) {
		const button = new ButtonView( this.locale );

		button.set( {
			label,
			icon,
			tooltip: true
		} );

		button.extendTemplate( {
			attributes: {
				class: className
			}
		} );

		if ( eventName ) {
			button.delegate( 'execute' ).to( this, eventName );
		}

		return button;
	}

	/**
	 * Populates {@link module:ui/viewcollection~ViewCollection} of {@link module:ui/button/switchbuttonview~SwitchButtonView}
	 * made based on {@link module:anchor/anchorcommand~AnchorCommand#manualDecorators}.
	 *
	 * @private
	 * @param {module:anchor/anchorcommand~AnchorCommand} anchorCommand A reference to the anchor command.
	 * @returns {module:ui/viewcollection~ViewCollection} of switch buttons.
	 */
	_createManualDecoratorSwitches( anchorCommand ) {
		const switches = this.createCollection();

		for ( const manualDecorator of anchorCommand.manualDecorators ) {
			const switchButton = new SwitchButtonView( this.locale );

			switchButton.set( {
				name: manualDecorator.id,
				label: manualDecorator.label,
				withText: true
			} );

			switchButton.bind( 'isOn' ).toMany( [ manualDecorator, anchorCommand ], 'value', ( decoratorValue, commandValue ) => {
				return commandValue === undefined && decoratorValue === undefined ? manualDecorator.defaultValue : decoratorValue;
			} );

			switchButton.on( 'execute', () => {
				manualDecorator.set( 'value', !switchButton.isOn );
			} );

			switches.add( switchButton );
		}

		return switches;
	}

	/**
	 * Populates the {@link #children} collection of the form.
	 *
	 * If {@link module:anchor/anchorcommand~AnchorCommand#manualDecorators manual decorators} are configured in the editor, it creates an
	 * additional `View` wrapping all {@link #_manualDecoratorSwitches} switch buttons corresponding
	 * to these decorators.
	 *
	 * @private
	 * @param {module:utils/collection~Collection} manualDecorators A reference to
	 * the collection of manual decorators stored in the anchor command.
	 * @returns {module:ui/viewcollection~ViewCollection} The children of anchor form view.
	 */
	_createFormChildren( manualDecorators ) {
		const children = this.createCollection();

		children.add( this.urlInputView );

		if ( manualDecorators.length ) {
			const additionalButtonsView = new View();

			additionalButtonsView.setTemplate( {
				tag: 'ul',
				children: this._manualDecoratorSwitches.map( switchButton => ( {
					tag: 'li',
					children: [ switchButton ],
					attributes: {
						class: [
							'ck',
							'ck-list__item'
						]
					}
				} ) ),
				attributes: {
					class: [
						'ck',
						'ck-reset',
						'ck-list'
					]
				}
			} );
			children.add( additionalButtonsView );
		}

		children.add( this.saveButtonView );
		children.add( this.cancelButtonView );

		return children;
	}
}

/**
 * Fired when the form view is submitted (when one of the children triggered the submit event),
 * for example with a click on {@link #saveButtonView}.
 *
 * @event submit
 */

/**
 * Fired when the form view is canceled, for example with a click on {@link #cancelButtonView}.
 *
 * @event cancel
 */
