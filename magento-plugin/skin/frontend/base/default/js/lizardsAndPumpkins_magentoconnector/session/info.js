Event.observe(document, 'dom:loaded', function () {
    if (typeof lizardsAndPumpkinsTransport != "undefined") {
        Mage.Cookies.set('lizardsAndPumpkinsTransport', JSON.stringify(lizardsAndPumpkinsTransport));
    }
});
