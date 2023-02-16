# Wish list for future releases

- a more intuitive login process (instead of simply having to leave the password field blank)
- forcing the user to use Webauthn
- General avoidance of passwords, login exclusively with FIDO2
  - However, a restore strategy is required in the event that a key is no longer available.
  - Alternatively, a random password unknown to the customer can be set, which is changed each time the customer logs on via Webauthn.
- Implementation of resident keys for logging in completely without user input (no user name required any more)