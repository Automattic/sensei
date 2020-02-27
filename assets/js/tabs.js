( function() {

	senseiTabs();

	/**
	 * Add tab toggling functionality to .sensei-tabs elements.
	 *
	 * @example
			<ul class="sensei-tabs">
				<li><a href="#tab1-content">First tab</a></li>
				<li><a href="#tab2-content">Second tab</a></li>
			</ul>
			<div id="tab1-content">...</div>
			<div id="tab2-content">...</div>
	 *
	 */
	function senseiTabs() {

		nodeListToArray( document.querySelectorAll( '.sensei-tabs' ) )
				.forEach( setupTabs );

		function setupTabs( container ) {
			const tabs = nodeListToArray( container.querySelectorAll( 'li a' ) ).map( tabNode => ( {
				tabNode,
				contentNode: document.querySelector( tabNode.getAttribute( 'href' ) )
			} ) );

			tabs.forEach( tab => {
				tab.tabNode.addEventListener( 'click', e => {
					showTab( tab );
					e.preventDefault();
				} )
			} );

			if ( tabs.length )
				showTab( tabs[ 0 ] );

			function showTab( tab ) {
				tabs.forEach( otherTab => {
					toggleTab( otherTab, false )
				} );
				toggleTab( tab, true )
			}

			function toggleTab( tab, state ) {
				toggleClass( tab.tabNode, 'sensei-tab-active', state );
				toggleClass( tab.contentNode, 'sensei-tab-hidden', !state )
			}
		}
	}

	/**
	 * IE9-compatible className toggling
	 *
	 * @since 3.0.0
	 * @access private
	 * @param {HTMLElement} element
	 * @param {string} className
	 * @param {boolean} state
	 */
	function toggleClass( element, className, state ) {
		if ( state ) {
			if ( element.className.indexOf( className ) < 0 ) {
				element.className += " " + className;
			}
		} else {
			element.className = element.className.replace( className, "" ).trim();
		}
	}

	/**
	 * Convert NodeList to array in IE-compatible way
	 *
	 * @since 3.0.0
	 * @access private
	 * @param {NodeList} nodeList
	 */
	function nodeListToArray( nodeList ) {
		const result = [];
		Array.prototype.forEach.call( nodeList, function( node ) {
			result.push( node );
		} );

		return result;

	}
} )();