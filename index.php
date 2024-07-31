<?php

// Request a client token
require_once( '../config.php' );

$curl = curl_init( 'https://api.sandbox.paypal.com/v1/oauth2/token' );
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $curl, CURLOPT_POST, true );
curl_setopt( $curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials&response_type=client_token&intent=sdk_init&domains[]=onrender.com' );
curl_setopt( $curl, CURLOPT_USERPWD, CLIENT_ID . ':' . SECRET );

$response = curl_exec( $curl );

if( false === $response ) {
    die( 'curl_exec() failed: ' . curl_error( $curl ) );
}

$json = json_decode( $response );
if( NULL === $json ) {
    die( 'json_decode() failed: ' . json_last_error_msg() );
}

if( !is_object( $json ) ) {
    die( 'Response from server was not a JSON object' );
}

if( !property_exists( $json, 'access_token' ) ) {
    die( 'No access_token present in response from server' );
}

$token = trim( $json->access_token );
$cmid = uniqid();

echo "$token"; 
echo "/n";
echo "$cmid";

//echo '<script>console.log("'$token . "\n" . $cmid'"); </script>'; 

?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Fastlane Demo</title>
        <link rel="preload" href="https://www.paypalobjects.com/fastlane-v1/assets/fastlane-with-tooltip_en_sm_light.0808.svg" as="image" type="image/avif" /> <!-- optional -->
        <link rel="preload" href="https://www.paypalobjects.com/fastlane-v1/assets/fastlane_en_sm_light.0296.svg" as="image" type="image/avif" />
        <style type="text/css">
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

         #fastlaneShippingAddressDiv,#shippingAddressEntryDiv,#fastlanePaymentDiv {
             margin-top: 20px;
         }

         #fastlanePaymentDiv {
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
        <form action="submit.php" method="post" type="application/x-www-form-urlencoded" id="paymentForm">
            <div>
                <input type="text" size="80" placeholder="Enter email address" id="email">
                <button id="lookupEmailButton" disabled="disabled">Continue</button>
            </div>

            <!-- Container for the Fastlane logo -->
            <div style="display: flex;">
                <div id="watermarkContainer" style="display: flex; width: 607px; justify-content: flex-end;">
                    <img src="https://www.paypalobjects.com/fastlane-v1/assets/fastlane-with-tooltip_en_sm_light.0808.svg" />
                </div>
            </div>

            <!-- Container that displays the shipping address that was pulled in from Fastlane.  If Fastlane wasn't used (or if Fastlane didn't return an address),
                 we hide it and show the form fields instead. -->
            <div id="fastlaneShippingAddressDiv" style="display: none;">
                <div style="font-weight: bold;">Shipping address:</div>
                <div id="shippingNameDiv"></div>
                <div id="shippingAddressLine1Div"></div>
                <div id="shippingAddressLine2Div"></div>
                <div>
                    <span id="shippingAdminArea2Span"></span>, <span id="shippingAdminArea1Span"></span>&nbsp;&nbsp;<span id="shippingPostalCodeSpan"></span>
                </div>
                <div id="shippingCountryCodeDiv"></div>
                <div id="shippingPhoneNumberDiv"></div>
                <div id="shippingWatermarkContainer"></div>
                <div>
                    <button id="changeShippingButton">Change</button>
                </div>
            </div>

            <!-- Container with form fields for the user to enter their address. -->
            <div id="shippingAddressEntryDiv" style="display: none;">
                <div style="font-weight: bold;">Shipping address:</div>
                <div>
                    <input type="text" name="firstName" placeholder="First name" id="firstNameField" style="width: 294px;">
                    <input type="text" name="lastName" placeholder="Last name" id="lastNameField" style="width: 294px;">
                </div>
                <div>
                    <input type="text" name="addressLine1" placeholder="Address line 1" id="addressLine1Field" style="width: 602px;">
                </div>
                <div>
                    <input type="text" name="addressLine2" placeholder="Address line 2" id="addressLine2Field" style="width: 602px;">
                </div>
                <div>
                    <input type="text" name="adminArea2" placeholder="City" id="adminArea2Field" style="width: 338px;">
                    <input type="text" name="adminArea1" placeholder="State/Province" id="adminArea1Field" style="width: 44px;">
                    <input type="text" name="postalCode" placeholder="ZIP/Postal Code" id="postalCodeField" style="width: 192px;">
                </div>
                <div>
                    <input type="text" name="countryCode" placeholder="Country code" id="countryCodeField" style="width: 150px;">
                </div>
                <div>
                    <input type="text" name="phoneCountryCode" placeholder="Phone country code" id="phoneCountryCodeField" style="width: 50px;">
                    <input type="text" name="phone" placeholder="Phone number" id="phoneField" style="width: 150px;">
                </div>
            </div>

            <!-- Container where Fastlane component will be rendered -->
            <div id="fastlanePaymentDiv" style="display: none;">
                <div style="font-weight: bold;">Payment details:</div>
                <div id="paymentContainer"></div>
                <div>
                    <button id="submitButton">Submit Payment</button>
                </div>
            </div>

            <input type="hidden" name="paymentToken" value="" id="paymentTokenField">
            <input type="hidden" name="cmid" value="<?= $cmid ?>">
        </form>
        <script
            src="https://www.paypal.com/sdk/js?client-id=<?= urlencode( CLIENT_ID ) ?>&components=buttons,fastlane"
            data-sdk-client-token="<?= addslashes( $token ) ?>"
            data-client-metadata-id="<?= $cmid ?>">
        </script>
        <script>
         function fillShippingAddress(addressData) {
             let name = addressData.name?.fullName || (addressData.name?.firstName && addressData.name?.lastName ? addressData.name.firstName + ' ' + addressData.name.lastName : '');
             let firstName = addressData.name?.firstName || '';
             let lastName = addressData.name?.lastName || '';
             let addressLine1 = addressData.address?.addressLine1 || '';
             let addressLine2 = addressData.address?.addressLine2 || '';
             let adminArea2 = addressData.address?.adminArea2 || '';
             let adminArea1 = addressData.address?.adminArea1 || '';
             let postalCode = addressData.address?.postalCode || '';
             let countryCode = addressData.address?.countryCode || '';
             let phoneCountryCode = addressData.phoneNumber?.countryCode || '';
             let phone = addressData.phoneNumber?.nationalNumber || '';
             let phoneCombined = phoneCountryCode ? phoneCountryCode + ' ' + phone : phone;
             
             // Fill in the fields in the Fastlane shipping address section
             document.querySelector('#shippingNameDiv').innerHTML = name;
             document.querySelector('#shippingAddressLine1Div').innerHTML = addressLine1;
             document.querySelector('#shippingAddressLine2Div').innerHTML = addressLine2;
             document.querySelector('#shippingAdminArea2Span').innerHTML = adminArea2;
             document.querySelector('#shippingAdminArea1Span').innerHTML = adminArea1;
             document.querySelector('#shippingPostalCodeSpan').innerHTML = postalCode;
             document.querySelector('#shippingCountryCodeDiv').innerHTML = countryCode;
             document.querySelector('#shippingPhoneNumberDiv').innerHTML = phoneCombined;
             
             // Fill in the form fields
             document.querySelector('#firstNameField').value = firstName;
             document.querySelector('#lastNameField').value = lastName;
             document.querySelector('#addressLine1Field').value = addressLine1;
             document.querySelector('#addressLine2Field').value = addressLine2;
             document.querySelector('#adminArea2Field').value = adminArea2;
             document.querySelector('#adminArea1Field').value = adminArea1;
             document.querySelector('#postalCodeField').value = postalCode;
             document.querySelector('#countryCodeField').value = countryCode;
             document.querySelector('#phoneCountryCodeField').value = phoneCountryCode;
             document.querySelector('#phoneField').value = phone;
         }
         
         window.addEventListener('load', () => {
             async function fastlaneSetup() {
                 let fastlanePaymentComponent;

                 const shippingAddressOptions = {
                     allowedLocations: [
                         // Can be a list of country codes (as the bare ISO 3166-1 alpha-2 country code)
                         // or country code+region combinations (separated by a colon; e.g., "US:FL")
                         // where the merchant will ship to.  Shipping address from all other regions
                         // will be blocked.  An empty array means that all regions are allowed.
                     ]
                 };
                 
                 const cardOptions = {
                     allowedBrands: [
                         'VISA',
                         'MASTER_CARD',
                         'AMEX',
                         // 'DINERS',
                         'DISCOVER',
                         'JCB',
                         'CHINA_UNION_PAY',
                         'MAESTRO' //,
                         // 'ELO',
                         // 'MIR',
                         // 'HIPER',
                         // 'HIPERCARD'
                     ]
                 };
                 
                 const styleOptions = {
                     root: {
                         backgroundColor: '#ffffff', // All colors may be any valid CSS color, but no transparency is allowed
                         errorColor: '#d9360b',
                         fontFamily: 'PayPal-Open',  // Must be one of 'Arial', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Times New Roman', 'Georgia', 'Garamond', 'Courier New', or 'Brush Script MT'
                         textColorBase: '#010b0d',
                         fontSizeBase: '16px',       // Must be between 13px and 24px
                         padding: '4px',             // Must be between 0px and 10px
                         primaryColor: '#0057ff'
                     },
                     input: {
                         backgroundColor: '#dadddd',
                         borderRadius: '4px',        // Must be between 0px and 32px
                         borderColor: '#dadddd',
                         borderWidth: '1px',         // Must be between 1px and 5px
                         textColorBase: '#010b0d',
                         focusBorderColor: '#0057ff'
                     }
                 };
                 
                 const fastlane = await window.paypal.Fastlane({
                     shippingAddressOptions,
                     cardOptions,
                     styleOptions
                 });
                 
                 window.localStorage.setItem('fastlaneEnv', 'sandbox');
                 
                 fastlane.setLocale('en_us');
                 
                 const fastlaneWatermark = (await fastlane.FastlaneWatermarkComponent({
                     includeAdditionalInfo: true // if false, use https://www.paypalobjects.com/fastlane-v1/assets/fastlane_en_sm_light.0296.svg for the placeholder image instead
                 }));
                 
                 fastlaneWatermark.render('#watermarkContainer');

                 const fastlaneWatermarkNoTooltip = (await fastlane.FastlaneWatermarkComponent({
                     includeAdditionalInfo: false
                 }));

                 fastlaneWatermarkNoTooltip.render('#shippingWatermarkContainer');

                 // Event handler for the Continue button
                 document.querySelector('#lookupEmailButton').addEventListener('click', evt => {
                     // We have to prevent the event from bubbling up because it's a button in a form and it will try to submit the form
                     evt.preventDefault();

                     (async () => {
                         let nameData = false;
                         let shippingAddressData = false;
                         let cardData = false;
                         
                         // Check to make sure the user actually entered an email address
                         if(!document.querySelector('#email').value.trim().length) {
                             window.alert('You must enter an email address.');
                             return;
                         }
                         
                         // Disable all the buttons on the page while doing the lookup
                         let disabledAttr = document.createAttribute('disabled');
                         disabledAttr.value = 'disabled';
                         [ 'lookupEmailButton', 'changeShippingButton', 'submitButton' ].forEach(e => document.querySelector('#' + e).attributes.setNamedItem(disabledAttr.cloneNode()));

                         // Get a customer context ID for the email address
                         const { customerContextId } = await fastlane.identity.lookupCustomerByEmail(document.querySelector('#email').value);
                         
                         let renderFastlaneMemberExperience = false;

                         // If there is a Fastlane customer under the given email address, customerContextId will be a string containing
                         // an ID to be passed to  fastlane.identity.triggerAuthenticationflow().  Otherwise, customerContextId will be
                         // set to an empty string.
                         if(customerContextId) {
                             const { authenticationState, profileData } = await fastlane.identity.triggerAuthenticationFlow(customerContextId);
                             
                             if(authenticationState === 'succeeded') {
                                 // Customer authenticated successfully
                                 renderFastlaneMemberExperience = true;
                                 
                                 if(profileData.name) {
                                     nameData = profileData.name;
                                 }
                                 
                                 if(profileData.shippingAddress) {
                                     shippingAddressData = profileData.shippingAddress;
                                 }
                                 
                                 if(profileData.card) {
                                     cardData = profileData.cardData;
                                 }
                             } else {
                                 // Customer failed or cancelled authentication
                             }
                         } else {
                             // No profile found for this email
                         }
                         
                         if(renderFastlaneMemberExperience) {
                             // Check all the fields -- sometimes they come back empty! (e.g., if the buyer doesn't have any cards or shipping addresses in his account)
                             
                             // Check the shipping address
                             if(shippingAddressData) {
                                 // Fill in our form fields/the Fastlane shipping display with the default shipping address from the member's profile
                                 fillShippingAddress(shippingAddressData);
                                 
                                 // Hide the shipping address entry fields
                                 document.querySelector('#shippingAddressEntryDiv').style.display = 'none';
                                 
                                 // Show the Fastlane shipping address section
                                 document.querySelector('#fastlaneShippingAddressDiv').style.removeProperty('display');
                                 
                                 // Grab the shipping address so that we can pass it to the Fastlane payment component
                                 let firstName = shippingAddressData.name?.firstName || '';
                                 let lastName = shippingAddressData.name?.lastName || '';
                                 let addressLine1 = shippingAddressData.address?.addressLine1 || '';
                                 let addressLine2 = shippingAddressData.address?.addressLine2 || '';
                                 let adminArea2 = shippingAddressData.address?.adminArea2 || '';
                                 let adminArea1 = shippingAddressData.address?.adminArea1 || '';
                                 let postalCode = shippingAddressData.address?.postalCode || '';
                                 let countryCode = shippingAddressData.address?.countryCode || '';
                                 let phone = shippingAddressData.phoneNumber?.nationalNumber ? (shippingAddressData.phoneNumber?.countryCode ? shippingAddressData.phoneNumber.countryCode : '') + shippingAddressData.phoneNumber?.nationalNumber : '';

                                 // Render the Fastlane payment component
                                 fastlanePaymentComponent = await fastlane.FastlanePaymentComponent({
                                     shippingAddress: {
                                         name: { firstName, lastName },
                                         address: { addressLine1, addressLine2, adminArea1, adminArea2, postalCode, countryCode, phone }
                                     }
                                 });
                                 
                                 fastlanePaymentComponent.render('#paymentContainer');
                             } else {
                                 // Clear out the shipping address
                                 fillShippingAddress({});
                                 
                                 // Hide the Fastlane shipping address section
                                 document.querySelector('#fastlaneShippingAddressDiv').style.display = 'none';
                                 
                                 // Show the shipping address entry fields
                                 document.querySelector('#shippingAddressEntryDiv').style.removeProperty('display');
                                 
                                 // Render the Fastlane payment component
                                 fastlanePaymentComponent = await fastlane.FastlanePaymentComponent();
                                 fastlanePaymentComponent.render('#paymentContainer');
                             }
                         } else {
                             // Clear out the shipping address
                             fillShippingAddress({});
                             
                             // Hide the Fastlane shipping address section
                             document.querySelector('#fastlaneShippingAddressDiv').style.display = 'none';
                             
                             // Show the shipping address entry fields
                             document.querySelector('#shippingAddressEntryDiv').style.removeProperty('display');
                             
                             // Render the Fastlane payment component
                             fastlanePaymentComponent = await fastlane.FastlanePaymentComponent();
                             fastlanePaymentComponent.render('#paymentContainer');
                         }
                         
                         // Show the payment div
                         document.querySelector('#fastlanePaymentDiv').style.removeProperty('display');
                         
                         // Re-enable the buttons
                         [ 'lookupEmailButton', 'changeShippingButton', 'submitButton' ].forEach(e => document.querySelector('#' + e).attributes.removeNamedItem('disabled'));
                     })();
                 });

                 // Enable the Continue button
                 document.querySelector('#lookupEmailButton').attributes.removeNamedItem('disabled');

                 // Event handler for the Change button in the Fastlane shipping address section
                 document.querySelector('#changeShippingButton').addEventListener('click', evt => {
                     // We have to prevent the event from bubbling up because it's a button in a form and it will try to submit the form
                     evt.preventDefault();
                     
                     // Update the shipping address
                     (async () => {
                         const { selectedAddress, selectionChanged } = await fastlane.profile.showShippingAddressSelector();
                         
                         if(selectionChanged) {
                             // Update the address being displayed
                             fillShippingAddress(selectedAddress);
                         }
                     })();
                 });

                 // Event handler for the Submit Payment button
                 document.querySelector('#submitButton').addEventListener('click', evt => {
                     // We have to prevent the event from bubbling up because it's a form button and it will try to submit the form
                     evt.preventDefault();

                     // Disable all the buttons on the page
                     let disabledAttr = document.createAttribute('disabled');
                     disabledAttr.value = 'disabled';
                     [ 'lookupEmailButton', 'changeShippingButton', 'submitButton' ].forEach(e => document.querySelector('#' + e).attributes.setNamedItem(disabledAttr.cloneNode()));

                     // Get the payment token
                     (async () => {
                         const { id } = await fastlanePaymentComponent.getPaymentToken();

                         // Insert the payment token into our hidden form field
                         document.querySelector('#paymentTokenField').value = id;

                         // Submit the form
                         document.querySelector('#paymentForm').submit();
                     })();
                 });

             }
             
             fastlaneSetup();
         });
        </script>
    </body>
</html>
