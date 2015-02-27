Event.observe(document, 'dom:loaded', function() {
    if (typeof localStorage != "undefined" && typeof breraTransport != "undefined") {
        localStorage.setItem('breraTransport', JSON.stringify(breraTransport));
    }
});
