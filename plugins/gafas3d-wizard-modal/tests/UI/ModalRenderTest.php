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
    var overlay = document.querySelector('[' + OVERLAY_ATTR + ']');

    if (!overlay) {
      return;
    }

    var root = overlay;
    var modal = root.querySelector('.g3d-wizard-modal');
    var dialog = root.querySelector('[role="dialog"]');
    var cta = overlay.querySelector('[data-g3d-wizard-modal-cta]');
    var verifyButton = overlay.querySelector('[data-g3d-wizard-modal-verify]');
    var message = overlay.querySelector('.g3d-wizard-modal__msg');
    var rulesContainer = modal ? modal.querySelector('.g3d-wizard-modal__rules') : null;
    var tabs = modal
      ? Array.prototype.slice.call(modal.querySelectorAll('[role="tab"]'))
      : [];
    var panels = modal
      ? Array.prototype.slice.call(modal.querySelectorAll('[role="tabpanel"]'))
      : [];
    var lastValidation = null;
    var autoVerify = overlay.getAttribute('data-auto-verify') === '1';
    var shouldAutoAudit = modal && modal.getAttribute('data-auto-audit') === '1';
    var previousFocus = null;

    function setText(element, value) {
      if (!element) {
        return;
      }

      element.textContent = value;
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

    function activateTab(tab) {
      if (!tab || tabs.indexOf(tab) === -1) {
        return;
      }

      var controls = tab.getAttribute('aria-controls');

      tabs.forEach(function (item) {
        var isActive = item === tab;
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        item.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panels.forEach(function (panel) {
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

    function focusNext(current) {
      if (!tabs.length) {
        return;
      }

      var index = tabs.indexOf(current);
      var nextIndex = (index + 1) % tabs.length;
      var target = tabs[nextIndex];

      if (target && typeof target.focus === 'function') {
        target.focus();
      }
    }

    function focusPrev(current) {
      if (!tabs.length) {
        return;
      }

      var index = tabs.indexOf(current);

      if (index === -1) {
        index = 0;
      }

      var prevIndex = (index - 1 + tabs.length) % tabs.length;
      var target = tabs[prevIndex];

      if (target && typeof target.focus === 'function') {
        target.focus();
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
        setText(message, __('ERROR — endpoint no disponible', TEXT_DOMAIN));
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
        setText(message, '');
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

          setText(message, successMessage);

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

          if (autoVerify) {
            runVerifyRequest();
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
            code = __('HTTP ', TEXT_DOMAIN) + response.status;
          }

          setText(message, __('ERROR — ', TEXT_DOMAIN) + code);
        }
      } catch (error) {
        setText(message, __('ERROR — fallo de red', TEXT_DOMAIN));
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
        setText(message, __('ERROR — endpoint no disponible', TEXT_DOMAIN));
        return;
      }

      if (!lastValidation || !lastValidation.sku_hash || !lastValidation.sku_signature) {
        setText(message, __('ERROR — Primero valida y firma', TEXT_DOMAIN));
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
        setText(message, '');
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
          setText(message, __('Verificado OK — request_id: ', TEXT_DOMAIN) + requestId);

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

          setText(message, __('ERROR — ', TEXT_DOMAIN) + code);
        }
      } catch (error) {
        setText(message, __('ERROR — fallo de red', TEXT_DOMAIN));
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
        var wizard = global.G3DWIZARD || {};
        var modalData = getModalData();
        var snapshotId = modalData.snapshotId;
        var productoId = modalData.productoId;
        var locale = modalData.locale || wizard.locale || '';

        if (!productoId) {
          setText(
            rulesContainer,
            __('TODO(plugin-2-g3d-catalog-rules.md §6): faltan parámetros.', TEXT_DOMAIN)
          );
        } else {
          var params = {
            producto_id: productoId,
          };

          if (snapshotId) {
            params.snapshot_id = snapshotId;
          }

          if (locale) {
            params.locale = locale;
          }

          var api = wizard.api || {};
          var rulesUrl = api.rules || api.catalogRules;

          if (!rulesUrl) {
            rulesUrl = '/wp-json/g3d/v1/catalog/rules';
            // TODO(Plugin 2 §endpoint público): confirmar ruta
          }

          var getJSON =
            typeof wizard.getJSON === 'function'
              ? wizard.getJSON
              : global.G3DWIZARD.getJSON;

          if (typeof getJSON !== 'function') {
            setText(rulesContainer, __('Reglas: N/D', TEXT_DOMAIN));
          } else {
            setText(rulesContainer, __('Reglas: N/D', TEXT_DOMAIN));

            getJSON(rulesUrl, params)
              .then(function (data) {
                if (data && Array.isArray(data.rules)) {
                  setText(
                    rulesContainer,
                    __('Reglas: ', TEXT_DOMAIN) + String(data.rules.length)
                  );
                }
              })
              .catch(function () {
                setText(rulesContainer, __('Reglas: N/D', TEXT_DOMAIN));
              });
          }
        }
      }
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
    }

    var openButtons = document.querySelectorAll('[' + OPEN_ATTR + ']');
    openButtons.forEach(function (button) {
      button.addEventListener('click', openModal);
    });

    var closeButtons = overlay.querySelectorAll('[' + CLOSE_ATTR + ']');
    closeButtons.forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    if (tabs.length && panels.length) {
      activateTab(tabs[0]);

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function (event) {
          if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
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
            focusNext(tab);
            return;
          }

          if (key === 'ArrowLeft' || key === 'ArrowUp') {
            event.preventDefault();
            focusPrev(tab);
            return;
          }

          if (key === 'Home') {
            event.preventDefault();

            if (tabs[0] && typeof tabs[0].focus === 'function') {
              tabs[0].focus();
            }

            return;
          }

          if (key === 'End') {
            event.preventDefault();

            var last = tabs[tabs.length - 1];

            if (last && typeof last.focus === 'function') {
              last.focus();
            }

            return;
          }

          if (key === 'Enter' || key === ' ') {
            event.preventDefault();
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
