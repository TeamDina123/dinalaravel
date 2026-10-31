# Apple transaction trust anchor

AppleRootCA-G3.pem is the public Apple Root CA G3 certificate downloaded from
https://www.apple.com/certificateauthority/AppleRootCA-G3.cer and converted from DER to PEM.
It contains no private key. Do not replace it with a certificate received in a purchase request.

StoreKit 2 transactions are checked with OpenSSL against this root, including
certificate validity, the App Store signing extension OIDs, ES256 signature,
bundle ID, and environment. Verification uses current certificate validity;
expired signing certificates require a fresh transaction from Restore purchases.
This verifier does not perform online OCSP checks.

The default bundle ID matches the iOS project. Override APPLE_IAP_BUNDLE_ID only
if deploying a different app. Legacy receipts still use APPLE_IAP_SHARED_SECRET.

Deploy the PHP changes and this certificate together and refresh Laravel's
configuration cache if enabled. Existing missing purchases are not fabricated or
automatically imported: signed-in iOS clients can use Restore purchases to submit
their transactions again. This does not charge them again. The current StoreKit 2
mobile build already sends the signed transaction in receipt_data.
