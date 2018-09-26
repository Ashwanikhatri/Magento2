define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {

                template: 'Creationent_Payware/payment/payware'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'creationent_payware';
            },

            isActive: function() {
                return true;
            }
        });
    }
);