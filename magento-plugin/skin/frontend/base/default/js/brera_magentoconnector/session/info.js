Event.observe(document, 'dom:loaded', function() {
    if (typeof breraTransport != "undefined") {
        Mage.Cookies.set('breraTransport', JSON.stringify(breraTransport));
    }
});
