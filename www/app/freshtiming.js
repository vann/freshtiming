/* global $, oAuth */

define (function () {

    var
        init,
        doFreshbooks;
    
    /* 
        javascript route of oauthing to freshbooks. but how do we protect the client_secret? also: not working.
        using php example instead: see auth.php
    */
    doFreshbooks = function(require, $, OAuth) {
        
        console.log('in doFreshbooks');		
        
        // see: https://my.freshbooks.com/#/developer
        // see: https://github.com/zalando/oauth2-client-js
        
        // auth url:
        // https://my.freshbooks.com/service/auth/oauth/authorize?client_id=c0386f435a5d1b85dca1c34575ef61cf747f3525c690f87156271fd4f4c9b19e&response_type=code&redirect_uri=https://github.com/vann/freshtiming
        
        var freshbooks = new OAuth.Provider({
            id: 'freshbooks',   // required
            authorization_url: 'https://api.freshbooks.com/auth/oauth/token' // required
        });
        
       // console.log('freshbooks oauth provider is: ' + JSON.stringify(freshbooks));
       
       // how to add these HTTP headers to request?:
                  
           // 'Api-Version': 'alpha',
        //    'Content-Type': 'application/json',
        
        // Create a new request
        var request = new OAuth.Request({
            client_id: 'c0386f435a5d1b85dca1c34575ef61cf747f3525c690f87156271fd4f4c9b19e',  // required
            client_secret: '4469b1415ea07b31e8f28d12d909852d598021c03972814444556af31d394ead',
            redirect_uri: 'https://freshtiming.combicombi.com/auth.php',
            grant_type: 'authorization_code'
           // 'Api-Version': 'alpha',
        //    'Content-Type': 'application/json',
        //    Authorization: 'Bearer'
            
        });
        
        console.log('freshbooks oauth request is: ' + JSON.stringify(request));
        
        // Give it to the provider
        var uri = freshbooks.requestToken(request);
        
        console.log('freshbooks oauth uri token is: ' + JSON.stringify(uri));
        
        // Later we need to check if the response was expected so save the request
        freshbooks.remember(request);
        
        
        //window.open(uri, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
        
    };
    
    init = function(require, $, OAut) {
        
        $('#fbauth').on("click", function (event) {
            console.log('got click');
        
            doFreshbooks();
            
            return false;
        });
        
        // Do the redirect:
        //window.location.href = uri;    
    };
    
    return {
        init: init
    };
})

