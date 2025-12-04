/**
 *
 */
(function() {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { decodeEntities } = wp.htmlEntities;
    const { __ } = wp.i18n;
    const { createElement } = wp.element;

    const settings = wc.wcSettings.getPaymentMethodData('binance_pay_c2c');

    if (!settings) {
        return;
    }

    const paymentMethodName = 'binance_pay_c2c';



    const Content = () => {
        const description = decodeEntities(settings.description || '');

        return createElement(
            'div',
            null,
            description
        );
    };

    const Label = () => {
        const title = decodeEntities(settings.title || '');
        const iconUrl = decodeEntities(settings.icon || '');

        if (!iconUrl) {
            return createElement(
                'span',
                null,
                title
            );
        }

        return createElement(
            'span',
            null,
            title,
            createElement('img', {
                src: iconUrl,
                alt: title,
                style: {
                    height: '1.4em',
                    verticalAlign: 'middle',
                    marginLeft: '10px'
                }
            })
        );
    };


    const options = {
        name: paymentMethodName,
        label: createElement(Label),
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => true,
        ariaLabel: decodeEntities(settings.title || 'C2C Crypto'),
        supports: {
            features: settings.supports?.features || [],
        },
    };

    registerPaymentMethod(options);

})();
