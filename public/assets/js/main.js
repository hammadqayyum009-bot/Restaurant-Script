(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    initMobileNav();
    initCartDrawer();
    initAddToCartForms();
    initQtyControls();
    initRemoveButtons();
    initFlashToast();
  });

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  }

  function showToast(message) {
    var toast = document.getElementById("app-toast");
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add("is-visible");
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove("is-visible");
    }, 2800);
  }

  function initFlashToast() {
    var flash = document.body.getAttribute("data-flash-success");
    if (flash) showToast(flash);
  }

  function initMobileNav() {
    var toggle = document.getElementById("nav-toggle");
    var nav = document.getElementById("main-nav");
    var scrim = document.getElementById("nav-scrim");
    var close = document.getElementById("nav-close");
    if (!toggle || !nav) return;

    function open() {
      nav.classList.add("is-open");
      scrim && scrim.classList.add("is-open");
      document.documentElement.classList.add("scroll-locked");
    }
    function closeNav() {
      nav.classList.remove("is-open");
      scrim && scrim.classList.remove("is-open");
      document.documentElement.classList.remove("scroll-locked");
    }
    toggle.addEventListener("click", open);
    close && close.addEventListener("click", closeNav);
    scrim && scrim.addEventListener("click", closeNav);

    // .main-nav switches from an off-canvas drawer to the static desktop
    // layout at the same 960px breakpoint style.css uses (@media (min-width:
    // 960px)). CSS alone recovers the layout at that width, but a resize
    // (not a reload) leaves .is-open and .scroll-locked stuck, since nothing
    // else ever clears them. Below that width they're inert (the drawer is
    // closed either way); above it, this drops the stale locked-scroll state.
    var mediaQuery = window.matchMedia("(min-width: 960px)");
    function closeIfDesktop() {
      if (mediaQuery.matches) closeNav();
    }
    mediaQuery.addEventListener
      ? mediaQuery.addEventListener("change", closeIfDesktop)
      : window.addEventListener("resize", closeIfDesktop);
  }

  function initCartDrawer() {
    var drawer = document.getElementById("cart-drawer");
    var scrim = document.getElementById("cart-scrim");
    var openers = document.querySelectorAll("[data-cart-open]");
    var closers = document.querySelectorAll("[data-cart-close]");
    if (!drawer) return;

    function open() {
      drawer.classList.add("is-open");
      scrim && scrim.classList.add("is-open");
      document.documentElement.classList.add("scroll-locked");
    }
    function close() {
      drawer.classList.remove("is-open");
      scrim && scrim.classList.remove("is-open");
      document.documentElement.classList.remove("scroll-locked");
    }
    openers.forEach(function (el) {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        open();
      });
    });
    closers.forEach(function (el) {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        close();
      });
    });
    scrim && scrim.addEventListener("click", close);

    window.__openCartDrawer = open;
  }

  function updateCartUI(data) {
    var badge = document.getElementById("cart-count-badge");
    if (badge) {
      badge.textContent = data.count;
      badge.classList.toggle("hidden", data.count == 0);
    }
    var mini = document.getElementById("mini-cart-content");
    if (mini && typeof data.html === "string") {
      mini.innerHTML = data.html;
      initQtyControls();
      initRemoveButtons();
    }
    var subtotalEl = document.getElementById("cart-subtotal-value");
    if (subtotalEl && typeof data.subtotal !== "undefined") {
      subtotalEl.setAttribute("data-aed", data.subtotal);
    }
    if (window.applyCurrency) window.applyCurrency();
  }

  function initAddToCartForms() {
    document.querySelectorAll(".add-to-cart-form").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = form.querySelector("button[type=submit]");
        var original = btn ? btn.innerHTML : null;
        if (btn) {
          btn.disabled = true;
          btn.innerHTML = "Adding...";
        }
        fetch(form.action, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": csrfToken(),
            Accept: "application/json",
          },
          body: new FormData(form),
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            updateCartUI(data);
            showToast("Added to your cart");
            if (window.__openCartDrawer) window.__openCartDrawer();
          })
          .catch(function () {
            showToast("Could not add item. Please try again.");
          })
          .finally(function () {
            if (btn) {
              btn.disabled = false;
              btn.innerHTML = original;
            }
          });
      });
    });
  }

  function ajaxCartUpdate(url, method, body) {
    fetch(url, {
      method: method,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": csrfToken(),
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: body ? JSON.stringify(body) : null,
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        updateCartUI(data);
        if (document.getElementById("cart-page-root")) {
          window.location.reload();
        }
      });
  }

  function initQtyControls() {
    document.querySelectorAll("[data-qty-decrease]").forEach(function (btn) {
      btn.onclick = function () {
        var id = btn.getAttribute("data-id");
        var qty = Math.max(0, parseInt(btn.getAttribute("data-qty"), 10) - 1);
        ajaxCartUpdate("/cart/" + id, "PATCH", { quantity: qty });
      };
    });
    document.querySelectorAll("[data-qty-increase]").forEach(function (btn) {
      btn.onclick = function () {
        var id = btn.getAttribute("data-id");
        var qty = parseInt(btn.getAttribute("data-qty"), 10) + 1;
        ajaxCartUpdate("/cart/" + id, "PATCH", { quantity: qty });
      };
    });
  }

  function initRemoveButtons() {
    document.querySelectorAll("[data-remove-item]").forEach(function (btn) {
      btn.onclick = function () {
        var id = btn.getAttribute("data-id");
        ajaxCartUpdate("/cart/" + id, "DELETE");
      };
    });
  }
})();
