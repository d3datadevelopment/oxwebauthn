/* jshint esversion: 9 */
/* jslint bitwise: true */

if (!window.PublicKeyCredential) {
    document.getElementById('webauthn').error.value = 'd3NoPublicKeyCredentialSupportedError: aborting';
    document.getElementById('webauthn').submit();
}

const base64UrlDecode = (input) => {
    "use strict";
    input = input
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
        }
        input += new Array(5-pad).join('=');
    }

    return window.atob(input);
};

const prepareOptions = (publicKey) => {
    "use strict";
    //Convert challenge from Base64Url string to Uint8Array
    publicKey.challenge = Uint8Array.from(
        base64UrlDecode(publicKey.challenge),
        c => c.charCodeAt(0)
    );

    //Convert the user ID from Base64 string to Uint8Array
    if (publicKey.user !== undefined) {
        publicKey.user = {
            ...publicKey.user,
            id: Uint8Array.from(
                window.atob(publicKey.user.id),
                c => c.charCodeAt(0)
            ),
        };
    }

    //If excludeCredentials is defined, we convert all IDs to Uint8Array
    if (publicKey.excludeCredentials !== undefined) {
        publicKey.excludeCredentials = publicKey.excludeCredentials.map(
            data => {
                return {
                    ...data,
                    id: Uint8Array.from(
                        base64UrlDecode(data.id),
                        c => c.charCodeAt(0)
                    ),
                };
            }
        );
    }

    if (publicKey.allowCredentials !== undefined) {
        publicKey.allowCredentials = publicKey.allowCredentials.map(
            data => {
                return {
                    ...data,
                    id: Uint8Array.from(
                        base64UrlDecode(data.id),
                        c => c.charCodeAt(0)
                    ),
                };
            }
        );
    }

    return publicKey;
};

/** https://gist.github.com/jonleighton/958841 **/
function base64ArrayBuffer(arrayBuffer) {
    "use strict";
    let base64    = '';
    let encodings = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    let bytes         = new Uint8Array(arrayBuffer);
    let byteLength    = bytes.byteLength;
    let byteRemainder = byteLength % 3;
    let mainLength    = byteLength - byteRemainder;

    let a, b, c, d;
    let chunk;

    // Main loop deals with bytes in chunks of 3
    for (let i = 0; i < mainLength; i = i + 3) {
        // Combine the three bytes into a single integer
        chunk = (bytes[i] << 16) | (bytes[i + 1] << 8) | bytes[i + 2];

        // Use bitmasks to extract 6-bit segments from the triplet
        a = (chunk & 16515072) >> 18; // 16515072 = (2^6 - 1) << 18
        b = (chunk & 258048)   >> 12; // 258048   = (2^6 - 1) << 12
        c = (chunk & 4032)     >>  6; // 4032     = (2^6 - 1) << 6
        d = chunk & 63;               // 63       = 2^6 - 1

        // Convert the raw binary segments to the appropriate ASCII encoding
        base64 += encodings[a] + encodings[b] + encodings[c] + encodings[d];
    }

    // Deal with the remaining bytes and padding
    if (byteRemainder === 1) {
        chunk = bytes[mainLength];

        a = (chunk & 252) >> 2; // 252 = (2^6 - 1) << 2

        // Set the 4 least significant bits to zero
        b = (chunk & 3)   << 4; // 3   = 2^2 - 1

        base64 += encodings[a] + encodings[b] + '==';
    } else if (byteRemainder === 2) {
        chunk = (bytes[mainLength] << 8) | bytes[mainLength + 1];

        a = (chunk & 64512) >> 10; // 64512 = (2^6 - 1) << 10
        b = (chunk & 1008)  >>  4; // 1008  = (2^6 - 1) << 4

        // Set the 2 least significant bits to zero
        c = (chunk & 15)    <<  2; // 15    = 2^4 - 1

        base64 += encodings[a] + encodings[b] + encodings[c] + '=';
    }

    return base64;
}

const createCredentials = (publicKey) => {
    "use strict";

    prepareOptions(publicKey);

    navigator.credentials.create({publicKey: publicKey})
        .then(function (newCredentialInfo) {
            // Send new credential info to server for verification and registration.
            let cred = {
                id: newCredentialInfo.id,
                rawId: base64ArrayBuffer(newCredentialInfo.rawId),
                response: {
                    clientDataJSON: base64ArrayBuffer(newCredentialInfo.response.clientDataJSON),
                    attestationObject: base64ArrayBuffer(newCredentialInfo.response.attestationObject)
                },
                type: newCredentialInfo.type
            };

            document.getElementById('webauthn').credential.value = JSON.stringify(cred);
            document.getElementById('webauthn').submit();
        }).catch(function (err) {
            document.getElementById('webauthn').error.value = err;
            document.getElementById('webauthn').submit();
        });
};

const requestCredentials = (publicKey) => {
    "use strict";

    prepareOptions(publicKey);

    navigator.credentials.get({publicKey: publicKey})
        .then(function (authenticateInfo) {
            // Send authenticate info to server for verification.
            let cred = {
                id: authenticateInfo.id,
                rawId: base64ArrayBuffer(authenticateInfo.rawId),
                response: {
                    authenticatorData: base64ArrayBuffer(authenticateInfo.response.authenticatorData),
                    signature: base64ArrayBuffer(authenticateInfo.response.signature),
                    userHandle: authenticateInfo.response.userHandle,
                    clientDataJSON: base64ArrayBuffer(authenticateInfo.response.clientDataJSON)
                },
                type: authenticateInfo.type
            };
            document.getElementById('webauthn').credential.value = JSON.stringify(cred);
            document.getElementById('webauthn').submit();
        }).catch(function (err) {
            document.getElementById('webauthn').error.value = err;
            document.getElementById('webauthn').submit();
        });
};

