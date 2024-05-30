const settings = window.wc.wcSettings.getSetting( 'wcmolpay_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Fiuu', 'wcmolpay' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

// Define the logo URL
const logoSrc = settings.icon.rc;

// Create a component for the label to ensure HTML is rendered correctly
const LabelComponent = () => {
    return window.wp.element.createElement(
      'div',
      { style: { width: '100%', display: 'flex', alignItems: 'center' } }, // Add inline styles to the outer div
      label,
      window.wp.element.createElement(
        'div',
        { style: { marginLeft: 'auto' } }, // Add inline styles to the inner div
        window.wp.element.createElement('img', {
          src: logoSrc,
          alt: 'Logo',
          style: { width: '65px', marginRight: '20px' } // Add inline styles to the img element
        })
      )
    );
  };;

const Block_Gateway = {
    name: 'wcmolpay',
    label: Object( window.wp.element.createElement )( LabelComponent, null ),
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );