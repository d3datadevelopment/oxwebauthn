[{if $webauthn_publickey_register}]
    [{capture name="webauthn_register"}]
        function arrayToBase64String(a) {
            return btoa(String.fromCharCode(...a));
        }

        function base64url2base64(input) {
            input = input
                .replace(/=/g, "")
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const pad = input.length % 4;
            if(pad) {
                if(pad === 1) {
                    throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
                }
                input += new Array(5-pad).join('=');
            }

            return input;
        }

        function authnregister() {
            let publicKey = [{$webauthn_publickey_register}];

            publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), function(c){return c.charCodeAt(0);});
            publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), function(c){return c.charCodeAt(0);});
            if (publicKey.excludeCredentials) {
                publicKey.excludeCredentials = publicKey.excludeCredentials.map(function(data) {
                    data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), function(c){return c.charCodeAt(0);});
                    return data;
                });
            }

            navigator.credentials.create({ 'publicKey': publicKey })
                .then(function(data){
                    let publicKeyCredential = {
                        id: data.id,
                        type: data.type,
                        rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                        response: {
                            clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                            attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject))
                        }
                    };
                window.location = 'index.php?cl=start&fnc=checkregister&authn='+btoa(JSON.stringify(publicKeyCredential));
                }).catch(function(error){
                    //alert('Open your browser console!');
                    console.log('FAIL', error);
                }
            );
        }
    [{/capture}]
    [{oxscript add=$smarty.capture.webauthn_register}]
    <button onclick="authnregister();">Fido2 Register</button>

    [{capture name="webauthn_login"}]
        function authnlogin() {
            let publicKey = [{$webauthn_publickey_login}];

            publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), function(c){return c.charCodeAt(0);});
            if (publicKey.allowCredentials) {
                publicKey.allowCredentials = publicKey.allowCredentials.map(function(data) {
                    data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), function(c){return c.charCodeAt(0);});
                    return data;
                });
            }

            navigator.credentials.get({ 'publicKey': publicKey })
                .then(function(data){
                    let publicKeyCredential = {
                        id: data.id,
                        type: data.type,
                        rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                        response: {
                            authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
                            clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                            signature: arrayToBase64String(new Uint8Array(data.response.signature)),
                            userHandle: data.response.userHandle ? arrayToBase64String(new Uint8Array(data.response.userHandle)) : null
                        }
                    };
                    window.location = 'index.php?cl=start&fnc=checklogin&authn='+btoa(JSON.stringify(publicKeyCredential));
                })
                .catch(function(error){
                    // alert('Open your browser console!');
                    console.log('FAIL', error);
                });
        }
    [{/capture}]
    [{oxscript add=$smarty.capture.webauthn_login}]
    <button onclick="authnlogin();">Fido2 Login</button>
[{/if}]

[{$smarty.block.parent}]