[![deutsche Version](https://logos.oxidmodule.com/de2_xs.svg)](README.md)
[![english version](https://logos.oxidmodule.com/en2_xs.svg)](README.en.md)

# D³ WebAuthn / FIDO2 Login for OXID eShop

With this module, the login in the OXID shop can be carried out with a hardware token instead of a password. 

This secures the login in the frontend and (if allowed for the user) also in the backend.

Security keys are devices that contain cryptographic keys. These can be used for two-factor authentication. The security key must support the standard "[WebAuthn](https://w3c.github.io/webauthn/#webauthn-authenticator)".

The key management is done in the admin area and in the user's "My Account".

## Table of content

- [What is FIDO2?](#what-is-fido2)
- [Module installation](#module-installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)
- [Further licences and terms of use](#further-licences-and-terms-of-use)

## What is FIDO2?

It enables secure authentication of the user in web-based user interfaces via a browser. Instead of a password ("knowledge" factor), one logs in with special hardware that uses strong public key cryptography for verification ("ownership" factor). Due to the way it is implemented, FIDO2 logins cannot be intercepted by phishing. 

FIDO2 describes the entire authentication process, WebAuthn and CTAP are subcomponents of this process. CTAP is the communication from the FIDO2 device to the client / browser. WebAuthn handles the transmission from the browser to the application (shop).

Any FIDO2-certified hardware can be used for registration. This can be:

- Cross-Platform Authenticators (device-independent):
  - USB tokens (e.g. Solokey or YubiKey),
  - NFC transmitters
  - Bluetooth transmitters
  - Smartcards
- Platform Authenticators (device-dependent)
  - Face ID devices
  - Windows Hello devices
- Hybrid authenticators
  - Smartphones with Touch ID (Android from version 7, iOS from version 14),

FIDO2 can be used as:
- an additional 2nd factor to the existing username-password combination
- as a secure substitute instead of a password (passwordless, still in connection with the entry of a user name)
- as a complete substitute for user name and password (with login data stored in the FIDO2 device).

In this module, passwordless login is currently implemented (2nd option). 
For the 1st options we see too little security gain compared to option 2. The implementation of the 3rd option is technically feasible, but for the normal field of application not very relevant and technically complex. If required, we are available for enquiries.

When registering a FIDO2 key, access data is created in order to be able to check a later login attempt. These access data are firmly bound to the customer account and the shop and cannot be exchanged with each other.

## Module installation

This package requires an Composer installed OXID eShop as defined in [composer.json](composer.json).

Open a command line interface and navigate to the shop root directory (parent of source and vendor). Execute the following command. Adapt the paths to your environment.

```bash
php composer require d3/oxwebauthn:^1.0
``` 

If a reference to an unsuitable package `symfony/process` is shown, this must be changed. To do this, please add the switch `-W` to the above command (`... require -W ...`).

Activate the module in the admin area of the shop in "Extensions -> Modules".

## Usage

The shop account is opened (as usual) with user name and password. Afterwards, [FIDO2](https://fidoalliance.org/) keys can be added as an additional authentication option. From this moment on, logging into the shop (frontend and backend) can be done either with FIDO2 or with password. Both work independently of each other.

Logging in with password does not differ from the shop standard and remains as a fallback option.

To use the registered FIDO2 keys, simply leave the password field blank when logging in. As soon as at least one character is entered in the password field, a login with password is assumed. If the password field is left blank, the system checks for the existence of a key registration and, if successful, requests the corresponding device. If there is no registration, a login with password is also assumed.

The keys can be easily managed in the My Account area of the frontend and also in the customer account in the backend. The administration includes the registration of new keys (multiple keys per account are possible and recommended). A free text name can be assigned to each key. Furthermore, all registered keys are displayed with their names. Registered keys can also be deleted there.

Since a password is no longer required with a FIDO2-based login, the backup password can also be more complex than passwords suitable for everyday use.

## Configuration

Options used:

- allows Platform and Cross-Platform Authenticators
- does not define interface restrictions (USB, NFC, ...)
- User verification recommended, but not required
  - Not all browsers can e.g. request the PIN from Cross-Platform Authenticators, with User Verification these browsers are excluded from use
- does not request attestation
- no request for user data stored on the device
- Timeout: 60 seconds

All other options can be freely adapted to individual requirements by overloading.

## Changelog

See [CHANGELOG](CHANGELOG.md) for further informations.

## Contributing

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue. Don't forget to give the project a star! Thanks again!

- Fork the Project
- Create your Feature Branch (git checkout -b feature/AmazingFeature)
- Commit your Changes (git commit -m 'Add some AmazingFeature')
- Push to the Branch (git push origin feature/AmazingFeature)
- Open a Pull Request

## Licence
(status: 2022-10-25)

Distributed under the GPLv3 license.

```
Copyright (c) D3 Data Development (Inh. Thomas Dartsch)

This software is distributed under the GNU GENERAL PUBLIC LICENSE version 3.
```

For full copyright and licensing information, please see the [LICENSE](LICENSE.md) file distributed with this source code.

## Further licences and terms of use

### ArrayBuffer to Base64 converting JavaScript [MIT]
(https://gist.github.com/jonleighton/958841 - status: 2022-11-04)

```
MIT LICENSE
Copyright 2011 Jon Leighton
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```
