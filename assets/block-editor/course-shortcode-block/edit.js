/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';

import './editor.scss';
import {
	PanelBody,
	RangeControl,
	ServerSideRender,
} from '@wordpress/components';

class Edit extends Component {
	render() {
		const { attributes, className, setAttributes } = this.props;
		const { number }                               = attributes;
		const classes                                  = classNames( className, 'sensei-lms-course-shortcode-block' );
		return (
			<Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'sensei-lms' ) } >
						<RangeControl
							label={ __( 'Number of Courses', 'sensei-lms' ) }
							value={ number }
							onChange={ ( number ) => setAttributes( { number } ) }
							min={1}
							max={100}
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender
					block="sensei-lms/course-shortcode-block"
					className={ classes }
					attributes={ attributes }
				/>
			</Fragment>
		);
	}
}
export default Edit;
