/**
 * This file is part of BNS Featured Tag
 * Last revised at version 2.2
 *
 * Copyright 2009-2014  Edward Caissie  (email : edward.caissie@gmail.com)
 *
 * BNS Featured Category is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * BNS Featured Category is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 */
jQuery( document ).ready( function ( $ ) {
	// Note: $() will work as an alias for jQuery() inside of this function
	$( "p.bnsft-display-all-posts-check input.checkbox" ).click( function () {
		$( ".bnsft-all-options-open" ).toggleClass( "bnsft-all-options-closed" );
	} );

	$( "p.bnsft-display-thumbnail-sizes input.checkbox" ).click( function () {
		$( ".bnsft-thumbnails-open" ).toggleClass( "bnsft-thumbnails-closed" );
	} );

	$( "p.bnsft-excerpt-option-open-check input.checkbox" ).click( function () {
		$( ".bnsft-excerpt-option-open" ).toggleClass( "bnsft-excerpt-option-closed" );
	} );
} );