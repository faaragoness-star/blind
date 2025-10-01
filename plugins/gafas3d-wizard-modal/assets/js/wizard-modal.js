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

  global.G3DWIZARD.getJson = async function getJson(url, query) {
    const qs = query ? '?' + new URLSearchParams(query).toString() : '';
    const res = await fetch(url + qs, { method: 'GET' });
    return res;
  };

  global.G3DWIZARD.last = global.G3DWIZARD.last || null;
  global.G3DWIZARD.lastRules = global.G3DWIZARD.lastRules || null;
  global.G3DWIZARD.rules = global.G3DWIZARD.rules || null;

  let rulesPromise = null;
  let rulesData = null;
  let rulesResponse = null;
  let netCtl = null;

  function resetNetCtl() {
    if (netCtl && typeof netCtl.abort === 'function') {
      netCtl.abort();
    }

    netCtl =
      typeof AbortController !== 'undefined' ? new AbortController() : null;

    return netCtl;
  }

  function setBusy(modalEl, on) {
    if (!modalEl) {
      return;
    }

    if (on) {
      modalEl.setAttribute('aria-busy', 'true');

      var isModal =
        modalEl.classList && modalEl.classList.contains('g3d-wizard-modal');

      if (
        isModal &&
        typeof document !== 'undefined' &&
        document.body &&
        document.body.classList
      ) {
        document.body.classList.add('g3d-wizard-open');
      }
    } else {
      modalEl.removeAttribute('aria-busy');
    }
  }

  function setBtnBusy(btn, on, labelIdle, labelBusy) {
    if (!btn) {
      return;
    }

    btn.disabled = !!on;

    if (on && labelBusy) {
      btn.textContent = labelBusy;
    }

    if (!on && labelIdle) {
      btn.textContent = labelIdle;
    }
  }

  function announce(msgEl, text) {
    if (msgEl) {
      msgEl.textContent = String(text || '');
    }
  }

  var inflight = { validate: false, verify: false };

  function setDisabled(el, on) {
    if (!el) {
      return;
    }

    const disabled = !!on;

    el.disabled = disabled;
    if (disabled) {
      el.setAttribute('aria-disabled', 'true');
    } else {
      el.removeAttribute('aria-disabled');
    }
  }

  function buildQuery(params) {
    var query = {};

    if (!params || typeof params !== 'object') {
      return query;
    }

    Object.keys(params).forEach(function (key) {
      var value = params[key];

      if (value === undefined || value === null || value === '') {
        return;
      }

      query[key] = value;
    });

    return query;
  }

  async function getJSON(url, params) {
    if (!url) {
      return { res: null, data: {} };
    }

    var query = buildQuery(params);
    var res = await global.G3DWIZARD.getJson(url, query);
    var data = {};

    if (res && typeof res.json === 'function') {
      data = await res.json().catch(function () {
        return {};
      });
    }

    return { res: res, data: data };
  }

  global.G3DWIZARD.getJSON = getJSON;

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

  function setText(el, s) {
    if (!el) {
      return;
    }

    el.textContent = s;
  }

  function findPanelById(panels, id) {
    var panel = null;

    Array.prototype.forEach.call(panels, function (candidate) {
      if (!panel && candidate.id === id) {
        panel = candidate;
      }
    });

    return panel;
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

  function getTabs(rootEl) {
    return rootEl ? rootEl.querySelectorAll('[role="tab"]') : [];
  }

  function getPanels(rootEl) {
    return rootEl ? rootEl.querySelectorAll('[role="tabpanel"]') : [];
  }

  function activateTab(tabEl, rootEl) {
    if (!tabEl || !rootEl) {
      return;
    }

    if (isTabDisabled(tabEl)) {
      return;
    }

    var id = tabEl.getAttribute('aria-controls');
    var tabs = getTabs(rootEl);
    var panels = getPanels(rootEl);

    if (!id) {
      if (global.console && typeof global.console.warn === 'function') {
        global.console.warn('G3DWIZARD: tab sin aria-controls.');
      }

      // TODO(doc §estructura tabs)
      return;
    }

    var panel = findPanelById(panels, id);

    if (!panel) {
      if (global.console && typeof global.console.warn === 'function') {
        global.console.warn(
          'G3DWIZARD: aria-controls="' + id + '" sin <tabpanel> asociado.'
        );
      }

      // TODO(doc §estructura tabs)
      return;
    }

    Array.prototype.forEach.call(tabs, function (t) {
      if (!t) {
        return;
      }

      var on = t === tabEl;

      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.setAttribute('tabindex', on ? '0' : '-1');
    });

    Array.prototype.forEach.call(panels, function (p) {
      if (!p) {
        return;
      }

      p.hidden = p.id !== id;
    });

    var shouldFocus = !rootEl.hasAttribute('hidden');

    if (shouldFocus && typeof tabEl.focus === 'function') {
      tabEl.focus();
    }
  }

  function onTabKeydown(e, rootEl) {
    if (!rootEl) {
      return;
    }

    var tabs = getTabs(rootEl);

    if (!tabs || !tabs.length) {
      return;
    }

    var current = e.currentTarget;

    if (!current || isTabDisabled(current)) {
      return;
    }

    var idx = Array.prototype.indexOf.call(tabs, current);

    if (idx < 0) {
      return;
    }

    var next = null;
    var key = e.key;
    var total = tabs.length;

    if (key === 'ArrowRight') {
      e.preventDefault();

      for (var step = 1; step <= total; step += 1) {
        var candidateRight = tabs[(idx + step) % total];

        if (candidateRight && !isTabDisabled(candidateRight)) {
          next = candidateRight;
          break;
        }
      }
    } else if (key === 'ArrowLeft') {
      e.preventDefault();

      for (var stepLeft = 1; stepLeft <= total; stepLeft += 1) {
        var candidateLeft = tabs[(idx - stepLeft + total) % total];

        if (candidateLeft && !isTabDisabled(candidateLeft)) {
          next = candidateLeft;
          break;
        }
      }
    } else if (key === 'Home') {
      e.preventDefault();

      for (var i = 0; i < total; i += 1) {
        var first = tabs[i];

        if (first && !isTabDisabled(first)) {
          next = first;
          break;
        }
      }
    } else if (key === 'End') {
      e.preventDefault();

      for (var j = total - 1; j >= 0; j -= 1) {
        var last = tabs[j];

        if (last && !isTabDisabled(last)) {
          next = last;
          break;
        }
      }
    } else if (
      key === 'Enter' ||
      key === ' ' ||
      key === 'Space' ||
      key === 'Spacebar'
    ) {
      e.preventDefault();
      activateTab(current, rootEl);

      return;
    }

    if (next) {
      activateTab(next, rootEl);
    }
  }

  global.G3DWIZARD.postJson = async function postJson(url, body, init) {
    const res = await fetch(
      url,
      Object.assign(
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': (global.G3DWIZARD && global.G3DWIZARD.nonce) || '',
          },
          body: JSON.stringify(body || {}),
        },
        init || {}
      )
    );

    return res;
  };

  if (global.console && typeof global.console.log === 'function') {
    global.console.log(global.G3DWIZARD.api);
  }

  var OPEN_ATTR = 'data-g3d-wizard-modal-open';
  var CLOSE_ATTR = 'data-g3d-wizard-modal-close';
  var OVERLAY_ATTR = 'data-g3d-wizard-modal-overlay';

  async function init() {
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
    var ctaLabelIdle = cta ? cta.textContent || '' : '';
    var verifyLabelIdle = verifyButton ? verifyButton.textContent || '' : '';
    var ctaBusyLabel = __('Enviando…', TEXT_DOMAIN);
    var verifyBusyLabel = __('Verificando…', TEXT_DOMAIN);
    var summaryContainer = overlay.querySelector('.g3d-wizard-modal__summary');
    var rulesContainer = modal
      ? modal.querySelector('[data-g3d-wizard-rules]')
      : null;
    var tablist = root.querySelector('[role="tablist"]');
    var shouldAutoAudit = modal && modal.getAttribute('data-auto-audit') === '1';
    var previousFocus = null;
    var summaryMessage = '';
    var statusMessage = '';
    var rulesSummaryMessage = '';
    var rulesLoaded = false;
    var rulesAttempted = false;
    var rulesCount = 0;
    var ttlMessage = '';
    var lastRules = null;
    var rulesSelection = {};
    var ttlIntervalId = null;
    var ttlDeadline = null;
    var hasExpired = false;
    var autoVerifyEnabled = false;
    var liveOverrideMessage = '';

    function updateModalBusy() {
      if (!modal) {
        return;
      }

      var isBusy = inflight.validate || inflight.verify;

      setBusy(modal, isBusy);
    }


    if (
      rootContainer &&
      rootContainer.getAttribute('data-auto-verify') === '1'
    ) {
      autoVerifyEnabled = true;
    }

    if (!autoVerifyEnabled && overlay) {
      autoVerifyEnabled = overlay.getAttribute('data-auto-verify') === '1';
    }

    if (!autoVerifyEnabled && modal) {
      autoVerifyEnabled = modal.getAttribute('data-auto-verify') === '1';
    }

    function updateLiveMessage() {
      if (!message) {
        return;
      }

      if (liveOverrideMessage) {
        var overrideText = liveOverrideMessage;

        if (ttlMessage) {
          overrideText += ' · ' + ttlMessage;
        }

        announce(message, overrideText);

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

      if (ttlMessage) {
        parts.push(ttlMessage);
      }

      announce(message, parts.join(' · '));
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
      updateLiveMessage();
    }

  function setRulesContainerText(value) {
    if (!rulesContainer) {
      return;
    }

    rulesContainer.textContent = value || '';
  }

  function setRulesBusyState(on) {
    if (rulesContainer) {
      if (on) {
        rulesContainer.setAttribute('aria-busy', 'true');
      } else {
        rulesContainer.removeAttribute('aria-busy');
      }

      return;
    }

    if (!root) {
      return;
    }

    if (on) {
      root.setAttribute('aria-busy', 'true');
    } else {
      root.removeAttribute('aria-busy');
    }

    // TODO(doc §markup): definir panel específico para mostrar estado de reglas.
  }

    function showRulesLoading() {
      setRulesContainerText(__('Reglas: cargando…', TEXT_DOMAIN));
    }

    function showRulesError(messageText) {
      if (messageText) {
        setRulesContainerText(messageText);

        return;
      }

      setRulesContainerText(formatRulesError(rulesResponse, rulesData));
    }

    function showRulesNetworkError() {
      showRulesError(formatRulesNetworkError());
    }

    function showRulesList(rules) {
      if (!rulesContainer) {
        return;
      }

      rulesContainer.textContent = '';

      if (!Array.isArray(rules) || rules.length === 0) {
        showRulesError();

        return;
      }

      var list = document.createElement('ul');

      rules.forEach(function (rule) {
        var item = document.createElement('li');
        var label = '';

        if (rule && typeof rule === 'object') {
          if (typeof rule.label === 'string' && rule.label) {
            label = rule.label;
          } else if (typeof rule.key === 'string' && rule.key) {
            label = rule.key; // TODO(plugin-2-g3d-catalog-rules.md §payload): confirmar etiqueta visible.
          }
        } else if (typeof rule === 'string' && rule) {
          label = rule;
        }

        if (!label) {
          label = 'rule.key'; // TODO(plugin-2-g3d-catalog-rules.md §payload): definir fallback visible.
        }

        item.textContent = label;
        list.appendChild(item);
      });

      rulesContainer.appendChild(list);
    }

    function resetRulesSelection() {
      rulesSelection = {};
      // TODO(plugin-2-g3d-catalog-rules.md §payload state): mapear selección de reglas cuando se documente.
    }

    function setButtonEnabledState(button, shouldEnable) {
      if (!button) {
        return;
      }

      setDisabled(button, !shouldEnable);
    }

    function gateCtasByRules(rulesPayload) {
      var enable = false;

      if (
        rulesPayload &&
        typeof rulesPayload === 'object' &&
        Object.prototype.hasOwnProperty.call(rulesPayload, 'ok')
      ) {
        enable = rulesPayload.ok === true;
      } else if (
        rulesPayload &&
        typeof rulesPayload === 'object' &&
        Array.isArray(rulesPayload.rules)
      ) {
        enable = true;
      }

      setButtonEnabledState(cta, enable);
      setButtonEnabledState(verifyButton, enable);
    }

    function setStatusMessage(value, options) {
      statusMessage = value || '';
      var opts = options && typeof options === 'object' ? options : {};

      if (opts.override === true) {
        liveOverrideMessage = statusMessage;
      } else if (!statusMessage) {
        liveOverrideMessage = '';
      } else if (opts.keepOverride === true) {
        // preserve current liveOverrideMessage
      } else {
        liveOverrideMessage = '';
      }

      updateLiveMessage();
    }

    function setTtlMessage(value) {
      ttlMessage = value || '';
      updateLiveMessage();
    }

    function clearTtlCountdown() {
      if (ttlIntervalId) {
        global.clearInterval(ttlIntervalId);
        ttlIntervalId = null;
      }

      ttlDeadline = null;
    }

    function resetTtlState() {
      hasExpired = false;
      clearTtlCountdown();
      setTtlMessage('');
    }

    function padTimeSegment(value) {
      var segment = Number(value);

      if (!Number.isFinite(segment)) {
        segment = 0;
      }

      return segment < 10 ? '0' + String(segment) : String(segment);
    }

    function formatRemainingDuration(milliseconds) {
      var totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
      var hours = Math.floor(totalSeconds / 3600);
      var minutes = Math.floor((totalSeconds % 3600) / 60);
      var seconds = totalSeconds % 60;

      if (hours > 0) {
        return (
          padTimeSegment(hours) +
          ':' +
          padTimeSegment(minutes) +
          ':' +
          padTimeSegment(seconds)
        );
      }

      return padTimeSegment(minutes) + ':' + padTimeSegment(seconds);
    }

    function markAsExpired() {
      hasExpired = true;
      setTtlMessage('Expirado.');

      if (verifyButton) {
        setButtonEnabledState(verifyButton, false);
      }
    }

    function updateTtlCountdown() {
      if (!ttlDeadline) {
        return;
      }

      var now = Date.now();
      var remaining = ttlDeadline - now;

      if (remaining <= 0) {
        clearTtlCountdown();
        markAsExpired();

        return;
      }

      hasExpired = false;
      setTtlMessage('OK — expira en ' + formatRemainingDuration(remaining));
    }

    function startTtlCountdown(expiresAt) {
      if (typeof expiresAt !== 'string' || !expiresAt) {
        return false;
      }

      clearTtlCountdown();

      var parsed = Date.parse(expiresAt);

      if (Number.isNaN(parsed)) {
        // TODO(Capa 3 §Caducidad): definir formato/ausencia y no mostrar cuenta atrás (comportamiento degradado).
        return false;
      }

      ttlDeadline = parsed;

      if (ttlDeadline <= Date.now()) {
        markAsExpired();

        return true;
      }

      updateTtlCountdown();
      ttlIntervalId = global.setInterval(function () {
        updateTtlCountdown();
      }, 1000);

      return true;
    }

    function formatRulesSummary(data) {
      var payload = data && typeof data === 'object' ? data : {};
      var rulesList = Array.isArray(payload.rules) ? payload.rules : [];

      return (
        'Reglas cargadas (' +
        String(rulesList.length) +
        ') — TODO(plugin-4-gafas3d-wizard-modal.md §5.6).'
      );
    }

    function formatRulesError(response, payload) {
      var body = payload && typeof payload === 'object' ? payload : {};

      if (body && typeof body.reason_key === 'string' && body.reason_key) {
        return 'ERROR — ' + body.reason_key;
      }

      if (body && typeof body.code === 'string' && body.code) {
        return 'ERROR — ' + body.code;
      }

      if (response && typeof response.status === 'number' && response.status) {
        return 'ERROR — HTTP ' + String(response.status);
      }

      return 'ERROR — HTTP';
    }

    function formatRulesNetworkError() {
      return 'ERROR — NETWORK';
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

      if (inflight.validate) {
        return;
      }

      if (cta.disabled) {
        return;
      }

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.validateSign || typeof wizard.postJson !== 'function') {
        setStatusMessage('Error ✗ — endpoint no disponible', { override: true });
        return;
      }

      resetTtlState();
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

      if (rulesSelection && Object.keys(rulesSelection).length) {
        // TODO(plugin-4-gafas3d-wizard-modal.md §4 estado): incluir selección de reglas en payload cuando se documente.
      }

      if (snapshotId) {
        payload.snapshot_id = snapshotId;
      }

      if (productoId) {
        payload.producto_id = productoId;
      }

      if (locale) {
        payload.locale = locale;
      }

      var signal = netCtl && netCtl.signal ? netCtl.signal : undefined;

      setBtnBusy(cta, true, ctaLabelIdle, ctaBusyLabel);
      inflight.validate = true;
      setBusy(modal, true);
      updateModalBusy();
      setStatusMessage(__('Validando…', TEXT_DOMAIN), { override: true });

      try {
        var response = await wizard.postJson(
          api.validateSign,
          payload,
          signal ? { signal: signal } : undefined
        );
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
          var hash = data && data.sku_hash ? data.sku_hash : '';
          var signature =
            data && typeof data.sku_signature === 'string'
              ? data.sku_signature
              : '';
          var expiresAtRaw =
            data && typeof data.expires_at === 'string' ? data.expires_at : '';
          var snapshotValue =
            data && data.snapshot_id ? data.snapshot_id : snapshotId || '';

          if (verifyButton) {
            setButtonEnabledState(verifyButton, true);
          }

          var countdownStarted = false;

          if (expiresAtRaw) {
            countdownStarted = startTtlCountdown(expiresAtRaw);
          } else {
            // TODO(Capa 3 §Caducidad): definir formato/ausencia y no mostrar cuenta atrás (comportamiento degradado).
          }

          var hashLabel = hash || '-';
          var expiresLabel = expiresAtRaw || '-';
          var successMessage =
            'Validado ✓ — hash: ' + hashLabel + ' | expira: ' + expiresLabel;

          setStatusMessage(successMessage, { override: true });

          if (verifyButton) {
            setButtonEnabledState(verifyButton, !hasExpired);
          }

          if (shouldAutoAudit) {
            audit('validate_sign_success', {
              snapshot_id: snapshotValue || '',
              // TODO(Plugin 5 §Auditoría): campos adicionales permitidos
            });
          }

          if (
            autoVerifyEnabled &&
            !hasExpired &&
            hash &&
            signature
          ) {
            runVerifyRequest({ force: true });
          }
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

          setStatusMessage('Error ✗ — ' + code, { override: true });
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          setStatusMessage('Cancelado', { override: true });

          return;
        }

        global.G3DWIZARD.last = {
          payload: payload,
          response: null,
        };

        setStatusMessage('Error ✗ — NETWORK', { override: true });
      } finally {
        inflight.validate = false;

        if (!inflight.verify) {
          setBusy(modal, false);
        }

        updateModalBusy();
        setBtnBusy(cta, false, ctaLabelIdle, ctaBusyLabel);

        if (!statusMessage) {
          liveOverrideMessage = '';
          announce(message, '');
        }
      }
    }

    async function runVerifyRequest(options) {
      if (!verifyButton) {
        return;
      }

      var opts = options && typeof options === 'object' ? options : {};
      var force = opts.force === true;

      if (inflight.verify) {
        return;
      }

      if (!force && verifyButton.disabled) {
        return;
      }

      if (hasExpired) {
        return;
      }

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.verify || typeof wizard.postJson !== 'function') {
        setStatusMessage('Error ✗ — endpoint no disponible', { override: true });
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
        setStatusMessage('Primero valida y firma', { override: true });

        return;
      }

      var payloadVerify = {
        sku_hash: skuHash,
        sku_signature: skuSignature,
        snapshot_id: snapshotForVerify,
      };

      var signal = netCtl && netCtl.signal ? netCtl.signal : undefined;
      var initialDisabled = verifyButton.disabled;
      var verifyBusyLabel = __('Verificando…', TEXT_DOMAIN);

      setBtnBusy(verifyButton, true, verifyLabelIdle, verifyBusyLabel);
      inflight.verify = true;
      setBusy(modal, true);
      updateModalBusy();
      setStatusMessage(__('Verificando…', TEXT_DOMAIN), { override: true });

      try {
        var responseVerify = await wizard.postJson(
          api.verify,
          payloadVerify,
          signal ? { signal: signal } : undefined
        );
        var data = null;

        try {
          data = await responseVerify.json();
        } catch (jsonError) {
          data = null;
        }

        if (responseVerify.ok && data && data.ok === true) {
          var successLabel = 'Verificado ✓';

          if (data.request_id) {
            successLabel += ' — request: ' + data.request_id;
          }

          setStatusMessage(successLabel, { override: true });
          // TODO(plugin-3-g3d-validate-sign.md §6.2 POST /verify): exponer request_id en payload.

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

          setStatusMessage('Error ✗ — ' + code, { override: true });
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          setStatusMessage('Cancelado', { override: true });

          return;
        }

        setStatusMessage('Error ✗ — NETWORK', { override: true });
      } finally {
        inflight.verify = false;

        if (!inflight.validate) {
          setBusy(modal, false);
        }

        updateModalBusy();
        setBtnBusy(verifyButton, false, verifyLabelIdle, verifyBusyLabel);

        if (hasExpired) {
          setButtonEnabledState(verifyButton, false);
        } else if (initialDisabled) {
          setButtonEnabledState(verifyButton, false);
        } else {
          setButtonEnabledState(verifyButton, true);
        }

        if (!statusMessage) {
          liveOverrideMessage = '';
          announce(message, '');
        }
      }
    }

    function handleVerifyClick(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      runVerifyRequest();
    }

    function applyRulesSummary() {
      if (rulesPromise || !rulesAttempted) {
        setRulesSummaryMessage('');

        return;
      }

      updateLiveMessage();
    }

    async function loadRulesData(snapshotId, productoId, locale) {
      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};
      var endpoint = '';

      if (typeof api.rules === 'string' && api.rules) {
        endpoint = api.rules;
      } else {
        endpoint = '/wp-json/g3d/v1/catalog/rules';
        // TODO(plugin-2-g3d-catalog-rules.md §6): confirmar ruta pública documentada.
      }

      var query = {};

      if (snapshotId) {
        query.snapshot_id = snapshotId;
      }

      if (productoId) {
        query.producto_id = productoId;
      }

      if (locale) {
        query.locale = locale;
      }

      var params = buildQuery(query);
      var fallbackSnapshotId = '';

      if (typeof params.snapshot_id === 'string' && params.snapshot_id) {
        fallbackSnapshotId = params.snapshot_id;
      } else if (snapshotId) {
        fallbackSnapshotId = snapshotId;
      }

      rulesAttempted = false;
      rulesLoaded = false;
      rulesPromise = getJSON(endpoint, params);

      setRulesBusyState(true);
      setRulesSummaryMessage(__('Cargando…', TEXT_DOMAIN));

      try {
        var result = await rulesPromise;
        var response = result && typeof result.res === 'object' ? result.res : null;
        var payload = result && result.data ? result.data : {};
        var rulesList = Array.isArray(payload.rules) ? payload.rules : [];

        rulesData = payload;
        rulesResponse = response;
        wizard.rules = payload;
        rulesCount = rulesList.length;

        var state = { count: rulesCount };

        if (typeof payload.snapshot_id === 'string' && payload.snapshot_id) {
          state.snapshot_id = payload.snapshot_id;
        } else if (fallbackSnapshotId) {
          state.snapshot_id = fallbackSnapshotId;
        }

        if (typeof payload.ver === 'string' && payload.ver) {
          state.ver = payload.ver;
        }

        if (productoId) {
          state.producto_id = productoId;
        }

        if (locale) {
          state.locale = locale;
        }

        lastRules = state;
        wizard.lastRules = state;
        rulesAttempted = true;

        var okFlag = true;

        if (payload && typeof payload.ok === 'boolean') {
          okFlag = payload.ok;
        }

        if (response && response.ok && okFlag && Array.isArray(payload.rules)) {
          rulesLoaded = true;
          setRulesSummaryMessage(formatRulesSummary(payload));
        } else if (response) {
          rulesLoaded = false;
          setRulesSummaryMessage(formatRulesError(response, payload));
        } else {
          rulesLoaded = false;
          setRulesSummaryMessage(formatRulesNetworkError());
        }
      } catch (rulesError) {
        rulesAttempted = true;
        rulesLoaded = false;
        rulesData = null;
        rulesResponse = null;
        wizard.rules = null;

        var fallbackState = { count: 0 };

        if (fallbackSnapshotId) {
          fallbackState.snapshot_id = fallbackSnapshotId;
        }

        if (productoId) {
          fallbackState.producto_id = productoId;
        }

        if (locale) {
          fallbackState.locale = locale;
        }

        lastRules = fallbackState;
        wizard.lastRules = fallbackState;
        setRulesSummaryMessage(formatRulesNetworkError());
      } finally {
        rulesPromise = null;
        setRulesBusyState(false);
        applyRulesSummary();
      }
    }

    function openModal(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      resetNetCtl();

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
      setRulesSummaryMessage('');
      applyRulesSummary();

      loadRulesData(snapshotId, productoId, locale);
    }

    function closeModal(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      overlay.setAttribute('hidden', '');
      document.body.classList.remove('g3d-wizard-open');

      resetNetCtl();
      netCtl = null;

      if (previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
      }

      previousFocus = null;
      setSummaryMessage('');
      setStatusMessage('');
      resetTtlState();
      setRulesSummaryMessage('');

      if (rulesContainer) {
        setRulesContainerText('');
      }

      resetRulesSelection();

      if (cta) {
        setBtnBusy(cta, false, ctaLabelIdle, ctaBusyLabel);
        setButtonEnabledState(cta, true);
      }

      if (verifyButton) {
        setBtnBusy(verifyButton, false, verifyLabelIdle, verifyBusyLabel);
        setButtonEnabledState(verifyButton, true);
      }

      inflight.validate = false;
      inflight.verify = false;
      setBusy(modal, false);
      announce(message, '');
      liveOverrideMessage = '';
      updateModalBusy();
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

    if (tablist) {
      var tabNodes = getTabs(root);
      var panelNodes = getPanels(root);

      if (tabNodes.length && panelNodes.length) {
        (function initTabs() {
          var selected = null;

          if (Array.prototype.find) {
            selected = Array.prototype.find.call(tabNodes, function (t) {
              return t && t.getAttribute('aria-selected') === 'true';
            });
          } else {
            for (var idx = 0; idx < tabNodes.length; idx += 1) {
              var candidate = tabNodes[idx];

              if (candidate && candidate.getAttribute('aria-selected') === 'true') {
                selected = candidate;
                break;
              }
            }
          }

          var target = selected;

          if (!target || isTabDisabled(target)) {
            target = null;

            for (var i = 0; i < tabNodes.length; i += 1) {
              var fallback = tabNodes[i];

              if (fallback && !isTabDisabled(fallback)) {
                target = fallback;
                break;
              }
            }
          }

          if (!target && tabNodes.length) {
            target = tabNodes[0];
          }

          if (target) {
            activateTab(target, root);
          }
        })();

        Array.prototype.forEach.call(tabNodes, function (tab) {
          if (!tab) {
            return;
          }

          tab.addEventListener('click', function (event) {
            if (event && typeof event.preventDefault === 'function') {
              event.preventDefault();
            }

            activateTab(tab, root);
          });

          tab.addEventListener('keydown', function (event) {
            onTabKeydown(event, root);
          });
        });
      }
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
