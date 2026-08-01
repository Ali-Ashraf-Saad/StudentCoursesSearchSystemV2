(function () {
  const nav = document.querySelector(".top-nav");
  if (!nav) return;

  const toggle = nav.querySelector(".nav-menu-toggle");
  const links = nav.querySelector(".nav-links");

  function setOpen(isOpen) {
    nav.classList.toggle("menu-open", isOpen);
    if (toggle) {
      toggle.setAttribute("aria-expanded", String(isOpen));
      toggle.setAttribute("aria-label", isOpen ? "إغلاق القائمة" : "فتح القائمة");
    }
  }

  toggle?.addEventListener("click", () => {
    setOpen(!nav.classList.contains("menu-open"));
  });

  links?.addEventListener("click", (event) => {
    const button = event.target.closest(".nav-btn");
    if (!button) return;

    const href = button.dataset.navHref;
    if (href) {
      window.location.href = href;
      return;
    }

    if (button.dataset.navAction === "refresh") {
      window.location.reload();
    }
  });

  document.addEventListener("click", (event) => {
    if (!nav.classList.contains("menu-open")) return;
    if (nav.contains(event.target)) return;
    setOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setOpen(false);
  });
})();
