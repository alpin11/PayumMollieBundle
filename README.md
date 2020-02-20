# CoreShop Mollie Payum Connector
This bundle activates the Mollie PaymentGateway in CoreShop.
It requires the [alpin11/payum-mollie](https://github.com/alpin11/payum-mollie) repository which will be installed automatically.

## Installation

#### 1. Composer

```json
    "alpin11/payum-mollie-bundle": "~1.0.0"
```

#### 2. Activate
Enable the Bundle in Pimcore Extension Manager or via the CLI

```bash
    bin/console pimcore:bundle:enable MollieBundle
```

#### 3. Setup
Go to CoreShop -> PaymentProvider and add a new provider. Choose `mollie` from `type` and fill out the required fields.
