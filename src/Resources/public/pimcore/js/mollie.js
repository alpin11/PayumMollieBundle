pimcore.registerNS('coreshop.provider.gateways.mollie');
coreshop.provider.gateways.mollie = Class.create(coreshop.provider.gateways.abstract, {
    getLayout: function (config) {

        var methods = Ext.create('Ext.data.Store', {
            fields: ['key', 'name'],
            data: [
                {"key": "applepay", "name": "Apple Pay"},
                {"key": "bancontact", "name": "Bancontact"},
                {"key": "banktransfer", "name": "Bank Transfer"},
                {"key": "belfius", "name": "Belfius"},
                {"key": "creditcard", "name": "Credit Card"},
                {"key": "directdebit", "name": "Direct Debit"},
                {"key": "eps", "name": "EPS"},
                {"key": "giftcard", "name": "Gift Card"},
                {"key": "giropay", "name": "GiroPay"},
                {"key": "ideal", "name": "Ideal"},
                {"key": "inghomepay", "name": "ING Home'Pay"},
                {"key": "kbc", "name": "KBC"},
                {"key": "mybank", "name": "myBank"},
                {"key": "paypal", "name": "PayPal"},
                {"key": "paysafecard", "name": "Paysafe Card"},
                {"key": "przelewy24", "name": "Przelewy24"},
                {"key": "sofort", "name": "Sofort"},
                {"key": "klarnapaylater", "name": "Klarna Pay Later"},
                {"key": "klarnasliceit", "name": "Klarna Slice It"},
            ]
        });

        var methodCombo = Ext.create('Ext.form.ComboBox', {
            fieldLabel: t('mollie_method'),
            store: methods,
            queryMode: 'local',
            displayField: 'name',
            valueField: 'key',
            name: 'gatewayConfig.config.method',
            multiSelect: true,
            value: config.method ? config.method : []
        });

        return [
            {
                xtype: 'textfield',
                fieldLabel: t('mollie_api_key'),
                name: 'gatewayConfig.config.apiKey',
                length: 255,
                value: config.apiKey ? config.apiKey : ""
            },
            methodCombo
        ];
    }
});
