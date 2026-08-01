(function () {
  "use strict";

  // Static indicative rates, base currency = AED (the site's native currency).
  var RATES = {
    AED: 1,
    USD: 0.272,
    SAR: 1.02,
    QAR: 0.99,
    KWD: 0.0836,
    BHD: 0.1026,
    OMR: 0.1047,
  };

  var STORAGE_KEY = "site_currency";

  function getCurrency() {
    return localStorage.getItem(STORAGE_KEY) || "AED";
  }

  function setCurrency(code) {
    localStorage.setItem(STORAGE_KEY, code);
  }

  function applyCurrency() {
    var code = getCurrency();
    var rate = RATES[code] || 1;

    document.querySelectorAll(".js-price[data-aed]").forEach(function (el) {
      var aed = parseFloat(el.getAttribute("data-aed"));
      if (isNaN(aed)) return;
      el.textContent = code + " " + (aed * rate).toFixed(2);
    });

    document.querySelectorAll("#currency-picker").forEach(function (el) {
      el.value = code;
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    applyCurrency();

    document.querySelectorAll("#currency-picker").forEach(function (picker) {
      picker.addEventListener("change", function () {
        setCurrency(picker.value);
        applyCurrency();
      });
    });
  });

  window.applyCurrency = applyCurrency;
})();
