function loadQACounter() {
      fetch("/counterFiles/counter?counter=qa", { cache: "no-store" })
        .then(response => response.json())
        .then(data => {
          document.getElementById("visitCount").innerText = data.count ?? 0;
        })
        .catch(() => {
          document.getElementById("visitCount").innerText = "--";
        });
    }

    const QA_VISIT_KEY = "qa_visit";
    const QA_VISIT_TTL = 1 * 60 * 1000;
    const lastQAVisit = Number(sessionStorage.getItem(QA_VISIT_KEY));
    const enteredFromStats = (() => {
      if (!document.referrer) return false;
      try {
        const referrer = new URL(document.referrer);
        return referrer.origin === location.origin && /^\/stats(?:\.php)?\/?$/.test(referrer.pathname);
      } catch (_) {
        return false;
      }
    })();
    const qaVisitPromise = (!enteredFromStats && (!Number.isFinite(lastQAVisit) || Date.now() - lastQAVisit > QA_VISIT_TTL))
      ? (() => {
      sessionStorage.setItem(QA_VISIT_KEY, String(Date.now()));
      return fetch("/counterFiles/counter?action=increment&counter=qa", {
        method: "POST",
        keepalive: true,
        cache: "no-store"
      }).catch(() => {});
    })()
      : Promise.resolve();

    qaVisitPromise.finally(loadQACounter);
    setInterval(loadQACounter, 4000);
