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

  global.G3DWIZARD.postJson = async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (global.G3DWIZARD && global.G3DWIZARD.nonce) || '',
      },
      body: JSON.stringify(body || {}),
    });

    return res;
  };

  global.G3DWIZARD.getJSON = async function getJSON(url, params) {
    const qs =
      params && Object.keys(params).length
        ? '?' + new URLSearchParams(params).toString()
        : '';
    const res = await fetch(url + qs, { method: 'GET' });

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
    var lastValidation = null;
    var shouldAutoAudit = modal && modal.getAttribute('data-auto-audit') === '1';
    var previousFocus = null;
    var summaryMessage = '';
    var statusMessage = '';
    var rulesSummaryMessage = '';
    var lastRules = null;

    function setText(element, value) {
      if (!element) {
        return;
      }

      element.textContent = value;
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

    function setStatusMessage(value) {
      statusMessage = value || '';
      updateLiveMessage();
    }

    function formatRulesSummary(data) {
      var payload = data && typeof data === 'object' ? data : {};
      var sections =
        payload.rules && typeof payload.rules === 'object'
          ? Object.keys(payload.rules)
          : [];
      var count = sections.length;
      var summaryParts = ['Reglas: ' + String(count)];

      ['id', 'ver', 'schema_version', 'producto_id', 'published_at', 'published_by'].forEach(
        function (key) {
          var value = payload[key];

          if (typeof value === 'string' && value) {
            summaryParts.push(key + '=' + value);
          }
        }
      );

      if (Array.isArray(payload.locales) && payload.locales.length) {
        summaryParts.push('locales=' + payload.locales.join(','));
      }

      return summaryParts.join(' · ');
    }

    function formatRulesError(error) {
      var detailParts = [];

      if (error && typeof error.status === 'number' && error.status > 0) {
        detailParts.push('HTTP ' + String(error.status));
      }

      if (error && error.code) {
        detailParts.push(String(error.code));
      } else if (
        error &&
        error.payload &&
        typeof error.payload === 'object' &&
        error.payload.reason_key
      ) {
        detailParts.push(String(error.payload.reason_key));
      }

      if (detailParts.length) {
        return 'Reglas: error (' + detailParts.join(' | ') + ')';
      }

      return 'Reglas: error';
    }

    async function fetchRules(params) {
      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};
      var url = typeof api.rules === 'string' && api.rules ? api.rules : '';

      if (!url) {
        var endpointError = new Error('missing_endpoint');
        endpointError.code = 'missing_endpoint';

        return Promise.reject(endpointError);
      }

      return global.G3DWIZARD.getJSON(url, params || {});
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

    function setTabFocusState(target) {
      tabElements.forEach(function (item) {
        item.setAttribute('tabindex', item === target ? '0' : '-1');
      });
    }

    function focusTab(tab) {
      if (!tab || tabElements.indexOf(tab) === -1 || isTabDisabled(tab)) {
        return;
      }

      setTabFocusState(tab);

      if (typeof tab.focus === 'function') {
        tab.focus();
      }
    }

    function activateTab(tab) {
      if (!tab || tabElements.indexOf(tab) === -1 || isTabDisabled(tab)) {
        return;
      }

      var controls = tab.getAttribute('aria-controls');

      tabElements.forEach(function (item) {
        var isActive = item === tab;
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      focusTab(tab);

      panelElements.forEach(function (panel) {
        if (!panel || !panel.id) {
          return;
        }

        panel.hidden = panel.id !== controls;
      });
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

      var wizard = global.G3DWIZARD || {};
      var api = wizard.api || {};

      if (!api.validateSign || typeof wizard.postJson !== 'function') {
        setStatusMessage(__('ERROR — endpoint no disponible', TEXT_DOMAIN));
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

      var defaultLabel = cta.textContent;
      cta.disabled = true;
      cta.setAttribute('aria-busy', 'true');
      setText(cta, __('Enviando…', TEXT_DOMAIN));

      if (message) {
        message.setAttribute('aria-busy', 'true');
        setStatusMessage('');
      }

      try {
        var response = await wizard.postJson(api.validateSign, payload);
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
            code = __('HTTP ', TEXT_DOMAIN) + response.status;
          }

          setStatusMessage(__('ERROR — ', TEXT_DOMAIN) + code);
        }
      } catch (error) {
        setStatusMessage(__('ERROR — NETWORK', TEXT_DOMAIN));
      } finally {
        cta.disabled = false;
        cta.removeAttribute('aria-busy');
        setText(cta, defaultLabel);

        if (message) {
          message.removeAttribute('aria-busy');
        }
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
        setStatusMessage(__('ERROR — endpoint no disponible', TEXT_DOMAIN));
        return;
      }

      if (!lastValidation || !lastValidation.sku_hash || !lastValidation.sku_signature) {
        setStatusMessage(__('ERROR — Primero valida y firma', TEXT_DOMAIN));
        return;
      }

      var payload = {
        sku_hash: lastValidation.sku_hash,
        sku_signature: lastValidation.sku_signature,
        snapshot_id: lastValidation.snapshot_id || '',
      };

      var defaultLabel = verifyButton.textContent;
      verifyButton.disabled = true;
      verifyButton.setAttribute('aria-busy', 'true');
      setText(verifyButton, __('Verificando…', TEXT_DOMAIN));

      if (message) {
        message.setAttribute('aria-busy', 'true');
        setStatusMessage('');
      }

      try {
        var response = await wizard.postJson(api.verify, payload);
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
            code = __('HTTP ', TEXT_DOMAIN) + response.status;
          }

          if (!code) {
            code = __('ERROR', TEXT_DOMAIN);
          }

          setStatusMessage(__('ERROR — ', TEXT_DOMAIN) + code);
        }
      } catch (error) {
        setStatusMessage(__('ERROR — NETWORK', TEXT_DOMAIN));
      } finally {
        verifyButton.disabled = false;
        verifyButton.removeAttribute('aria-busy');
        setText(verifyButton, defaultLabel);

        if (message) {
          message.removeAttribute('aria-busy');
        }
      }
    }

    function handleVerifyClick(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      runVerifyRequest();
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
      var productoId = modalData.productoId;
      var locale = modalData.locale || wizard.locale || '';

      setStatusMessage('');
      setSummaryMessage('');

      var params = {};

      if (message) {
        message.setAttribute('aria-busy', 'true');
      }

      if (!productoId) {
        lastRules = null;
        setRulesSummaryMessage('Reglas: error (missing producto_id)');

        if (message) {
          message.removeAttribute('aria-busy');
        }

        return;
      }

      params.producto_id = productoId;

      if (locale) {
        params.locale = locale;
      }

      if (rulesContainer) {
        rulesContainer.setAttribute('aria-busy', 'true');
      }

      setRulesSummaryMessage('Reglas: cargando…');

      fetchRules(params)
        .then(function (data) {
          lastRules = data;
          setRulesSummaryMessage(formatRulesSummary(data));
        })
        .catch(function (error) {
          lastRules = null;
          setRulesSummaryMessage(formatRulesError(error));
          // TODO(Plugin 4 §controles de recarga).
        })
        .finally(function () {
          if (message) {
            message.removeAttribute('aria-busy');
          }

          if (rulesContainer) {
            rulesContainer.removeAttribute('aria-busy');
          }
        });
    }

    function closeModal(event) {
      if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
      }

      overlay.setAttribute('hidden', '');
      document.body.classList.remove('g3d-wizard-open');

      if (previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus();
      }

      previousFocus = null;
      setSummaryMessage('');
      setStatusMessage('');
      setRulesSummaryMessage('');

      if (message) {
        message.removeAttribute('aria-busy');
      }

      if (rulesContainer) {
        rulesContainer.removeAttribute('aria-busy');
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
        activateTab(initialTab);
      }

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

          if (key === 'ArrowRight' || key === 'ArrowDown') {
            event.preventDefault();
            var next = getNextEnabledTab(tab);

            if (next) {
              focusTab(next);
            }
            return;
          }

          if (key === 'ArrowLeft' || key === 'ArrowUp') {
            event.preventDefault();
            var previous = getPreviousEnabledTab(tab);

            if (previous) {
              focusTab(previous);
            }
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
