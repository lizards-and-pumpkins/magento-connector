function setLizardsAndPumpkinsCookie() {
    if (typeof lizardsAndPumpkinsTransport != "undefined") {
        Mage.Cookies.set('lizardsAndPumpkinsTransport', JSON.stringify(lizardsAndPumpkinsTransport), null, '/');
    }
}

if (document.addEventListener) {
    document.addEventListener("DOMContentLoaded", function () {
        setLizardsAndPumpkinsCookie();
    }, false);
} else if (document.attachEvent) {
    document.attachEvent("onreadystatechange", function () {
        if ("complete" === document.readyState) {
            setLizardsAndPumpkinsCookie();
        }
    });
}
