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

  var rulesURL =
    (global.G3DWIZARD &&
      global.G3DWIZARD.api &&
      global.G3DWIZARD.api.rules) ||
    '/wp-json/g3d/v1/catalog/rules'; // TODO(plugin-2-g3d-catalog-rules.md §6): confirmar endpoint público exacto.

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
    const res = await fetch(url + qs, {
      headers: {
        'X-WP-Nonce': (global.G3DWIZARD && global.G3DWIZARD.nonce) || '',
      },
    });

    return res.ok ? res.json() : {};
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
    var rulesLoaded = false;

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

    function setStatusMessage(value) {
      statusMessage = value || '';
      updateLiveMessage();
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

    function buildRulesParams(productoId, snapshotId, locale) {
      var params = {};

      // TODO(docs/plugin-2-g3d-catalog-rules.md §6): confirmar lista definitiva de parámetros públicos.

      if (productoId) {
        params.producto_id = productoId;
      }

      if (snapshotId) {
        params.snapshot_id = snapshotId;
      }

      if (locale) {
        params.locale = locale;
      }

      return params;
    }

    async function loadRulesOnce(params) {
      if (rulesLoaded) {
        return;
      }

      rulesLoaded = true;

      var headers = {
        'X-WP-Nonce': (global.G3DWIZARD && global.G3DWIZARD.nonce) || '',
      };

      var query = '';

      if (params && typeof params === 'object' && Object.keys(params).length) {
        query = '?' + new URLSearchParams(params).toString();
      }

      var ctaWasDisabled = cta ? cta.disabled : false;

      if (cta) {
        cta.disabled = true;
      }

      setSummaryMessage(__('Cargando reglas…', TEXT_DOMAIN));

      try {
        var res = await fetch(rulesURL + query, { headers: headers });
        var data = {};

        try {
          data = await res.json();
        } catch (error) {
          data = {};
        }

        if (res.ok && data) {
          var n = Array.isArray(data.rules) ? data.rules.length : undefined;
          var bits = [];

          if (typeof n === 'number') {
            bits.push('rules: ' + n);
          }

          if (data.snapshot_id) {
            bits.push('snapshot_id: ' + data.snapshot_id);
          }

          if (data.version) {
            bits.push('version: ' + data.version);
          }

          setSummaryMessage(bits.join(' | '));
        } else {
          var status = res && typeof res.status === 'number' ? res.status : 0;
          setSummaryMessage('ERROR — HTTP ' + String(status));
        }
      } catch (error) {
        setSummaryMessage('ERROR — red');
      }

      if (cta) {
        cta.disabled = ctaWasDisabled;
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

    function activateTab(tab) {
      if (!tab || tabElements.indexOf(tab) === -1 || isTabDisabled(tab)) {
        return;
      }

      var controls = tab.getAttribute('aria-controls');

      tabElements.forEach(function (item) {
        var isActive = item === tab;
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        item.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panelElements.forEach(function (panel) {
        if (!panel || !panel.getAttribute) {
          return;
        }

        if (panel.id && panel.id === controls) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
      });

      if (typeof tab.focus === 'function') {
        tab.focus();
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
        state: {},
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
          var okValue = data && data.ok !== undefined ? data.ok : response.ok;
          var hash = data && data.sku_hash ? data.sku_hash : '-';
          var expires = data && data.expires_at ? data.expires_at : '-';
          var snapshotValue =
            data && data.snapshot_id ? data.snapshot_id : snapshotId || '';
          var snapshot = snapshotValue || '-';
          var signature = '-';

          if (data && typeof data.sku_signature === 'string' && data.sku_signature) {
            var truncated = data.sku_signature.slice(0, 16);
            signature = truncated + '…';
          }

          var successMessage =
            __('OK — ok: ', TEXT_DOMAIN) +
            String(okValue) +
            __(' | hash: ', TEXT_DOMAIN) +
            hash +
            __(' | expira: ', TEXT_DOMAIN) +
            expires +
            __(' | snapshot: ', TEXT_DOMAIN) +
            snapshot +
            __(' | sig: ', TEXT_DOMAIN) +
            signature;

          setStatusMessage(successMessage);

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
        setStatusMessage(__('ERROR — fallo de red', TEXT_DOMAIN));
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
          var requestId = data.request_id ? data.request_id : '-';
          setStatusMessage(__('Verificado OK — request_id: ', TEXT_DOMAIN) + requestId);

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
        setStatusMessage(__('ERROR — fallo de red', TEXT_DOMAIN));
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

      if (rulesContainer) {
        setText(rulesContainer, '');
      }

      var wizard = global.G3DWIZARD || {};
      var modalData = getModalData();
      var snapshotId = modalData.snapshotId;
      var productoId = modalData.productoId;
      var locale = modalData.locale || wizard.locale || '';

      setStatusMessage('');

      if (!rulesLoaded) {
        setSummaryMessage('');
      }

      if (!productoId) {
        setSummaryMessage(
          __('TODO(plugin-2-g3d-catalog-rules.md §6): faltan parámetros.', TEXT_DOMAIN)
        );
        return;
      }

      var params = buildRulesParams(productoId, snapshotId, locale);

      loadRulesOnce(params);
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
      rulesLoaded = false;
      setSummaryMessage('');
      setStatusMessage('');

      if (message) {
        message.removeAttribute('aria-busy');
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

      tabElements.forEach(function (tab) {
        if (!initialTab && tab.getAttribute('aria-selected') === 'true' && !isTabDisabled(tab)) {
          initialTab = tab;
        }
      });

      if (!initialTab) {
        initialTab = getFirstEnabledTab();
      }

      if (initialTab) {
        activateTab(initialTab);
      }

      tabElements.forEach(function (tab) {
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
              activateTab(next);
            }
            return;
          }

          if (key === 'ArrowLeft' || key === 'ArrowUp') {
            event.preventDefault();
            var previous = getPreviousEnabledTab(tab);

            if (previous) {
              activateTab(previous);
            }
            return;
          }

          if (key === 'Home') {
            event.preventDefault();

            var first = getFirstEnabledTab();

            if (first) {
              activateTab(first);
            }

            return;
          }

          if (key === 'End') {
            event.preventDefault();

            var last = getLastEnabledTab();

            if (last) {
              activateTab(last);
            }

            return;
          }

          if (key === 'Enter' || key === ' ') {
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
