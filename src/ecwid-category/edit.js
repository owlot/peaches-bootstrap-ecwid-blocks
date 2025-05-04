/**
 * External dependencies
 */
import clsx from 'clsx';

const { serverSideRender: ServerSideRender } = wp;
const { Fragment, useEffect } = wp.element;
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

const SUPPORTED_SETTINGS = {
	responsive: {
		sizes: {
			rowCols: true,
		},
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			textAlign: true,
			justifyContent: true,
			alginItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
			gutter: true,
		},
	},
};

function CategoryEdit( props ) {
	const { attributes, setAttributes } = props;

	useEffect( () => {
		setAttributes( {
			classes: clsx( 'row', computeClassName( attributes ) ),
		} );
	}, [ attributes, setAttributes ] );

	const setAttributesCB = ( obj ) => {
		setAttributes( obj );
		setAttributes( {
			classes: clsx( 'row', computeClassName( attributes ) ),
		} );
	};

	return (
		<>
			<InspectorControls>
				<BootstrapSettingsPanels
					setAttributes={ setAttributesCB }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>
			<ServerSideRender
				attributes={ attributes }
				block="peaches/ecwid-category"
			/>
		</>
	);
}

export default CategoryEdit;
