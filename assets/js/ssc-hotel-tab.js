/**
 * StaySuite hotel tab inside the theme's Property Details box.
 *
 * WpRentals renders its room fields as a hardcoded tab skeleton with no
 * registration seam, so this appends a "Hotel (StaySuite)" nav item plus
 * panel and binds the same active_tab switching the theme uses. Field
 * names match RoomLink::save_meta(), which is unchanged.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	if ( typeof sscHotelTab === 'undefined' ) {
		return;
	}
	if ( document.getElementById( 'ssc_hotel' ) ) {
		return;
	}
	var list = document.querySelector( '.property_options_wrapper_list' );
	var wrap = document.querySelector( '.property_options_content_wrapper' );
	if ( ! list || ! wrap ) {
		return;
	}

	var tab = document.createElement( 'div' );
	tab.className = 'property_tab_item';
	tab.setAttribute( 'data-content', 'ssc_hotel' );
	tab.textContent = sscHotelTab.i18n.tab;
	list.appendChild( tab );

	var panel = document.createElement( 'div' );
	panel.className = 'property_tab_item_content';
	panel.id = 'ssc_hotel';

	var heading = document.createElement( 'h3' );
	heading.textContent = sscHotelTab.i18n.tab;
	panel.appendChild( heading );

	var nonce = document.createElement( 'input' );
	nonce.type = 'hidden';
	nonce.name = 'ssc_room_hotel_nonce';
	nonce.value = sscHotelTab.nonce;
	panel.appendChild( nonce );

	var hotelPara = document.createElement( 'p' );
	var hotelLabel = document.createElement( 'label' );
	var hotelStrong = document.createElement( 'strong' );
	hotelStrong.textContent = sscHotelTab.i18n.belongs;
	hotelLabel.appendChild( hotelStrong );
	hotelPara.appendChild( hotelLabel );
	hotelPara.appendChild( document.createElement( 'br' ) );
	var select = document.createElement( 'select' );
	select.name = 'ssc_room_hotel_id';
	select.className = 'widefat';
	select.style.maxWidth = '400px';
	var standalone = document.createElement( 'option' );
	standalone.value = '0';
	standalone.textContent = sscHotelTab.i18n.standalone;
	select.appendChild( standalone );
	( sscHotelTab.hotels || [] ).forEach( function ( hotel ) {
		var option = document.createElement( 'option' );
		option.value = String( hotel.id );
		option.textContent = hotel.title;
		if ( parseInt( hotel.id, 10 ) === parseInt( sscHotelTab.current, 10 ) ) {
			option.selected = true;
		}
		select.appendChild( option );
	} );
	hotelPara.appendChild( select );
	panel.appendChild( hotelPara );

	var pricePara = document.createElement( 'p' );
	var priceLabel = document.createElement( 'label' );
	var priceStrong = document.createElement( 'strong' );
	priceStrong.textContent = sscHotelTab.i18n.original;
	priceLabel.appendChild( priceStrong );
	pricePara.appendChild( priceLabel );
	pricePara.appendChild( document.createElement( 'br' ) );
	var price = document.createElement( 'input' );
	price.type = 'number';
	price.name = 'ssc_room_original_price';
	price.className = 'widefat';
	price.style.maxWidth = '400px';
	price.min = '0';
	price.step = 'any';
	price.placeholder = 'e.g. 2500';
	if ( sscHotelTab.original !== '' ) {
		price.value = sscHotelTab.original;
	}
	pricePara.appendChild( price );
	panel.appendChild( pricePara );

	var description = document.createElement( 'p' );
	description.className = 'description';
	description.textContent = sscHotelTab.i18n.description;
	panel.appendChild( description );

	wrap.appendChild( panel );

	tab.addEventListener( 'click', function () {
		var items = document.querySelectorAll( '.property_tab_item' );
		var panes = document.querySelectorAll( '.property_tab_item_content' );
		for ( var i = 0; i < items.length; i++ ) {
			items[ i ].classList.remove( 'active_tab' );
		}
		for ( var j = 0; j < panes.length; j++ ) {
			panes[ j ].classList.remove( 'active_tab' );
		}
		tab.classList.add( 'active_tab' );
		panel.classList.add( 'active_tab' );
	} );
} );
