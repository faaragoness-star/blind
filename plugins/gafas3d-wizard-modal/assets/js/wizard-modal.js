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

  function setBusy(el, busy) {
    if (!el) {
      return;
    }

    el.setAttribute('aria-busy', busy ? 'true' : 'false');
  }

  function setText(el, s) {
    if (!el) {
      return;
    }

    el.textContent = s;
  }

  function disableBtn(btn, on, labelBusy) {
    if (!btn) {
      return function () {};
    }

    const previous = btn.textContent;
    btn.disabled = !!on;
    btn.setAttribute('aria-disabled', on ? 'true' : 'false');

    if (on && labelBusy) {
      setText(btn, labelBusy);
    }

    return function () {
      btn.disabled = false;
      btn.setAttribute('aria-disabled', 'false');
      setText(btn, previous);
    };
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
    const tablist = root.querySelector('[role="tablist"]');
    const tabs = tablist ? tablist.querySelectorAll('[role="tab"]') : [];
    const panels = root.querySelectorAll('[role="tabpanel"]');
    var tabElements = Array.prototype.slice.call(tabs);
    var panelElements = Array.prototype.slice.call(panels);
    var panelById = {};
    var lastValidation = null;
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
      var payload =
        rulesPayload && typeof rulesPayload === 'object' ? rulesPayload : null;

      if (!payload) {
        setButtonEnabledState(cta, false);
        setButtonEnabledState(verifyButton, false);

        return;
      }

      var missing = [];

      if (typeof payload.id !== 'string' || payload.id === '') {
        missing.push('id');
      }

      if (typeof payload.producto_id !== 'string' || payload.producto_id === '') {
        missing.push('producto_id');
      }

      if (!payload.rules || typeof payload.rules !== 'object') {
        missing.push('rules');
      }

      if (missing.length) {
        setButtonEnabledState(cta, false);
        setButtonEnabledState(verifyButton, false);
        setRulesSummaryMessage(
          'Reglas ERROR — Faltan campos requeridos: ' + missing.join(', ')
        );
        // TODO(docs/plugin-2-g3d-catalog-rules.md §6 APIs / Contratos (lectura)):
        // confirmar parámetros requeridos para habilitar CTA.

        return;
      }

      if (Object.prototype.hasOwnProperty.call(payload, 'ok')) {
        if (payload.ok === true) {
          setButtonEnabledState(cta, true);
          setButtonEnabledState(verifyButton, true);
        } else {
          setButtonEnabledState(cta, false);
          setButtonEnabledState(verifyButton, false);
        }

        return;
      }

      // TODO(docs/plugin-4-gafas3d-wizard-modal.md §5.6 CTA Add to Cart):
      // confirmar gating exacto para CTAs ante reglas cargadas.
      setButtonEnabledState(cta, true);
      setButtonEnabledState(verifyButton, true);
    }

    function setStatusMessage(value) {
      statusMessage = value || '';
      updateLiveMessage();
    }

    function formatRulesSummary(data, fallbackSnapshotId) {
      var payload = data && typeof data === 'object' ? data : {};
      var details = [];
      var snapshotValue = '';

      if (typeof payload.id === 'string' && payload.id) {
        snapshotValue = payload.id;
      } else if (
        typeof payload.snapshot_id === 'string' &&
        payload.snapshot_id
      ) {
        snapshotValue = payload.snapshot_id;
      } else if (typeof fallbackSnapshotId === 'string' && fallbackSnapshotId) {
        snapshotValue = fallbackSnapshotId;
      }

      if (snapshotValue) {
        details.push('snapshot: ' + snapshotValue);
      }

      if (typeof payload.ver === 'string' && payload.ver) {
        details.push('version: ' + payload.ver);
      }

      var total = 0;

      if (Array.isArray(payload.rules)) {
        total = payload.rules.length;
      } else if (payload.rules && typeof payload.rules === 'object') {
        total = Object.keys(payload.rules).length;
      }

      details.push('total: ' + String(total));

      return details.length
        ? 'Reglas OK — ' + details.join(' | ')
        : 'Reglas OK';
    }

    function formatRulesError(status, payload) {
      var detail = '';

      if (payload && typeof payload === 'object') {
        if (payload.reason_key) {
          detail = String(payload.reason_key);
        } else if (payload.code) {
          detail = String(payload.code);
        }
      }

      if (!detail && typeof status === 'number' && status > 0) {
        detail = 'HTTP ' + String(status);
      }

      if (!detail) {
        detail = 'desconocido';
      }

      return 'Reglas ERROR — ' + detail;
    }

    function formatRulesNetworkError() {
      return 'Reglas ERROR — NETWORK';
    }

    function getRulesEndpoint() {
      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (typeof api.rules === 'string' && api.rules) {
        return api.rules;
      }

      return '/wp-json/g3d/v1/catalog/rules';
    }

    function blockButton(btn, busyLabel) {
      if (!btn) {
        return function () {};
      }

      var wasDisabled = btn.disabled;
      var previousText = btn.textContent;

      if (!wasDisabled) {
        return disableBtn(btn, true, busyLabel);
      }

      return function () {
        setText(btn, previousText);
      };
    }

    function blockRulesCtas() {
      var restoreFns = [];

      var restoreCta = blockButton(cta, __('Cargando…', TEXT_DOMAIN));
      var restoreVerify = blockButton(
        verifyButton,
        __('Cargando…', TEXT_DOMAIN)
      );

      if (typeof restoreCta === 'function') {
        restoreFns.push(restoreCta);
      }

      if (typeof restoreVerify === 'function') {
        restoreFns.push(restoreVerify);
      }

      return function () {
        restoreFns.forEach(function (fn) {
          if (typeof fn === 'function') {
            fn();
          }
        });
      };
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

    function getFirstEnabledTab() {
      for (var i = 0; i < tabElements.length; i += 1) {
        if (!isTabDisabled(tabElements[i])) {
          return tabElements[i];
        }
      }

      return null;
    }

    function getLastEnabledTab() {
      for (var i = tabElements.length - 1; i >= 0; i -= 1) {
        if (!isTabDisabled(tabElements[i])) {
          return tabElements[i];
        }
      }

      return null;
    }

    function getNextEnabledTab(current) {
      if (!tabElements.length) {
        return null;
      }

      var startIndex = tabElements.indexOf(current);

      if (startIndex === -1) {
        startIndex = -1;
      }

      for (var offset = 1; offset <= tabElements.length; offset += 1) {
        var nextIndex = (startIndex + offset) % tabElements.length;
        var candidate = tabElements[nextIndex];

        if (!isTabDisabled(candidate)) {
          return candidate;
        }
      }

      return null;
    }

    function getPreviousEnabledTab(current) {
      if (!tabElements.length) {
        return null;
      }

      var startIndex = tabElements.indexOf(current);

      if (startIndex === -1) {
        startIndex = tabElements.length;
      }

      for (var offset = 1; offset <= tabElements.length; offset += 1) {
        var nextIndex = (startIndex - offset + tabElements.length) % tabElements.length;
        var candidate = tabElements[nextIndex];

        if (!isTabDisabled(candidate)) {
          return candidate;
        }
      }

      return null;
    }

    var selectedTab = null;
    var rovingTab = null;

    panelElements.forEach(function (panel) {
      if (!panel || !panel.id) {
        return;
      }

      panelById[panel.id] = panel;
    });

    function setFocusableTab(target) {
      tabElements.forEach(function (item) {
        item.setAttribute('tabindex', item === target ? '0' : '-1');
      });

      rovingTab = target;
    }

    function focusTab(tab) {
      if (!tab || tabElements.indexOf(tab) === -1 || isTabDisabled(tab)) {
        return;
      }

      setFocusableTab(tab);

      if (typeof tab.focus === 'function') {
        tab.focus();
      }
    }

    function setActiveTab(tab) {
      if (!tab || tabElements.indexOf(tab) === -1 || isTabDisabled(tab)) {
        return;
      }

      var controls = tab.getAttribute('aria-controls');

      if (!controls || !panelById[controls]) {
        return;
      }

      tabElements.forEach(function (item) {
        var isActive = item === tab;
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      selectedTab = tab;
      focusTab(tab);

      panelElements.forEach(function (panel) {
        if (!panel || !panel.id) {
          return;
        }

        panel.hidden = panel.id !== controls;
      });
    }

    function focusNext(direction) {
      if (!tabElements.length) {
        return;
      }

      var current = document.activeElement;

      if (!current || tabElements.indexOf(current) === -1) {
        current = rovingTab || selectedTab || getFirstEnabledTab();
      }

      if (!current) {
        return;
      }

      var target = null;

      if (direction > 0) {
        target = getNextEnabledTab(current) || current;
      } else if (direction < 0) {
        target = getPreviousEnabledTab(current) || current;
      }

      if (target && target !== current) {
        focusTab(target);
      }
    }

    function collectState(scope) {
      var out = {};

      if (!scope || typeof scope.querySelectorAll !== 'function') {
        return out;
      }

      var nodes = scope.querySelectorAll('[data-g3d-state-key]');

      Array.prototype.forEach.call(nodes, function (el) {
        var key = el.getAttribute('data-g3d-state-key');

        if (!key) {
          return;
        }

        var v;
        var tag = el.tagName.toLowerCase();
        var type = (el.getAttribute('type') || '').toLowerCase();

        if (tag === 'input' && type === 'checkbox') {
          v = !!el.checked;
        } else if (
          tag === 'input' &&
          (type === 'number' || el.dataset.stateType === 'number')
        ) {
          var n = Number(el.value);

          if (!Number.isNaN(n)) {
            v = n;
          }
        } else if (tag === 'input' && type === 'radio') {
          if (el.checked) {
            v = el.value;
          }
        } else if (tag === 'select') {
          v = el.multiple
            ? Array.from(el.selectedOptions).map(function (o) {
                return o.value;
              })
            : el.value;
        } else {
          v = el.value != null ? el.value : el.textContent;
        }

        if (v !== undefined && v !== null && key.length) {
          out[key] = v;
        }
      });

      return out;
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

      if (cta.disabled) {
        return;
      }

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.validateSign || typeof wizard.postJson !== 'function') {
        setStatusMessage('ERROR — endpoint no disponible');
        return;
      }

      lastValidation = null;

      var modalData = getModalData();
      var snapshotId = modalData.snapshotId;
      var productoId = modalData.productoId;
      var locale = modalData.locale;

      if (!locale && wizard.locale) {
        locale = wizard.locale;
      }

      var payload = {
        state: collectState(modal),
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
      const restoreButton = disableBtn(cta, true, __('Enviando…', TEXT_DOMAIN));

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

          lastValidation = {
            sku_hash: data && data.sku_hash ? data.sku_hash : '',
            sku_signature:
              data && typeof data.sku_signature === 'string'
                ? data.sku_signature
                : '',
            snapshot_id: snapshotValue || '',
          };

          if (shouldAutoAudit) {
            audit('validate_sign_success', {
              snapshot_id: lastValidation.snapshot_id || '',
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

        setStatusMessage('ERROR — NETWORK');
      } finally {
        restoreButton();
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

      if (!lastValidation || !lastValidation.sku_hash || !lastValidation.sku_signature) {
        setStatusMessage('ERROR — Primero valida y firma');
        return;
      }

      var payload = {
        sku_hash: lastValidation.sku_hash,
        sku_signature: lastValidation.sku_signature,
        snapshot_id: lastValidation.snapshot_id || '',
      };

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;
      const restoreVerify = disableBtn(
        verifyButton,
        true,
        __('Verificando…', TEXT_DOMAIN)
      );

      setBusy(message, true);
      setStatusMessage('');

      try {
        var response = await wizard.postJson(api.verify, payload, {
          signal: signal,
        });
        var data = null;

        try {
          data = await response.json();
        } catch (jsonError) {
          data = null;
        }

        if (response.ok && data && data.ok === true) {
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
              snapshot_id: (lastValidation && lastValidation.snapshot_id) || '',
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

          if (!code && response.status) {
            code = 'HTTP ' + response.status;
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
        restoreVerify();
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
      var releaseButtons = blockRulesCtas();

      setBusy(message, true);

      if (rulesContainer) {
        setBusy(rulesContainer, true);
      }

      if (!productoId) {
        lastRules = null;
        setRulesSummaryMessage('Reglas ERROR — missing producto_id');
        // TODO(docs/plugin-2-g3d-catalog-rules.md §6 APIs / Contratos (lectura))
        // confirmar parámetros requeridos.

        releaseButtons();
        setBusy(message, false);

        if (rulesContainer) {
          setBusy(rulesContainer, false);
        }

        gateCtasByRules(lastRules);

        return;
      }

      var params = { producto_id: productoId };

      if (snapshotId) {
        params.snapshot_id = snapshotId;
      }

      if (locale) {
        params.locale = locale;
      }

      setRulesSummaryMessage(__('Reglas: cargando…', TEXT_DOMAIN));

      var url = getRulesEndpoint();

      if (!url) {
        lastRules = null;
        setRulesSummaryMessage('Reglas ERROR — missing endpoint');
        releaseButtons();
        setBusy(message, false);

        if (rulesContainer) {
          setBusy(rulesContainer, false);
        }

        gateCtasByRules(lastRules);

        return;
      }

      const controller = startAbortableRequest();
      var signal = controller ? controller.signal : undefined;

      try {
        var response = await global.G3DWIZARD.getJson(url, params, {
          signal: signal,
        });

        if (response && response.ok) {
          var data = null;

          try {
            data = await response.json();
          } catch (jsonError) {
            data = null;
          }

          lastRules = data;
          setRulesSummaryMessage(formatRulesSummary(data || {}, snapshotId));
        } else {
          var errorPayload = null;

          try {
            errorPayload = response ? await response.json() : null;
          } catch (parseError) {
            errorPayload = null;
          }

          lastRules = null;
          setRulesSummaryMessage(
            formatRulesError(response ? response.status : 0, errorPayload)
          );
        }
      } catch (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        lastRules = null;
        setRulesSummaryMessage(formatRulesNetworkError());
      } finally {
        releaseButtons();

        var shouldReleaseBusy = !currentAbort || currentAbort === controller;

        if (shouldReleaseBusy) {
          setBusy(message, false);

          if (rulesContainer) {
            setBusy(rulesContainer, false);
          }
        }

        clearAbort(controller);
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

    if (tabElements.length && panelElements.length) {
      var initialTab = null;

      if (Array.prototype.find) {
        initialTab = Array.prototype.find.call(tabs, function (candidate) {
          return (
            candidate &&
            candidate.getAttribute &&
            candidate.getAttribute('aria-selected') === 'true' &&
            !isTabDisabled(candidate)
          );
        });
      }

      if (!initialTab) {
        initialTab = getFirstEnabledTab() || (tabs.length ? tabs[0] : null);
      }

      if (initialTab) {
        setActiveTab(initialTab);
      }

      Array.prototype.forEach.call(tabs, function (tab) {
        tab.addEventListener('click', function (event) {
          if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
          }

          if (isTabDisabled(tab)) {
            return;
          }

          setActiveTab(tab);
        });

        tab.addEventListener('keydown', function (event) {
          if (!event) {
            return;
          }

          var key = event.key;

          if (key === 'ArrowRight') {
            event.preventDefault();
            focusNext(1);
            return;
          }

          if (key === 'ArrowLeft') {
            event.preventDefault();
            focusNext(-1);
            return;
          }

          if (key === 'Home') {
            event.preventDefault();

            var first = getFirstEnabledTab();

            if (first) {
              focusTab(first);
            }

            return;
          }

          if (key === 'End') {
            event.preventDefault();

            var last = getLastEnabledTab();

            if (last) {
              focusTab(last);
            }

            return;
          }

          if (key === 'Enter' || key === ' ' || key === 'Space') {
            event.preventDefault();

            if (isTabDisabled(tab)) {
              return;
            }

            setActiveTab(tab);
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
