(function (global) {
  'use strict';
  var __ =
    global.wp && global.wp.i18n && typeof global.wp.i18n.__ === 'function'
      ? global.wp.i18n.__
      : function (text) {
          return text;
        };
  var TEXT_DOMAIN = 'gafas3d-wizard-modal';

  global.G3DWIZARD = global.G3DWIZARD || {};
  global.G3DWIZARD.last = global.G3DWIZARD.last || null;

  function readValue(el) {
    if (!el) {
      return undefined;
    }

    const tag = el.tagName.toLowerCase();
    const type = (el.getAttribute('type') || '').toLowerCase();

    if (tag === 'input') {
      if (type === 'checkbox') {
        return !!el.checked;
      }

      if (type === 'radio') {
        return el.checked ? el.value : undefined;
      }

      if (type === 'number') {
        const n = Number(el.value);

        return Number.isFinite(n) ? n : undefined;
      }

      return el.value || undefined;
    }

    if (tag === 'select') {
      return el.value || undefined;
    }

    if (tag === 'textarea') {
      return el.value || undefined;
    }

    return el.getAttribute('data-value') || undefined;
  }

  function buildState(modalRoot) {
    const state = {};

    if (!modalRoot) {
      return state;
    }

    const nodes = modalRoot.querySelectorAll('[data-g3d-state-key]');

    Array.prototype.forEach.call(nodes, function (el) {
      const k = el.getAttribute('data-g3d-state-key');

      if (!k) {
        return;
      }

      const v = readValue(el);

      if (v !== undefined && v !== '') {
        state[k] = v;
      }
    });

    return state;
  }

  function setBusy(el, busy) {
    if (!el) {
      return;
    }

    if (busy) {
      el.setAttribute('aria-busy', 'true');
      el.setAttribute('data-busy', '1');
      if (typeof el.textContent === 'string') {
        el.textContent = '';
      }
    } else {
      el.removeAttribute('aria-busy');
      el.removeAttribute('data-busy');
    }
  }

  function disableBtn(btn, on, labelWhenBusy) {
    if (!btn) {
      return;
    }

    if (on) {
      btn.disabled = true;
      btn.setAttribute('aria-disabled', 'true');

      if (labelWhenBusy) {
        btn.setAttribute('data-label-prev', btn.textContent || '');
        btn.textContent = labelWhenBusy;
      }
    } else {
      btn.disabled = false;
      btn.removeAttribute('aria-disabled');
      const prev = btn.getAttribute('data-label-prev');

      if (prev !== null) {
        btn.textContent = prev;
        btn.removeAttribute('data-label-prev');
      }
    }
  }

  function getJSON(url, params, options) {
    var query = '';

    if (params && Object.keys(params).length) {
      query =
        '?' +
        Object.keys(params)
          .map(function (key) {
            return (
              encodeURIComponent(key) + '=' + encodeURIComponent(params[key])
            );
          })
          .join('&');
    }

    var init = { method: 'GET' };

    if (options && options.signal) {
      init.signal = options.signal;
    }

    return fetch(url + query, init);
  }

  global.G3DWIZARD.getJson =
    global.G3DWIZARD.getJson ||
    (async function (url, params, options) {
      const qs =
        params && Object.keys(params).length
          ? '?' + new URLSearchParams(params).toString()
          : '';
      const init = { method: 'GET' };

      if (options && options.signal) {
        init.signal = options.signal;
      }

      const response = await fetch(url + qs, init);

      return response;
    });

  function setText(el, s) {
    if (!el) {
      return;
    }

    el.textContent = s;
  }

  global.G3DWIZARD.postJson = async function postJson(url, body) {
    const options = arguments.length > 2 ? arguments[2] : null;
    const init = {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (global.G3DWIZARD && global.G3DWIZARD.nonce) || '',
      },
      body: JSON.stringify(body || {}),
    };

    if (options && options.signal) {
      init.signal = options.signal;
    }

    const res = await fetch(url, init);

    return res;
  };

  global.G3DWIZARD.getJSON = async function getJSON(url, params) {
    const options = arguments.length > 2 ? arguments[2] : null;
    const qs =
      params && Object.keys(params).length
        ? '?' + new URLSearchParams(params).toString()
        : '';
    const init = { method: 'GET' };

    if (options && options.signal) {
      init.signal = options.signal;
    }

    const res = await fetch(url + qs, init);

    let payload = null;

    try {
      payload = await res.json();
    } catch (error) {
      payload = null;
    }

    if (!res.ok) {
      const requestError = new Error('Request failed');
      requestError.status = res.status;

      if (payload && typeof payload === 'object') {
        requestError.payload = payload;

        if (payload.reason_key) {
          requestError.code = payload.reason_key;
        } else if (payload.code) {
          requestError.code = payload.code;
        }
      }

      throw requestError;
    }

    return payload || {};
  };

  if (global.console && typeof global.console.log === 'function') {
    global.console.log(global.G3DWIZARD.api);
  }

  var OPEN_ATTR = 'data-g3d-wizard-modal-open';
  var CLOSE_ATTR = 'data-g3d-wizard-modal-close';
  var OVERLAY_ATTR = 'data-g3d-wizard-modal-overlay';

  function init() {
    var rootContainer = document.getElementById('gafas3d-wizard-modal-root');
    var overlay = rootContainer
      ? rootContainer.querySelector('[' + OVERLAY_ATTR + ']')
      : document.querySelector('[' + OVERLAY_ATTR + ']');

    if (!overlay) {
      return;
    }

    var root = overlay;
    var modal = root.querySelector('.g3d-wizard-modal');
    var dialog = root.querySelector('[role="dialog"]');
    var cta = overlay.querySelector('[data-g3d-wizard-modal-cta]');
    var verifyButton = overlay.querySelector('[data-g3d-wizard-modal-verify]');
    var message = overlay.querySelector('.g3d-wizard-modal__msg');
    var summaryContainer = overlay.querySelector('.g3d-wizard-modal__summary');
    var rulesContainer = modal ? modal.querySelector('.g3d-wizard-modal__rules') : null;
    var tablist = root.querySelector('[role="tablist"]');
    var tabs = tablist ? tablist.querySelectorAll('[role="tab"]') : [];
    var panels = root.querySelectorAll('[role="tabpanel"]');
    var panelById = {};
    var shouldAutoAudit = modal && modal.getAttribute('data-auto-audit') === '1';
    var previousFocus = null;
    var summaryMessage = '';
    var statusMessage = '';
    var rulesSummaryMessage = '';
    var lastRules = null;
    let currentAbort = null;

    function startAbortableRequest() {
      if (typeof AbortController !== 'function') {
        if (currentAbort) {
          currentAbort = null;
        }

        return null;
      }

      if (currentAbort) {
        try {
          currentAbort.abort();
        } catch (abortError) {
          // no-op
        }
      }

      currentAbort = new AbortController();

      return currentAbort;
    }

    function clearAbort(controller) {
      if (controller && controller === currentAbort) {
        currentAbort = null;
      }
    }

    function updateLiveMessage() {
      if (!message) {
        return;
      }

      var parts = [];

      if (rulesSummaryMessage) {
        parts.push(rulesSummaryMessage);
      }

      if (summaryMessage) {
        parts.push(summaryMessage);
      }

      if (statusMessage) {
        parts.push(statusMessage);
      }

      setText(message, parts.join(' · '));
    }

    function setSummaryMessage(value) {
      summaryMessage = value || '';

      if (summaryContainer) {
        setText(summaryContainer, summaryMessage);
      }

      updateLiveMessage();
    }

    function setRulesSummaryMessage(value) {
      rulesSummaryMessage = value || '';

      if (rulesContainer) {
        setText(rulesContainer, rulesSummaryMessage);
      }

      updateLiveMessage();
    }

    function setButtonEnabledState(button, shouldEnable) {
      if (!button) {
        return;
      }

      var enabled = !!shouldEnable;
      button.disabled = !enabled;
      button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    function gateCtasByRules(rulesPayload) {
      var enable = true;

      if (
        rulesPayload &&
        typeof rulesPayload === 'object' &&
        Object.prototype.hasOwnProperty.call(rulesPayload, 'ok')
      ) {
        enable = rulesPayload.ok === true;
      }

      setButtonEnabledState(cta, enable);
      setButtonEnabledState(verifyButton, enable);
    }

    function setStatusMessage(value) {
      statusMessage = value || '';
      updateLiveMessage();
    }

    function formatRulesSummary(data, fallbackSnapshotId) {
      var payload = data && typeof data === 'object' ? data : {};
      var snapshotValue = '';
      var meta = [];

      if (typeof payload.snapshot_id === 'string' && payload.snapshot_id) {
        snapshotValue = payload.snapshot_id;
      } else if (
        typeof fallbackSnapshotId === 'string' &&
        fallbackSnapshotId
      ) {
        snapshotValue = fallbackSnapshotId;
      }

      if (snapshotValue) {
        meta.push('snap: ' + snapshotValue);
      }

      if (typeof payload.version === 'string' && payload.version) {
        meta.push('ver: ' + payload.version);
      }

      var rulesList = Array.isArray(payload.rules) ? payload.rules : [];
      var message = 'Reglas cargadas: ' + String(rulesList.length);

      if (meta.length) {
        message += ' (' + meta.join(' | ') + ')';
      }

      return message;
    }

    function formatRulesError() {
      return 'Sin reglas';
    }

    function formatRulesNetworkError() {
      return 'Sin reglas';
    }

    function getRulesEndpoint() {
      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (typeof api.catalogRules === 'string' && api.catalogRules) {
        return api.catalogRules;
      }

      if (typeof api.rules === 'string' && api.rules) {
        return api.rules;
      }

      return '/wp-json/g3d/v1/catalog/rules';
    }

    function fetchRules(params, options) {
      var url = getRulesEndpoint();

      if (!url) {
        throw new Error('Missing catalog rules endpoint');
      }

      var query =
        params && Object.keys(params).length
          ? '?' + new URLSearchParams(params).toString()
          : '';
      var init = { method: 'GET' };

      if (options && options.signal) {
        init.signal = options.signal;
      }

      return fetch(url + query, init);
    }

    function audit(action, extras) {
      try {
        if (!modal) {
          return;
        }

        var wizard = global.G3DWIZARD || {};
        var api = wizard.api || {};

        if (!api.audit || typeof wizard.postJson !== 'function') {
          return;
        }

        var actor = modal.getAttribute('data-actor-id') || '';
        var what = modal.getAttribute('data-what') || '';

        if (!actor || !what) {
          return;
        }

        var payload = {
          actor_id: actor,
          action: action,
          context: Object.assign({ what: what }, extras || {}),
        };

        wizard
          .postJson(api.audit, payload)
          .then(function (response) {
            if (
              response &&
              (response.status === 401 || response.status === 403) &&
              global.console &&
              typeof global.console.debug === 'function'
            ) {
              // TODO(doc §RBAC): revisar permisos para /audit.
              global.console.debug('Audit endpoint denied access.');
            }

            return response;
          })
          .catch(function () {
            if (global.console && typeof global.console.debug === 'function') {
              global.console.debug('Audit request failed.');
            }
          });
      } catch (error) {
        // no-op
      }
    }

    function isTabDisabled(tab) {
      if (!tab) {
        return true;
      }

      if (tab.disabled) {
        return true;
      }

      if (!tab.hasAttribute('aria-disabled')) {
        return false;
      }

      var value = tab.getAttribute('aria-disabled');

      return value === 'true' || value === '1';
    }

    Array.prototype.forEach.call(panels, function (panel) {
      if (!panel || !panel.id) {
        return;
      }

      panelById[panel.id] = panel;
    });

    function idxOf(nodeList, el) {
      return Array.prototype.indexOf.call(nodeList, el);
    }

    function getFirstEnabledTab() {
      for (var i = 0; i < tabs.length; i += 1) {
        if (!isTabDisabled(tabs[i])) {
          return tabs[i];
        }
      }

      return null;
    }

    function getLastEnabledTab() {
      for (var i = tabs.length - 1; i >= 0; i -= 1) {
        if (!isTabDisabled(tabs[i])) {
          return tabs[i];
        }
      }

      return null;
    }

    function activateTab(tabEl) {
      if (!tabEl || isTabDisabled(tabEl)) {
        return;
      }

      var target = tabEl.getAttribute('aria-controls');

      if (!target || !panelById[target]) {
        return;
      }

      Array.prototype.forEach.call(tabs, function (t) {
        var active = t === tabEl;
        t.setAttribute('aria-selected', active ? 'true' : 'false');
        t.setAttribute('tabindex', active ? '0' : '-1');
      });

      Array.prototype.forEach.call(panels, function (p) {
        p.hidden = p.id !== target;
      });

      if (typeof tabEl.focus === 'function') {
        tabEl.focus();
      }
    }

    function focusNext(current, dir) {
      var index = idxOf(tabs, current);

      if (index < 0) {
        return;
      }

      var len = tabs.length;

      if (!len) {
        return;
      }

      var step = dir === 1 ? 1 : -1;
      var next = index;

      for (var attempt = 0; attempt < len; attempt += 1) {
        next = (next + step + len) % len;
        var candidate = tabs[next];

        if (!candidate || isTabDisabled(candidate)) {
          continue;
        }

        if (typeof candidate.focus === 'function') {
          candidate.focus();
        }

        activateTab(candidate);

        return;
      }
    }

    (function initTabs() {
      if (!tabs.length || !panels.length) {
        return;
      }

      var active = Array.prototype.find
        ? Array.prototype.find.call(tabs, function (candidate) {
            return (
              candidate &&
              candidate.getAttribute &&
              candidate.getAttribute('aria-selected') === 'true' &&
              !isTabDisabled(candidate)
            );
          })
        : null;

      if (!active) {
        active = getFirstEnabledTab() || (tabs.length ? tabs[0] : null);
      }

      if (active) {
        activateTab(active);
      }
    })();

    function getModalData() {
      var snapshotId = '';
      var productoId = '';
      var locale = '';

      if (modal) {
        snapshotId = modal.getAttribute('data-snapshot-id') || '';
        productoId = modal.getAttribute('data-producto-id') || '';
        locale = modal.getAttribute('data-locale') || '';
      }

      return {
        snapshotId: snapshotId,
        productoId: productoId,
        locale: locale,
      };
    }

    async function handleCtaClick(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      if (!cta) {
        return;
      }

      if (cta.disabled) {
        return;
      }

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.validateSign || typeof wizard.postJson !== 'function') {
        setStatusMessage('ERROR — endpoint no disponible');
        return;
      }

      var modalData = getModalData();
      var snapshotId = modalData.snapshotId;
      var productoId = modalData.productoId;
      var locale = modalData.locale;

      if (!locale && wizard.locale) {
        locale = wizard.locale;
      }

      // TODO(Capa 4 §estado wizard): documentar claves válidas para state
      const state = buildState(modal);

      const payload = {
        state: state,
      };

      if (snapshotId) {
        payload.snapshot_id = snapshotId;
      }

      if (productoId) {
        payload.producto_id = productoId;
      }

      if (locale) {
        payload.locale = locale;
      }

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;
      disableBtn(cta, true, __('Enviando…', TEXT_DOMAIN));
      setBusy(message, true);
      setStatusMessage('');

      try {
        var response = await wizard.postJson(api.validateSign, payload, {
          signal: signal,
        });
        var data = null;

        try {
          data = await response.json();
        } catch (jsonError) {
          data = null;
        }

        const resultData = data || null;

        global.G3DWIZARD.last = {
          payload: payload,
          response: resultData,
        };

        if (response.ok) {
          var hash = data && data.sku_hash ? data.sku_hash : '-';
          var expires = data && data.expires_at ? data.expires_at : '-';
          var snapshotValue =
            data && data.snapshot_id ? data.snapshot_id : snapshotId || '';

          setStatusMessage(
            __('OK — hash: ', TEXT_DOMAIN) +
              hash +
              __(' | expira: ', TEXT_DOMAIN) +
              expires
          );

          if (shouldAutoAudit) {
            audit('validate_sign_success', {
              snapshot_id: snapshotValue || '',
              // TODO(Plugin 5 §Auditoría): campos adicionales permitidos
            });
          }

          // TODO(plugin-4-gafas3d-wizard-modal.md §9): encadenar verify tras validate-sign.

        } else {
          var code = '-';

          if (data) {
            if (data.reason_key) {
              code = data.reason_key;
            } else if (data.code) {
              code = data.code;
            }
          }

          if (code === '-' && response.status) {
            code = 'HTTP ' + response.status;
          }

          setStatusMessage('ERROR — ' + code);
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        global.G3DWIZARD.last = {
          payload: payload,
          response: null,
        };

        setStatusMessage('ERROR — NETWORK');
      } finally {
        disableBtn(cta, false);
        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);
        }

        clearAbort(controller);
      }
    }

    async function runVerifyRequest() {
      if (!verifyButton) {
        return;
      }

      if (verifyButton.disabled) {
        return;
      }

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.verify || typeof wizard.postJson !== 'function') {
        setStatusMessage('ERROR — endpoint no disponible');
        return;
      }

      var last = wizard.last || null;
      var response = last && typeof last === 'object' ? last.response : null;
      var payload = last && typeof last === 'object' ? last.payload : null;
      var skuHash =
        response && typeof response.sku_hash === 'string' ? response.sku_hash : '';
      var skuSignature =
        response && typeof response.sku_signature === 'string'
          ? response.sku_signature
          : '';
      var snapshotFromResponse =
        response && typeof response.snapshot_id === 'string'
          ? response.snapshot_id
          : '';
      var snapshotFromPayload =
        payload && typeof payload.snapshot_id === 'string'
          ? payload.snapshot_id
          : '';
      var snapshotForVerify = snapshotFromResponse || snapshotFromPayload || '';

      if (!skuHash || !skuSignature) {
        setStatusMessage('ERROR — Primero valida y firma');
        return;
      }

      var payloadVerify = {
        sku_hash: skuHash,
        sku_signature: skuSignature,
        snapshot_id: snapshotForVerify,
      };

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;
      disableBtn(verifyButton, true, __('Verificando…', TEXT_DOMAIN));

      setBusy(message, true);
      setStatusMessage('');

      try {
        var responseVerify = await wizard.postJson(api.verify, payloadVerify, {
          signal: signal,
        });
        var data = null;

        try {
          data = await responseVerify.json();
        } catch (jsonError) {
          data = null;
        }

        if (responseVerify.ok && data && data.ok === true) {
          if (data.request_id) {
            setStatusMessage(
              __('Verificado OK — request_id: ', TEXT_DOMAIN) + data.request_id
            );
          } else {
            setStatusMessage(__('Verificado OK', TEXT_DOMAIN));
            // TODO(plugin-3-g3d-validate-sign.md §6.2 POST /verify): exponer request_id en payload.
          }

          if (shouldAutoAudit) {
            audit('verify_success', {
              snapshot_id: snapshotForVerify || '',
            });
          }
        } else {
          var code = null;

          if (data) {
            if (data.reason_key) {
              code = data.reason_key;
            } else if (data.code) {
              code = data.code;
            }
          }

          if (!code && responseVerify.status) {
            code = 'HTTP ' + responseVerify.status;
          }

          if (!code) {
            code = __('ERROR', TEXT_DOMAIN);
          }

          setStatusMessage('ERROR — ' + code);
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        setStatusMessage('ERROR — NETWORK');
      } finally {
        disableBtn(verifyButton, false);
        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);
        }

        clearAbort(controller);
      }
    }

    function handleVerifyClick(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      runVerifyRequest();
    }

    async function loadRulesData(snapshotId, productoId, locale) {
      disableBtn(cta, true, __('Cargando…', TEXT_DOMAIN));

      if (verifyButton) {
        disableBtn(verifyButton, true, __('Cargando…', TEXT_DOMAIN));
      }

      setBusy(message, true);

      if (rulesContainer) {
        setBusy(rulesContainer, true);
      }

      var missing = [];

      if (!productoId) {
        missing.push('producto_id');
      }

      if (!snapshotId) {
        missing.push('snapshot_id');
      }

      if (!locale) {
        missing.push('locale');
      }

      if (missing.length) {
        lastRules = null;
        setRulesSummaryMessage(
          'TODO(docs/plugin-2-g3d-catalog-rules.md §6 APIs / Contratos (lectura)): falta ' +
            missing.join(', ')
        );
        setBusy(message, false);

        if (rulesContainer) {
          setBusy(rulesContainer, false);
        }

        disableBtn(cta, false);

        if (verifyButton) {
          disableBtn(verifyButton, false);
        }

        gateCtasByRules(lastRules);

        return;
      }

      var params = {};

      if (productoId) {
        params.producto_id = productoId;
      }

      if (snapshotId) {
        params.snapshot_id = snapshotId;
      }

      if (locale) {
        params.locale = locale;
      }

      setRulesSummaryMessage(__('Reglas: cargando…', TEXT_DOMAIN));

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;

      try {
        var response = await fetchRules(params, {
          signal: signal,
        });

        if (response && response.ok) {
          var data = null;

          try {
            data = await response.json();
          } catch (jsonError) {
            data = null;
          }

          var parsed = data && typeof data === 'object' ? data : {};
          lastRules = parsed;
          setRulesSummaryMessage(formatRulesSummary(parsed, snapshotId));
        } else {
          lastRules = null;
          setRulesSummaryMessage(formatRulesError());
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        lastRules = null;
        setRulesSummaryMessage(formatRulesNetworkError());
      } finally {
        disableBtn(cta, false);

        if (verifyButton) {
          disableBtn(verifyButton, false);
        }

        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);

          if (rulesContainer) {
            setBusy(rulesContainer, false);
          }
        }

        clearAbort(controller);
        if (modal) {
          modal.setAttribute('data-ready', '1');
        }
        gateCtasByRules(lastRules);
      }
    }

    function openModal(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      previousFocus =
        document.activeElement instanceof HTMLElement ? document.activeElement : null;

      overlay.removeAttribute('hidden');
      document.body.classList.add('g3d-wizard-open');

      if (dialog && typeof dialog.focus === 'function') {
        dialog.focus();
      }

      var wizard = global.G3DWIZARD || {};
      var modalData = getModalData();
      var snapshotId = modalData.snapshotId;
      var productoId = modalData.productoId;
      var locale = modalData.locale || wizard.locale || '';

      setStatusMessage('');
      setSummaryMessage('');

      loadRulesData(snapshotId, productoId, locale);
    }

    function closeModal(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      overlay.setAttribute('hidden', '');
      document.body.classList.remove('g3d-wizard-open');

      if (currentAbort) {
        try {
          currentAbort.abort();
        } catch (abortError) {
          // no-op
        }

        currentAbort = null;
      }

      if (previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
      }

      previousFocus = null;
      setSummaryMessage('');
      setStatusMessage('');
      setRulesSummaryMessage('');

      setBusy(message, false);

      if (rulesContainer) {
        setBusy(rulesContainer, false);
      }
    }

    var openButtons = document.querySelectorAll('[' + OPEN_ATTR + ']');
    openButtons.forEach(function (button) {
      button.addEventListener('click', openModal);
    });

    var closeButtons = overlay.querySelectorAll('[' + CLOSE_ATTR + ']');
    closeButtons.forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    var rulesRetryControl = overlay.querySelector(
      '[data-g3d-wizard-retry-rules]'
    );

    if (rulesRetryControl) {
      rulesRetryControl.addEventListener('click', function (event) {
        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }

        var wizard = global.G3DWIZARD || {};
        var modalData = getModalData();
        var snapshotId = modalData.snapshotId;
        var productoId = modalData.productoId;
        var locale = modalData.locale || wizard.locale || '';

        setStatusMessage('');
        loadRulesData(snapshotId, productoId, locale);
      });
    } else {
      // TODO(Plugin 4 §controles de recarga).
    }

    if (tabs.length && panels.length) {
      Array.prototype.forEach.call(tabs, function (tab) {
        tab.addEventListener('click', function (event) {
          if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
          }

          if (isTabDisabled(tab)) {
            return;
          }

          activateTab(tab);
        });

        tab.addEventListener('keydown', function (event) {
          if (!event) {
            return;
          }

          var key = event.key;

          if (key === 'ArrowRight') {
            event.preventDefault();
            focusNext(tab, 1);
            return;
          }

          if (key === 'ArrowLeft') {
            event.preventDefault();
            focusNext(tab, -1);
            return;
          }

          if (key === 'Home') {
            event.preventDefault();

            var first = getFirstEnabledTab() || (tabs.length ? tabs[0] : null);

            if (first) {
              activateTab(first);
            }

            return;
          }

          if (key === 'End') {
            event.preventDefault();

            var last = getLastEnabledTab() || (tabs.length ? tabs[tabs.length - 1] : null);

            if (last) {
              activateTab(last);
            }

            return;
          }

          if (key === 'Enter' || key === ' ' || key === 'Space') {
            event.preventDefault();

            if (isTabDisabled(tab)) {
              return;
            }

            activateTab(tab);
          }
        });
      });
    }

    if (cta) {
      cta.addEventListener('click', handleCtaClick);
    }

    if (verifyButton) {
      verifyButton.addEventListener('click', handleVerifyClick);
    }

    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        closeModal(event);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !overlay.hasAttribute('hidden')) {
        closeModal(event);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
