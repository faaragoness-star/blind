(function (global) {
  'use strict';

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

    var dialog = overlay.querySelector('[role="dialog"]');
    var modal = overlay.querySelector('.g3d-wizard-modal');
    var cta = overlay.querySelector('[data-g3d-wizard-modal-cta]');
    var verifyButton = overlay.querySelector('[data-g3d-wizard-modal-verify]');
    var message = overlay.querySelector('.g3d-wizard-modal__msg');
    var lastValidation = null;
    var autoVerify = overlay.getAttribute('data-auto-verify') === '1';
    var previousFocus = null;

    function setText(element, value) {
      if (!element) {
        return;
      }

      element.textContent = value;
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
        setText(message, 'ERROR — endpoint no disponible');
        return;
      }

      lastValidation = null;

      var snapshotId = '';
      var productoId = '';
      var locale = '';

      if (modal) {
        snapshotId = modal.getAttribute('data-snapshot-id') || '';
        productoId = modal.getAttribute('data-producto-id') || '';
        locale = modal.getAttribute('data-locale') || '';
      }

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
      setText(cta, 'Enviando…');
      setText(message, '');

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
            'OK — ok: ' +
            String(okValue) +
            ' | hash: ' +
            hash +
            ' | expira: ' +
            expires +
            ' | snapshot: ' +
            snapshot +
            ' | sig: ' +
            signature;

          setText(message, successMessage);

          lastValidation = {
            sku_hash: data && data.sku_hash ? data.sku_hash : '',
            sku_signature:
              data && typeof data.sku_signature === 'string'
                ? data.sku_signature
                : '',
            snapshot_id: snapshotValue,
          };

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
            code = 'HTTP ' + response.status;
          }

          setText(message, 'ERROR — ' + code);
        }
      } catch (error) {
        setText(message, 'ERROR — fallo de red');
      } finally {
        cta.disabled = false;
        setText(cta, defaultLabel);
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
        setText(message, 'ERROR — endpoint no disponible');
        return;
      }

      if (
        !lastValidation ||
        !lastValidation.sku_hash ||
        !lastValidation.sku_signature
      ) {
        setText(message, 'ERROR — Primero valida y firma');
        return;
      }

      var payload = {
        sku_hash: lastValidation.sku_hash,
        sku_signature: lastValidation.sku_signature,
        snapshot_id: lastValidation.snapshot_id || '',
      };

      var defaultLabel = verifyButton.textContent;
      verifyButton.disabled = true;
      setText(verifyButton, 'Verificando…');
      setText(message, '');

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
          setText(message, 'Verificado OK — request_id: ' + requestId);
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
            code = 'ERROR';
          }

          setText(message, 'ERROR — ' + code);
        }
      } catch (error) {
        setText(message, 'ERROR — fallo de red');
      } finally {
        verifyButton.disabled = false;
        setText(verifyButton, defaultLabel);
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

      previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

      overlay.removeAttribute('hidden');
      document.body.classList.add('g3d-wizard-open');

      if (dialog && typeof dialog.focus === 'function') {
        dialog.focus();
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
