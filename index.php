<!DOCTYPE html>
<!--
Copyright (c) 2011, salesforce.com, inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided
that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the
 following disclaimer.

Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
the following disclaimer in the documentation and/or other materials provided with the distribution.

Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
-->
<!--
Sample HTML page showing use of Force.com JavaScript REST Toolkit from
an HTML5 mobile app using jQuery Mobile
-->
<html>
<head>
<title>Contacts</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<!--
    For development, you may want to load jQuery/jQuery Mobile from their CDN. 
-->
<link rel="stylesheet" href="jquery.mobile-1.3.0.min.css" />
<script type="text/javascript" src="jquery.min.js"></script>
<!--
From jQuery-swip - http://code.google.com/p/jquery-swip/source/browse/trunk/jquery.popupWindow.js 
-->
<script type="text/javascript" src="jquery.popup.js"></script>
<script type="text/javascript" src="jquerymobile.js"></script>
<script type="text/javascript" src="cordova.force.js"></script>
<script type="text/javascript" src="backbone/underscore-1.4.4.min.js"></script>
<script type="text/javascript" src="force.entity.js"></script>
<script type="text/javascript" src="forcetk.mobilesdk.js"></script>
<script type="text/javascript" src="SObjectData.js"></script>
<script type="text/javascript" src="mobileapp.js"></script>
<script type="text/javascript">
// OAuth Configuration
var loginUrl    = 'https://login.salesforce.com/';
var clientId    = '<?=$_ENV['client_id']?>'; //demo only
var redirectUri = '<?=$_ENV['app_url']?>/index.php';
var proxyUrl    = '<?=$_ENV['app_url']?>/proxy.php?mode=native';

// We'll get an instance of the REST API client in a callback after we do 
// OAuth
var client = new forcetk.Client(clientId, loginUrl, proxyUrl);

// We use $j rather than $ for jQuery
if (window.$j === undefined) {
    $j = $;
}

$j(document).ready(function() {
	console.log('DOCUMENT READY '+window.location.href);
	if(client.sessionId == null) {
		var oauthResponse = {};
		if (window.location.hash && window.location.href.indexOf('access_token') > 0) {
			var message = window.location.hash.substr(1);
			var nvps = message.split('&');
			for (var nvp in nvps) {
			    var parts = nvps[nvp].split('=');
				oauthResponse[parts[0]] = unescape(parts[1]);
			}
			console.log('init app');
			if(oauthResponse['access_token']) {sessionCallback(oauthResponse);}
		} else {
			url = getAuthorizeUrl(loginUrl, clientId, redirectUri);
			window.location.href = url;
		}
	}
});

function getAuthorizeUrl(loginUrl, clientId, redirectUri){
    return loginUrl+'services/oauth2/authorize?display=touch'
        +'&response_type=token&client_id='+escape(clientId)
        +'&redirect_uri='+escape(redirectUri);
}

function sessionCallback(oauthResponse) {
    if (typeof oauthResponse === 'undefined'
        || typeof oauthResponse['access_token'] === 'undefined') {
        errorCallback({
            status: 0, 
            statusText: 'Unauthorized', 
            responseText: 'No OAuth response'
        });
    } else {
        client.setSessionToken(oauthResponse.access_token, null, oauthResponse.instance_url);

		addClickListeners();
		
		$j.mobile.changePage( "#mainpage" , { reverse: false, changeHash: true } );
	    $j.mobile.loading( "show", { text: 'Loading', textVisible: true } );
	    getRecords(function(){
	        $j.mobile.loading( "hide" );
			
		}); 
    }
}
  </script>
</head>

<body>
	<div data-role="page" data-theme="b" id="loginpage">

	    <div data-role="header">
	        <h1>Logging in...</h1>
	    </div>
	    <div data-role="footer">
	        <h4>Force.com</h4>
	    </div>
	</div>
	<div data-role="page" data-theme="b" id="mainpage">
	    <div data-role="header">
		    <h1>Contacts</h1>
	    </div>
	    <div data-role="content">
	        <form>
	            <button data-role="button" id="newbtn">New</button>
	        </form>
	        <ul id="list" data-inset="true" data-role="listview" 
			  data-theme="c" data-dividertheme="b">
	        </ul>
	    </div>
	    <div data-role="footer">
	        <h4>Force.com</h4>
	    </div>
	</div>
	<div data-role="page" data-theme="b" id="detailpage">
	    <div data-role="header">
	    <a href='#mainpage' id="back" class='ui-btn-left' data-icon='arrow-l'>Back</a>
		   <h1>Contact Detail</h1>
	    </div>
	    <div data-role="content">
	        <table>
	            <tr><td>First Name:</td><td id="FirstName"></td></tr>
	            <tr><td>Last Name:</td><td id="LastName"></td></tr>
				<tr><td>Email:</td><td id="Email"></td></tr>
	        </table>
	        <form name="detail" id="detail">
	            <input type="hidden" name="Id" id="Id" />
	            <button data-role="button" id="editbtn">Edit</button>
	            <button data-role="button" id="deletebtn" data-icon="delete" 
				  data-theme="e">Delete</button>
	        </form>
	    </div>
	    <div data-role="footer">
	        <h4>Force.com</h4>
	    </div>
	</div>
	<div data-role="page" data-theme="b" id="editpage">
	    <div data-role="header">
	    <a href='#mainpage' id="back" class='ui-btn-left' data-icon='arrow-l'>Back</a>
	    	<h1 id="formheader">New Contact</h1>
	    </div>
	    <div data-role="content">
	        <form name="contact" id="form">
	            <input type="hidden" name="Id" id="Id" />
	            <table>
	                <tr><td>First Name:</td><td ><input name="FirstName" id="FirstName" 
					  data-theme="c"/></td></tr>
		            <tr><td>Last Name:</td><td ><input name="LastName" id="LastName" 
						  data-theme="c"/></td></tr>
					<tr>
						<td>Email:</td>
						<td><input name="Email" id="Email" 
						  data-theme="c"/></td>
					</tr>
	            </table>
	            <button data-role="button" id="actionbtn">Action</button>
	        </form>
	    </div>
	    <div data-role="footer">
	        <h4>Force.com</h4>
	    </div>
	</div>
</body>
</html>
