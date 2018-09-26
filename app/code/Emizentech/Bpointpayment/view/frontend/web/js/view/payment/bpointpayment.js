define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'emizentech_bpointpayment',
                component: 'Emizentech_Bpointpayment/js/view/payment/method-renderer/bpointpayment'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
