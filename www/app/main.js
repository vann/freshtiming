/* global requirejs */


define(['require', 'jquery', 'oauth2-client-js/dist/oauth2-client','app/freshtiming'],function (require, jquery, OAuth, freshtiming) {
    
    freshtiming.init(require, jquery, OAuth);
});

