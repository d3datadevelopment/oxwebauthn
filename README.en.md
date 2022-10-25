[![deutsche Version](https://logos.oxidmodule.com/de2_xs.svg)](README.md)
[![english version](https://logos.oxidmodule.com/en2_xs.svg)](README.en.md)

# D³ WebAuthn / FIDO2 Login for OXID eShop

With this module, the login in the OXID shop can be carried out with a hardware token instead of a password. 

This secures the login in the frontend and (if allowed for the user) also in the backend.

Security keys are devices that contain cryptographic keys. These can be used for two-factor authentication. The security key must support the standard "[WebAuthn](https://w3c.github.io/webauthn/#webauthn-authenticator)".

The key management is done in the admin area and in the user's "My Account".

## Table of content

- [Installation](#installation)
- [Usage](#usage)
- [Extensibility](#extensibility)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)
- [Further licences and terms of use](#further-licences-and-terms-of-use)

## Installation

This package requires an Composer installed OXID eShop as defined in [composer.json](composer.json).

Open a command line interface and navigate to the shop root directory (parent of source and vendor). Execute the following command. Adapt the paths to your environment.

```bash
php composer require d3/oxwebauthn:^1.0
``` 

If a reference to an unsuitable package `symfony/process` is shown, this must be changed. To do this, please add the switch `-W` to the above command (`... require -W ...`).

Activate the module in the admin area of the shop in "Extensions -> Modules".

## Usage

## Extensibility

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
