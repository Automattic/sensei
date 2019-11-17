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
	TextControl,
	SelectControl,
} from '@wordpress/components';

class Edit extends Component {
	render() {
		const { attributes, className, setAttributes } = this.props;1
		const { exclude, ids, number, order, orderby, teacher } = attributes;
		const classes = classNames( className, 'sensei-lms-course-shortcode-block' );

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
						<SelectControl
							label={ __( 'Order By', 'sensei-lms' ) }
							value={ orderby }
							options={ [
								{ label: __( 'Course Title', 'sensei-lms' ), value: 'title' },
								{ label: __( 'Date', 'sensei-lms' ), value: 'date' },
								{ label: __( 'Modified Date', 'sensei-lms' ), value: 'modified' },
								{ label: __( 'Menu Order', 'sensei-lms' ), value: 'menu_order' },
								{ label: __( 'Random', 'sensei-lms' ), value: 'rand' },
								{ label: __( 'Teacher', 'sensei-lms' ), value: 'author' },
							] }
							onChange={ ( orderby ) => setAttributes( { orderby } ) }
						/>
						<SelectControl
							label={ __( 'Order Direction', 'sensei-lms' ) }
							value={ order }
							options={ [
								{ label: __( 'Ascending', 'sensei-lms' ), value: 'ASC' },
								{ label: __( 'Descending', 'sensei-lms' ), value: 'DESC' },
							] }
							onChange={ ( order ) => setAttributes( { order } ) }
						/>
						<TextControl
							label={ __( 'Teacher IDs', 'sensei-lms' ) }
							value={ teacher }
							help={ __( 'Filter to just certain teacher IDs (separated by commas).', 'sensei-lms' ) }
							onChange={ ( teacher ) => setAttributes( { teacher } ) }
						/>
						<TextControl
							label={ __( 'Include Post IDs', 'sensei-lms' ) }
							value={ ids }
							help={ __( 'If set, include only these post IDs (separated by commas).', 'sensei-lms' ) }
							onChange={ ( ids ) => setAttributes( { ids } ) }
						/>
						<TextControl
							label={ __( 'Exclude Post IDs', 'sensei-lms' ) }
							value={ exclude }
							help={ __( 'If set, exclude these post IDs (separated by commas).', 'sensei-lms' ) }
							onChange={ ( exclude ) => setAttributes( { exclude } ) }
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
