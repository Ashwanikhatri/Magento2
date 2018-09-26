define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Emizentech_Bpointpayment/payment/bpointpayment'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'emizentech_bpointpayment';
            },

            isActive: function() {
                return true;
            }
        });
    }
);