( function () {
    'use strict';
    if ( ! window.wc || ! window.wc.wcBlocksRegistry || ! window.wc.wcSettings || ! window.wp ) { return; }
    const data = window.wc.wcSettings.getSetting( 'jesb_infinitepay_data', {} );
    const create = window.wp.element.createElement;
    const decode = window.wp.htmlEntities.decodeEntities;
    const content = create( 'div', null, decode( data.description || '' ) );
    window.wc.wcBlocksRegistry.registerPaymentMethod( {
        name: 'jesb_infinitepay',
        gatewayId: 'jesb_infinitepay',
        paymentMethodId: 'jesb_infinitepay',
        title: decode( data.title || 'InfinitePay' ),
        description: decode( data.description || '' ),
        label: create( 'span', null, decode( data.title || 'InfinitePay' ) ),
        content: content,
        edit: content,
        canMakePayment: () => true,
        ariaLabel: decode( data.title || 'InfinitePay' ),
        supports: { features: data.supports || [ 'products' ] },
    } );
} )();
