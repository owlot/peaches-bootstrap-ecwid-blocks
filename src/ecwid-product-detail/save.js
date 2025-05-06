/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save( { attributes } ) {
	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
}
