/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

/**
 * Block icon
 */
const icon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	>
		<rect width="7" height="7" x="3" y="3" rx="1" />
		<rect width="7" height="7" x="14" y="3" rx="1" />
		<rect width="7" height="7" x="3" y="14" rx="1" />
		<rect width="7" height="7" x="14" y="14" rx="1" />
		<path d="M8 7h8" />
		<path d="M8 17h8" />
	</svg>
);

/**
 * Register the Related Products block
 */
registerBlockType( metadata.name, {
	...metadata,
	icon,
	edit: Edit,
	// No save function - using render.php
} );
