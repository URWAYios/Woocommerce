// Access the settings for the custom payment method
const settings = window.wc.wcSettings.getSetting('urway_payment_data', {});
 const label = window.wp.i18n.__('Urway Payment', 'urway_payment');
// Icon component to display the payment method icon
const Icon = () => {
       return settings.icon 
        ? window.wp.element.createElement('img', { 
            src: settings.icon,
            style: { 
                height: '50px',  // Apply height to 100px
                maxHeight: '50px',  // Maximum height
                width: 'auto',       // Maintain aspect ratio
                objectFit: 'contain', // Ensure it fits inside the container
                objectPosition: 'left', // Align the image to the left
                marginRight: '10px', // Add space between image and label
            },
            //className: 'wc-block-components-radio-control__label'  // Apply WooCommerce class for additional styling
        }) 
        : '';
};

// Label component to display the payment method label and icon
const Label = () => {
    return window.wp.element.createElement(
        'span',
        { style: { width: '100%', display: 'flex', alignItems: 'center' } },
        label, // The label text
        Icon() // The icon
    );
};

// Content component (uses the Label component)
const Content = () => {
   return window.wp.htmlEntities.decodeEntities( settings.description || '' );

};

// Register the payment method block
const Block_Gateway = {
    name: 'urway_payment',
    label: window.wp.element.createElement(Label, null), // Use Label for the label
    content: window.wp.element.createElement(Content, null), // Use Content component
    edit: window.wp.element.createElement(Content, null),    // Use Content component for editing
    canMakePayment: () => true, // Ensure payment method is always enabled
    ariaLabel: label, // Accessibility label
    supports: {
        features: settings.supports, // Support features defined in the settings
    },
};

// Register the payment method with WooCommerce Blocks
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
