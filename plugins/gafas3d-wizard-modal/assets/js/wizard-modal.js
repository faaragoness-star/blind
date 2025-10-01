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
  global.G3DWIZARD.lastRules = global.G3DWIZARD.lastRules || null;
  global.G3DWIZARD.rules = global.G3DWIZARD.rules || null;

  let rulesPromise = null;
  let rulesData = null;

  function setBusy(el, busy) {
    if (!el) {
      return;
    }

    el.setAttribute('aria-busy', busy ? 'true' : 'false');

    if (el.classList && typeof el.classList.toggle === 'function') {
      el.classList.toggle('is-busy', !!busy);
    }
  }

  function setDisabled(el, on) {
    if (!el) {
      return;
    }

    const disabled = !!on;

    el.disabled = disabled;
    el.setAttribute('aria-disabled', disabled ? 'true' : 'false');
  }

  function say(msgEl, text) {
    if (!msgEl) {
      return;
    }

    msgEl.textContent = text || '';
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
      return {};
    }

    var query = buildQuery(params);
    var queryString = Object.keys(query).length
      ? '?' + new URLSearchParams(query).toString()
      : '';
    var response = await fetch(url + queryString, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    return response
      .json()
      .catch(function () {
        return {};
      });
  }

  global.G3DWIZARD.getJSON = getJSON;
  global.G3DWIZARD.getJson = getJSON;

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
    var summaryContainer = overlay.querySelector('.g3d-wizard-modal__summary');
    var rulesContainer = modal
      ? modal.querySelector('[data-g3d-wizard-rules]')
      : null;
    var tablist = root.querySelector('[role="tablist"]');
    const tabs = tablist ? tablist.querySelectorAll('[role="tab"]') : [];
    const panels = root.querySelectorAll('[role="tabpanel"]');
    const panelById = {};
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
    let currentAbort = null;
    var autoVerifyEnabled = false;
    var modalBusyState = { validate: false, verify: false };
    var liveOverrideMessage = '';

    function updateModalBusy() {
      if (!modal) {
        return;
      }

      var isBusy = modalBusyState.validate || modalBusyState.verify;

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

      if (liveOverrideMessage) {
        say(message, liveOverrideMessage);

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

      say(message, parts.join(' · '));
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

    function showRulesLoading() {
      setRulesContainerText(__('Reglas: cargando…', TEXT_DOMAIN));
    }

    function showRulesError(messageText) {
      setRulesContainerText(messageText || formatRulesError());
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

    function setStatusMessage(value) {
      statusMessage = value || '';
      liveOverrideMessage = '';
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

    function focusTab(tabEl) {
      if (!tabEl || isTabDisabled(tabEl)) {
        return;
      }

      if (typeof tabEl.focus === 'function') {
        tabEl.focus();
      }
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

      focusTab(tabEl);

      Array.prototype.forEach.call(panels, function (p) {
        var isActivePanel = p.id === target;

        p.hidden = !isActivePanel;
      });
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

      var step = dir === -1 ? -1 : 1;
      var next = index;

      for (var attempt = 0; attempt < len; attempt += 1) {
        next = (next + step + len) % len;
        var candidate = tabs[next];

        if (!candidate || isTabDisabled(candidate)) {
          continue;
        }

        activateTab(candidate);

        return candidate;
      }
    }

    function focusPrev(current) {
      focusNext(current, -1);
    }

    function focusFirst() {
      var first = getFirstEnabledTab() || (tabs.length ? tabs[0] : null);

      if (first) {
        activateTab(first);
      }
    }

    function focusLast() {
      var last = getLastEnabledTab() || (tabs.length ? tabs[tabs.length - 1] : null);

      if (last) {
        activateTab(last);
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

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;
      var ctaBusyLabel = __('Enviando…', TEXT_DOMAIN);
      var ctaPrevLabel = cta.getAttribute('data-label-prev');

      if (ctaPrevLabel === null) {
        ctaPrevLabel = cta.textContent || '';
        cta.setAttribute('data-label-prev', ctaPrevLabel);
      }

      cta.textContent = ctaBusyLabel;
      modalBusyState.validate = true;
      updateModalBusy();
      setDisabled(cta, true);
      setStatusMessage('');
      liveOverrideMessage = ctaBusyLabel;
      say(message, ctaBusyLabel);
      setBusy(message, true);

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
          var signatureLabel = signature || '-';
          var successMessage =
            'OK — hash: ' +
            hashLabel +
            ' | expira: ' +
            expiresLabel +
            ' | sig: ' +
            signatureLabel;

          setStatusMessage(successMessage);

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

          setStatusMessage('ERROR — ' + code);
          say(message, 'ERROR — ' + code);
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
        say(message, 'ERROR — NETWORK');
      } finally {
        var prevLabel = cta.getAttribute('data-label-prev');

        if (prevLabel !== null) {
          cta.textContent = prevLabel;
          cta.removeAttribute('data-label-prev');
        }

        modalBusyState.validate = false;
        updateModalBusy();
        setDisabled(cta, false);

        if (!statusMessage) {
          liveOverrideMessage = '';
          say(message, '');
        }
        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);
        }

        clearAbort(controller);
      }
    }

    async function runVerifyRequest(options) {
      if (!verifyButton) {
        return;
      }

      var opts = options && typeof options === 'object' ? options : {};
      var force = opts.force === true;

      if (!force && verifyButton.disabled) {
        return;
      }

      if (hasExpired) {
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
      var initialDisabled = verifyButton.disabled;
      var verifyBusyLabel = __('Verificando…', TEXT_DOMAIN);
      var verifyPrevLabel = verifyButton.getAttribute('data-label-prev');

      if (verifyPrevLabel === null) {
        verifyPrevLabel = verifyButton.textContent || '';
        verifyButton.setAttribute('data-label-prev', verifyPrevLabel);
      }

      verifyButton.textContent = verifyBusyLabel;
      modalBusyState.verify = true;
      updateModalBusy();
      setDisabled(verifyButton, true);
      setStatusMessage('');
      liveOverrideMessage = verifyBusyLabel;
      say(message, verifyBusyLabel);
      setBusy(message, true);

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
          say(message, 'ERROR — ' + code);
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        setStatusMessage('ERROR — NETWORK');
        say(message, 'ERROR — NETWORK');
      } finally {
        var prevVerifyLabel = verifyButton.getAttribute('data-label-prev');

        if (prevVerifyLabel !== null) {
          verifyButton.textContent = prevVerifyLabel;
          verifyButton.removeAttribute('data-label-prev');
        }

        modalBusyState.verify = false;
        updateModalBusy();
        setDisabled(verifyButton, false);

        if (hasExpired) {
          setButtonEnabledState(verifyButton, false);
        } else if (initialDisabled) {
          setButtonEnabledState(verifyButton, false);
        } else {
          setButtonEnabledState(verifyButton, true);
        }

        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);
        }

        if (!statusMessage) {
          liveOverrideMessage = '';
          say(message, '');
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

    function applyRulesSummary() {
      if (!rulesAttempted || rulesPromise) {
        setRulesSummaryMessage('');

        return;
      }

      if (rulesData && Array.isArray(rulesData.rules)) {
        setRulesSummaryMessage('Reglas cargadas: ' + String(rulesData.rules.length));

        return;
      }

      setRulesSummaryMessage('Reglas no disponibles');
    }

    async function loadRulesData(snapshotId, productoId, locale) {
      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};
      var endpoint = '';

      if (!message) {
        // TODO(Plugin 4 §markup hooks): falta .g3d-wizard-modal__msg en el DOM.
      }

      if (typeof api.rules === 'string' && api.rules) {
        endpoint = api.rules;
      } else {
        endpoint = '/wp-json/g3d/v1/catalog/rules';
        // TODO(Capa 2 §endpoint): confirmar ruta pública documentada.
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

      if (rulesData) {
        var cachedPayload = rulesData || {};

        wizard.rules = cachedPayload;
        rulesCount = Array.isArray(cachedPayload.rules)
          ? cachedPayload.rules.length
          : 0;

        var cachedState = { count: rulesCount };

        if (snapshotId) {
          cachedState.snapshot_id = snapshotId;
        }

        if (productoId) {
          cachedState.producto_id = productoId;
        }

        if (locale) {
          cachedState.locale = locale;
        }

        lastRules = cachedState;
        wizard.lastRules = cachedState;
        rulesLoaded = Array.isArray(cachedPayload.rules);
        rulesAttempted = true;
        applyRulesSummary();

        return;
      }

      if (!rulesPromise) {
        var params = query;
        var rulesUrl = endpoint;

        rulesPromise = getJSON(rulesUrl, params)
          .then(function (res) {
            rulesData = res || {};

            return rulesData;
          })
          .catch(function (error) {
            rulesData = null;

            throw error;
          })
          .finally(function () {
            rulesPromise = null;
          });
      }

      rulesAttempted = false;
      rulesLoaded = false;

      if (message) {
        setBusy(message, true);
      }

      try {
        var payload = await rulesPromise;
        var resolvedPayload = payload || {};
        var isArray = Array.isArray(resolvedPayload.rules);

        wizard.rules = resolvedPayload;
        rulesCount = isArray ? resolvedPayload.rules.length : 0;

        var state = { count: rulesCount };

        if (snapshotId) {
          state.snapshot_id = snapshotId;
        }

        if (productoId) {
          state.producto_id = productoId;
        }

        if (locale) {
          state.locale = locale;
        }

        lastRules = state;
        wizard.lastRules = state;
        rulesLoaded = isArray;
        rulesAttempted = true;
        rulesData = resolvedPayload;
      } catch (rulesError) {
        rulesData = null;
        rulesLoaded = false;
        rulesAttempted = true;

        var networkState = { count: 0 };

        if (snapshotId) {
          networkState.snapshot_id = snapshotId;
        }

        if (productoId) {
          networkState.producto_id = productoId;
        }

        if (locale) {
          networkState.locale = locale;
        }

        lastRules = networkState;
        wizard.lastRules = networkState;
      } finally {
        if (message) {
          setBusy(message, false);
        }

        applyRulesSummary();
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
      applyRulesSummary();

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
      resetTtlState();
      setRulesSummaryMessage('');

      setBusy(message, false);

      if (rulesContainer) {
        setBusy(rulesContainer, false);
        setRulesContainerText('');
      }

      resetRulesSelection();

      if (cta) {
        var prevCtaLabel = cta.getAttribute('data-label-prev');

        if (prevCtaLabel !== null) {
          cta.textContent = prevCtaLabel;
          cta.removeAttribute('data-label-prev');
        }

        setDisabled(cta, false);
        setButtonEnabledState(cta, true);
      }

      if (verifyButton) {
        var prevVerifyLabelClose = verifyButton.getAttribute('data-label-prev');

        if (prevVerifyLabelClose !== null) {
          verifyButton.textContent = prevVerifyLabelClose;
          verifyButton.removeAttribute('data-label-prev');
        }

        setDisabled(verifyButton, false);
        setButtonEnabledState(verifyButton, true);
      }

      modalBusyState.validate = false;
      modalBusyState.verify = false;
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
            focusPrev(tab);
            return;
          }

          if (key === 'Home') {
            event.preventDefault();
            focusFirst();
            return;
          }

          if (key === 'End') {
            event.preventDefault();
            focusLast();
            return;
          }

          if (key === 'Enter' || key === ' ' || key === 'Space' || key === 'Spacebar') {
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
