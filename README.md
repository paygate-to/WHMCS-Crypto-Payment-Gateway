# WHMCS-Crypto-Payment-Gateway
No KYC crypto payment gateway solution for WHMCS. Accept crypto payments self hosted on your WHMCS website.

> **The only crypto payment gateway with instant payouts for Solana (SOL), Monero (XMR), Zcash (ZEC) and Bitcoin (BTC).**
> Every payment is forwarded straight to your own wallet the moment it is confirmed: no holds, no sign up, no KYC. The same support is built into all our plugins and modules: WooCommerce, WHMCS, OpenCart, PrestaShop and Magento.

## Features

- Accept **Bitcoin, Solana, Monero, Zcash, ETH, USDT, USDC** and hundreds of other coins and tokens on BTC, BCH, LTC, DOGE, ETH, ERC20, BEP20, Polygon, Arbitrum, Base, Optimism, Avalanche-C, Solana, Tron (TRC20), TON and more ([full list and minimums](https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/#minimumorder)).
- **Instant payouts**: crypto is forwarded to your wallet the moment the payment is confirmed, with no delay or holds.
- **No KYC and no registration**: you and your customers stay anonymous.
- **QR code on your own site** with a unique address for every order, or one **Multicoin** option where the customer picks any coin on the hosted page.
- Payment confirmations signed by PayGate.to and verified by the module: orders are marked paid by themselves.
- Choose who pays the blockchain fee: you or the customer.
- If a customer sends the wrong token or uses the wrong network, supported tokens are still forwarded to you automatically. In Individual Coins mode the invoice is then not marked paid automatically: check it and mark it paid yourself.
- Works with your own checkout domain and a free dedicated API endpoint ([custom domain guide](https://paygate.to/white-label-api-custom-domain-guide/)).
- Fees: **1.5% flat rate + blockchain fees**.

📘 **Step-by-step guide:** [https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/whmcs/](https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/whmcs/)

## Requirements

- WHMCS with admin access, and FTP/SFTP or your hosting File Manager
- PHP with cURL and OpenSSL, and outbound HTTPS from your server (the coin list is loaded live)
- Your WHMCS **System URL** set to your public https address
- A wallet address for each coin you want to accept

## Installation

1. **Prepare your wallets.** Crypto is forwarded to **your own wallet addresses**, one per coin or network: a Bitcoin address for BTC, a Solana address for SOL and Solana tokens, Monero and Zcash addresses for XMR and ZEC, and one `0x` address for ETH and all EVM networks (ERC20, BEP20, Polygon, Arbitrum, Base, Optimism, Avalanche-C). Use **self-custodial wallets** you control and **never exchange deposit addresses**.
2. **Download** [WHMCS-Crypto-Payment-Gateway-main.zip](https://github.com/paygate-to/WHMCS-Crypto-Payment-Gateway/archive/refs/heads/main.zip) and unzip it.
3. **Upload the `modules` folder** into your **WHMCS root** (the folder with `configuration.php`), merging it with your existing `modules` folder. Keep the `paygatecrypto` folder (`lib.php` and `keys/`): both gateways use it.

   ```
   WHMCS-Crypto-Payment-Gateway-main/       <whmcs root>/
   └── modules/            ── upload ──►    └── modules/gateways/
                                                ├── paygatecrypto.php
                                                ├── paygatecryptomulti.php
                                                ├── paygatecrypto/       (lib.php, keys, logo)
                                                ├── paygatecryptomulti/  (logo)
                                                └── callback/
                                                    ├── paygatecrypto.php
                                                    └── paygatecryptomulti.php
   ```

4. **Activate**: **Configuration › System Settings › Payment Gateways › All Payment Gateways** (WHMCS 7: **Setup › Payments › Payment Gateways**). Click **Crypto Payment Gateway (Individual Coins)** and/or **Crypto Payment Gateway (Multicoin / Multi Mode)**.
5. **Individual Coins**: the settings show one wallet field per coin, loaded live from PayGate.to. Fill in a wallet for every coin you want (Bitcoin, Solana, Monero, Zcash, USDT…) and leave the others blank. Also set **Underpaid Tolerance** (1% default) and **Customer pays blockchain fees**. Save.
6. **Multicoin (optional)**: fill in one wallet per network:

   | Field | Accepts |
   |---|---|
   | EVM Wallet Address | ETH and tokens on ERC20, BEP20, Polygon, Optimism, Arbitrum, Base, Avax-C |
   | Bitcoin Wallet Address (BTC) | Bitcoin |
   | Solana Wallet Address (SOL) | SOL and Solana tokens |
   | Monero Wallet Address (XMR) | Monero |
   | Zcash Wallet Address (ZEC) | Zcash |
   | BCH, LTC, DOGE, TRC20 (Tron/USDT), TON | One field each |

   Optional: **Custom Domain** (default `checkout.paygate.sbs`), **Custom Logo URL** and colours for the hosted page.
7. **Test** with an invoice. Individual Coins: the client picks a coin and sees the QR code, amount and address on the invoice. Multicoin: the client clicks **Pay with cryptocurrency**. When the payment is confirmed the crypto is forwarded to your wallet and the invoice is marked **Paid** (see also **Billing › Gateway Log**).

The module keeps its payment data for each invoice in the invoice Notes: do not edit them while a crypto payment is pending. If you used our older per-coin modules, deactivate those gateways.

## Troubleshooting

- **"Coin list" error in the settings**: allow outbound HTTPS from your server to `api.paygate.to`.
- **"No cryptocurrencies are currently available"**: fill in at least one coin wallet.
- **Invoice stays Unpaid**: check the System URL and that `/modules/gateways/callback/` is reachable; look for "Underpaid" in the Gateway Log. See [WHMCS troubleshooting](https://paygate.to/whmcs-payment-gateway-troubleshooting/).

Full product page: [https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/](https://paygate.to/crypto-payment-gateway-no-kyc-instant-payouts/) · Support: [Telegram](https://t.me/PayGate_to)
