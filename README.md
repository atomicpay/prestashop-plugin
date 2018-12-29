![AtomicPay](https://github.com/atomicpay/prestashop-plugin/blob/master/assets/atomicpay-plugin-header.png)
## AtomicPay For PrestaShop Module
This is an open source module for PrestaShop, allowing merchants to start accepting cryptocurrency payments on their PrestaShop website by simply installing the module. AtomicPay is a decentralized cryptocurrency payment processor that eliminates the involvement of a third-party payment gateway, allowing merchants to accept payments directly from their customers, in a secured and trustless environment.

### Follow us on our developments
We develop in lightning speed! If you would like to keep up with what we are building or our upcoming cool features, please follow us on AtomicPay Official Channels:
* [Official Twitter](https://twitter.com/AtomicPay)
* [Official Facebook](https://www.facebook.com/atomicpay)
* [Official Instagram](https://instagram.com/atomicpay.io/)
* [Official YouTube](https://www.youtube.com/channel/UCm8tPvdxi8PA97ZMIINYjZQ)
* [Official Reddit](https://www.reddit.com/r/atomicpay)
* [Official Telegram](https://t.me/atomicpay)
* And obviously, please follow us on [Official Github](https://github.com/atomicpay)

Meanwhile, here is a short deck to learn more about AtomicPay: https://www.slideshare.net/atomicpay/atomicpay-decentralized-noncustodial-payment-gateway-126944216

## Prerequisites
* Compatible with v1.6 and v1.7.
* **Last Version Tested:** PrestaShop 1.7.5

## Server Requirements

* SSL is Highly Recommended
* [PrestaShop](https://www.prestashop.com/en/system-requirements) >= 1.6
* [PHP5 Curl](http://php.net/manual/en/curl.installation.php) Must be compiled with PHP
* PHP >= 5.4 (Tested on v7.1)
* JSON

## Getting Started
AtomicPay For PrestaShop module is designed to be **"Plug-n-Play" installation** without any programming knowledge. Anyone can do it! To set up our module quickly, please follow the following guide.

- You must have a AtomicPay merchant account and API keys to use this module. It's free to [sign-up for a AtomicPay merchant account](https://merchant.atomicpay.io/beta-registration)
- Once registered, you may retrieve the API keys by login to [AtomicPay Merchant Account](https://merchant.atomicpay.io/login) and go to [API Integration](https://merchant.atomicpay.io/apiIntegration) page. If your key becomes compromised, you may revoke the keys by regenerating new set of keys.
- Below is the installation guide for PrestaShop v1.7. For v1.6 installation, we will release a step by step video shortly

## Installation Guide For v1.7
Visit the [Releases](https://github.com/atomicpay/prestashop-plugin/releases) page of this repository and download the latest version. Once this is done, you can just go to PrestaShop Admin's **Modules > Module Catalog > Install a module**, select the downloaded file and installation will auto-run. After the module is installed, click on **Configure**.

**WARNING:** It is good practice to backup your databases before installing modules. Please make sure you have created backups.

### Youtube Video - Step by Step Installation
Click on the image to view our installation video

[![Video - Step by Step Installation For PrestaShop](https://github.com/atomicpay/prestashop-plugin/blob/master/assets/video.png)](https://youtu.be/4yJbK6K9kO4)

## Authorization Pairing
Authorization Pairing can be performed using the Setting section of AtomicPay module.
Once logged in, you can find the configuration settings under **Modules > Module manager > Other > AtomicPay > Configure**.

#### STEP 1
Login to your [AtomicPay Merchant Account](https://merchant.atomicpay.io/login) and go to [API Integration](https://merchant.atomicpay.io/apiIntegration) page. You will need the following values for next step: `ACCOUNT ID`, `PRIVATE KEY` and `PUBLIC KEY`.

![API Keys](https://github.com/atomicpay/prestashop-plugin/blob/master/assets/getting-keys.png)

#### STEP 2
Here you will need to copy and paste the values from STEP 1 into the corresponding fields: `Account ID`, `Private Key` and `Public Key`.

Next, we will need you to select a default **Transaction Speed** value. `HIGH Risk` speed require 1 confirmation, and can be used for digital goods or low-risk items. `MEDIUM Risk` speed require at least 2 confirmations, and should be used for mid-value items. `LOW Risk` speed require at least 6 confirmations (averaging 30 mins, depending on selected cryptocurrency), and should be used for high-value items.

![Step 2](https://github.com/atomicpay/prestashop-plugin/blob/master/assets/authorization.png)

Click on the button **Save Setting**. The module will attempt to connect to AtomicPay Server for an authorization.

Once authorization is successful, you should see the following message "Settings updated. Authorization Successful"

![Successful Message](https://github.com/atomicpay/prestashop-plugin/blob/master/assets/success.png)

## Usage
Once authorized, your customers will be given the option to pay via AtomicPay which will redirect them to AtomicPay checkout UI to complete the payment. On your PrestaShop backend, everything remains the same as how you would use other payment processors such as PayPal, etc. AtomicPay is designed to be an addtional option on top of the existing payment options which you are already offering. There will be no conflicts with other modules.

**Note: In order for AtomicPay to generate payment invoices, please remember to setup your cryptocurrency view-only wallets at AtomicPay Merchant Panel.**

## Troubleshooting and Debugging
In the event where you experience issues or bugs, please open an issue by following our [Bug Reporting Guidelines](CONTRIBUTING.md#bugs)

## Contributions & Developments
Anyone and everyone is welcome to contribute or develop for this module. Please take a moment to review the [guidelines for contributing to AtomicPay for PrestaShop Module](https://github.com/atomicpay/prestashop-plugin/blob/master/CONTRIBUTING.md).

- [Bug reports](CONTRIBUTING.md#bugs)
- [Feature requests](CONTRIBUTING.md#features)
- [Pull requests](CONTRIBUTING.md#pull-requests)

## License
AtomicPay is released under the MIT License. Please refer to the [License](https://github.com/atomicpay/prestashop-plugin/blob/master/LICENSE) file that accompanies this project for more information including complete terms and conditions.
