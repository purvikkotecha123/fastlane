<?php

require_once( '../config.php' );

// Make sure all our fields are present
$errors = [];

$keysToCheck = [
    'firstName' => 'First name',
    'lastName' => 'Last name',
    'addressLine1' => 'Address line 1',
    'adminArea2' => 'City',
    'adminArea1' => 'State',
    'postalCode' => 'ZIP/Postal Code',
    'countryCode' => 'Country code',
    'phone' => 'Phone number',
    'paymentToken' => 'Payment token',
    'cmid' => 'Client metadata ID'
];

foreach( $keysToCheck as $key => $errmsg ) {
    if( !array_key_exists( $key, $_POST ) || !strlen( trim( $_POST[ $key ] ) ) ) {
        $errors[] = $errmsg . ' was not provided.';
    } else {
        $$key = trim( $_POST[ $key ] );
    }
}

$accessToken = false;
$captureId = false;

if( !count( $errors ) ) {
    // Get an access token
    $json = false;
    $curl = curl_init( 'https://api.sandbox.paypal.com/v1/oauth2/token' );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials' );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_USERPWD, CLIENT_ID . ':' . SECRET );
    
    $response = curl_exec( $curl );
    if( false === $response ) {
        $errors[] = 'Tried to request an access token, but curl_exec() failed: ' . curl_error( $curl );
    } else if( NULL === ( $json = json_decode( $response ) ) ) {
        $errors[] = 'Tried to request an access token, but json_decode() failed while trying to parse the response: ' . json_last_error_msg();
    } else if( !property_exists( $json, 'access_token' ) || !strlen( $accessToken = trim( $json->access_token ) ) ) {
        $errors[] = 'Tried to request an access token, but no access_token property could be found in the response';
    }

    curl_close( $curl );
}

if( !count( $errors ) ) {
    // Put the POST /v2/checkout/orders request together
    $request = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '24.99'
                ],
                'shipping' => [
                    'type' => 'SHIPPING',
                    'name' => [
                        'full_name' => $firstName . ' ' . $lastName,
                    ],
                    'address' => [
                        'address_line_1' => $addressLine1,
                        'admin_area_2' => $adminArea2,
                        'admin_area_1' => $adminArea1,
                        'postal_code' => $postalCode,
                        'country_code' => $countryCode
                    ],
                    'phone' => [
                        'national_number' => $phone
                    ]
                ]
            ]
        ],
        'payment_source' => [
            'card' => [
                'single_use_token' => $paymentToken
            ]
        ],
    ];
    
    if( array_key_exists( 'addressLine2', $_POST ) && strlen( trim( $_POST[ 'addressLine2' ] ) ) ) {
        $request[ 'shipping' ][ 'address' ][ 'address_line_2' ] = trim( $_POST[ 'addressLine2' ] );
    }

    if( array_key_exists( 'phoneCountryCode', $_POST ) && strlen( trim( $_POST[ 'phoneCountryCode' ] ) ) ) {
        $request[ 'shipping' ][ 'phone' ][ 'country_code' ] = trim( $_POST[ 'phoneCountryCode' ] );
    }

    $curl = curl_init( 'https://api.sandbox.paypal.com/v2/checkout/orders' );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $request ) );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        'Content-Type: application/json',
        "PayPal-Client-Metadata-Id: $cmid",
        'PayPal-Request-Id: ' . uniqid()
    ]);

    if( false === ( $response = curl_exec( $curl ) ) ) {
        $errors[] = 'Tried to process the order, but curl_exec() failed: ' . curl_error( $curl );
    } else if( NULL === ( $json = json_decode( $response ) ) ) {
        $errors[] = 'Tried to process the order, but json_decode() failed to parse the response: ' . json_last_error_msg();
    } else {
        if( property_exists( $json, 'purchase_units' ) && is_array( $json->purchase_units ) && count( $json->purchase_units ) ) {
            foreach( $json->purchase_units as $purchase_unit ) {
                if( property_exists( $purchase_unit, 'payments' ) && is_object( $purchase_unit->payments ) && property_exists( $purchase_unit->payments, 'captures' ) && is_array( $purchase_unit->payments->captures ) && count( $purchase_unit->payments->captures ) ) {
                    foreach( $purchase_unit->payments->captures as $capture ) {
                        if( property_exists( $capture, 'status' ) && is_string( $capture->status ) && ( $capture->status == 'PENDING' || $capture->status == 'COMPLETED' ) && property_exists( $capture, 'id' ) && is_string( $capture->id ) && strlen( trim( $capture->id ) ) ) {
                            $captureId = $capture->id;
                        }
                    }

                    if( !$captureId ) {
                        $errors[] = 'Unable to locate a successful capture in the response -- capture may have been declined';
                    }
                } else {
                    $errors[] = 'Tried to process the order, but no captures object could be located in the response';
                }
            }
        } else {
            $errors[] = 'Tried to process the order, but no purchase_units object could be located in the response';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Fastlane Demo</title>
        <style type="text/css">
         @font-face {
             font-family: PayPal-Open;
             font-style: normal;
             font-weight: 400;
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Regular.otf') format('opentype');
             /* IE9 Compat Modes */
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Regular.woff2') format('woff2'),
             /*Moderner Browsers*/ url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Regular.woff') format('woff');
             /* Modern Browsers */
             /* Fallback font for - MS Outlook older versions (2007,13, 16)*/
             mso-font-alt: 'Calibri';
         }

         /* Headline/Subheadline/Button text font-weight:500 */
         @font-face {
             font-family: PayPal-Open;
             font-style: normal;
             font-weight: 500;
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Medium.otf') format('opentype');
             /* IE9 Compat Modes */
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Medium.woff2') format('woff2'),
             /*Moderner Browsers*/ url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Medium.woff') format('woff');
             /* Modern Browsers */
             /* Fallback font for - MS Outlook older versions (2007,13, 16)*/
             mso-font-alt: 'Calibri';
         }

         /* Bold text - <b>, <strong> Bold equals to font-weight:700 */
         @font-face {
             font-family: PayPal-Open;
             font-style: normal;
             font-weight: 700;
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Bold.otf') format('opentype');
             /* IE9 Compat Modes */
             src: url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Bold.woff2') format('woff2'),
             /*Moderner Browsers*/ url('https://www.paypalobjects.com/digitalassets/c/system-triggered-email/n/layout/fonts/PayPalOpen/PayPalOpen-Bold.woff') format('woff');
             /* Modern Browsers */
             /* Fallback font for - MS Outlook older versions (2007,13, 16)*/
             mso-font-alt: 'Calibri';
         }

         body {
             font-family: PayPal-Open;
         }

         #email {
             width: 600px;
         }

         #shippingAddressEntryDiv input {
             margin-top: 4px;
         }

         #lookupEmailButton {
             width: 100px;
             height: 28px;             
         }

         #shippingAddressDiv,#shippingAddressEntryDiv,#paymentDiv {
             margin-top: 20px;
         }

         #paymentDiv {
             max-width: 600px;
         }

         input {
             font-size: 16px;
             border-radius: 4px;
             border: 1px solid #666;
             padding: 4px;
         }
        </style>
    </head>
    <body>
        <h1>Fastlane Demo</h1>
        <?php
if( count( $errors ) ) {
?><div>The following errors were encountered while trying to process the order:</div>
        <ul>
            <?php
    foreach( $errors as $error ) {
        ?><li><?= htmlentities( $error ) ?></li>
            <?php
    }
?></ul>
<?php
} else {
?><div>Transaction complete!  Transaction ID: <?= htmlentities( $captureId ) ?></div>
<?php
}
?>    </body>
</html>
