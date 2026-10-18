(() => {
    const config = window.siteMeasurement;
    const panel = document.querySelector('[data-measurement-choice]');
    if (!config || !panel) return;
    const key = 'site-measurement-consent';
    let preference = null;
    try { preference = localStorage.getItem(key); } catch (_) { /* Consent remains unset. */ }
    let started = false;
    const start = () => {
        if (started || preference !== 'accepted') return;
        started = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
        window.gtag('js', new Date());
        let referrerOrigin = '';
        try { if (document.referrer) referrerOrigin = new URL(document.referrer).origin; } catch (_) { /* Invalid referrer omitted. */ }
        window.gtag('config', config.id, { page_location: location.origin + config.path, page_referrer: referrerOrigin, allow_google_signals: false, allow_ad_personalization_signals: false, send_page_view: true });
        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.id);
        document.head.append(script);
    };
    const choose = (value) => {
        preference = value;
        try { localStorage.setItem(key, value); } catch (_) { /* No persistent storage available. */ }
        panel.hidden = true;
        if (value === 'rejected' && started) {
            window['ga-disable-' + config.id] = true;
            window.gtag('consent', 'update', { analytics_storage: 'denied' });
        }
        if (value === 'accepted') { window['ga-disable-' + config.id] = false; if (started) window.gtag('consent', 'update', { analytics_storage: 'granted' }); start(); }
    };
    panel.hidden = preference !== null;
    panel.querySelector('[data-measurement-accept]').addEventListener('click', () => choose('accepted'));
    panel.querySelector('[data-measurement-reject]').addEventListener('click', () => choose('rejected'));
    document.querySelector('[data-measurement-preference]').addEventListener('click', () => { panel.hidden = false; });
    document.addEventListener('site:measurement', (event) => {
        if (preference === 'accepted' && started) window.gtag('event', event.detail.name, event.detail.parameters);
    });
    start();
})();
