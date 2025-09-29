(function () {
  'use strict';

  const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  const ROOT_ID = 'gafas3d-wizard-modal-root';
  const ROOT_SELECTOR = '#' + ROOT_ID + ', [data-g3d-wizard-modal-root]';
  const DEFAULT_ENDPOINTS = {
    rules: '/wp-json/g3d/v1/catalog/rules', // TODO(doc §9): confirmar ruta pública.
    validate: '/wp-json/g3d/v1/validate-sign',
    verify: '/wp-json/g3d/v1/verify',
  };

  /**
   * @param {HTMLElement} element
   * @returns {boolean}
   */
  function isVisible(element) {
    return (
      element.offsetWidth > 0 ||
      element.offsetHeight > 0 ||
      element.getClientRects().length > 0
    );
  }

  /**
   * @param {HTMLElement} scope
   * @returns {HTMLElement[]}
   */
  function getFocusables(scope) {
    if (!scope) {
      return [];
    }

    const candidates = scope.querySelectorAll(FOCUSABLE_SELECTOR);

    return Array.prototype.filter.call(candidates, function (element) {
      if (!(element instanceof HTMLElement)) {
        return false;
      }

      if (element.hasAttribute('disabled')) {
        return false;
      }

      if (element.matches('[data-g3d-wizard-focus-guard]')) {
        return false;
      }

      return isVisible(element);
    });
  }

  /**
   * @param {Record<string, unknown>} params
   * @returns {string}
   */
  function buildQueryString(params) {
    const searchParams = new URLSearchParams();

    if (!params) {
      return '';
    }

    Object.keys(params).forEach(function (key) {
      const value = params[key];

      if (value === undefined || value === null) {
        return;
      }

      if (Array.isArray(value)) {
        value.forEach(function (item) {
          if (item === undefined || item === null) {
            return;
          }

          searchParams.append(key + '[]', String(item));
        });

        return;
      }

      searchParams.append(key, String(value));
    });

    const query = searchParams.toString();

    return query ? '?' + query : '';
  }

  /**
   * @param {string} url
   * @param {Record<string, unknown>} [params]
   * @returns {Promise<unknown>}
   */
  function getJSON(url, params) {
    const query = buildQueryString(params || {});

    return fetch(url + query, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
      credentials: 'same-origin',
    }).then(function (response) {
      return response.text().then(function (text) {
        const hasBody = text !== '';
        let data = null;

        if (hasBody) {
          try {
            data = JSON.parse(text);
          } catch (error) {
            const parseError = new Error('Respuesta JSON inválida');
            parseError.cause = error;
            parseError.status = response.status;
            throw parseError;
          }
        }

        if (!response.ok) {
          const errorMessage = data && data.message ? data.message : 'Request failed';
          const error = new Error(errorMessage);
          error.status = response.status;
          error.body = data;
          throw error;
        }

        return data;
      });
    });
  }

  /**
   * @param {string} url
   * @param {Record<string, unknown>} [body]
   * @returns {Promise<unknown>}
   */
  function postJSON(url, body) {
    const payload = body ? JSON.stringify(body) : '{}';

    return fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: payload,
      credentials: 'same-origin',
    }).then(function (response) {
      return response.text().then(function (text) {
        const hasBody = text !== '';
        let data = null;

        if (hasBody) {
          try {
            data = JSON.parse(text);
          } catch (error) {
            const parseError = new Error('Respuesta JSON inválida');
            parseError.cause = error;
            parseError.status = response.status;
            throw parseError;
          }
        }

        if (!response.ok) {
          const errorMessage = data && data.message ? data.message : 'Request failed';
          const requestError = new Error(errorMessage);
          requestError.status = response.status;
          requestError.body = data;
          throw requestError;
        }

        return data;
      });
    });
  }

  function init() {
    const root = document.querySelector(ROOT_SELECTOR);

    if (!root) {
      return;
    }

    const overlay = root.querySelector('[data-g3d-wizard-modal-overlay]');
    const dialog = overlay ? overlay.querySelector('[role="dialog"]') : null;
    const closeButtons = root.querySelectorAll('[data-g3d-wizard-modal-close]');
    const focusGuards = root.querySelectorAll('[data-g3d-wizard-focus-guard]');
    const openButtons = document.querySelectorAll('[data-g3d-wizard-modal-open]');
    let lastInvoker = null;
    let isOpen = false;

    function focusFallback() {
      if (!overlay) {
        return;
      }

      if (!overlay.hasAttribute('tabindex')) {
        overlay.setAttribute('tabindex', '-1');
      }

      overlay.focus();
    }

    function focusInitialElement() {
      if (!overlay) {
        return;
      }

      const title = root.querySelector('#g3d-wizard-modal-title');

      if (title instanceof HTMLElement) {
        if (!title.hasAttribute('tabindex')) {
          title.setAttribute('tabindex', '-1');
        }

        title.focus();

        if (document.activeElement === title) {
          return;
        }
      }

      const focusables = getFocusables(overlay);

      if (focusables.length) {
        focusables[0].focus();

        return;
      }

      focusFallback();
    }

    function handleDocumentKeydown(event) {
      if (!isOpen || event.key !== 'Escape') {
        return;
      }

      event.preventDefault();
      closeModal();
    }

    function handleOverlayKeydown(event) {
      if (!isOpen || event.key !== 'Tab') {
        return;
      }

      const focusables = getFocusables(overlay);

      if (!focusables.length) {
        event.preventDefault();
        focusFallback();

        return;
      }

      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      const active = /** @type {HTMLElement} */ (document.activeElement);

      if (event.shiftKey) {
        if (active === first || !overlay.contains(active)) {
          event.preventDefault();
          last.focus();
        }

        return;
      }

      if (active === last || !overlay.contains(active)) {
        event.preventDefault();
        first.focus();
      }
    }

    function handleOverlayClick(event) {
      if (!isOpen || !overlay) {
        return;
      }

      if (event.target === overlay) {
        closeModal();

        return;
      }

      if (dialog && dialog.contains(/** @type {Node} */ (event.target))) {
        return;
      }

      closeModal();
    }

    function handleFocusGuard(event) {
      if (!isOpen || !overlay) {
        return;
      }

      const guard = /** @type {HTMLElement|null} */ (
        event.currentTarget instanceof HTMLElement ? event.currentTarget : null
      );

      const focusables = getFocusables(overlay);

      if (!guard || !focusables.length) {
        focusFallback();

        return;
      }

      const value = guard.getAttribute('data-g3d-wizard-focus-guard');

      if (value === 'start' || guard === focusGuards[0]) {
        focusables[focusables.length - 1].focus();

        return;
      }

      if (value === 'end') {
        focusables[0].focus();

        return;
      }

      focusables[0].focus();
    }

    function openModal(invoker) {
      if (!overlay || isOpen) {
        return;
      }

      overlay.hidden = false;
      isOpen = true;

      const activeElement = /** @type {HTMLElement|null} */ (document.activeElement);

      lastInvoker = invoker || activeElement;

      document.addEventListener('keydown', handleDocumentKeydown);
      overlay.addEventListener('keydown', handleOverlayKeydown, true);
      overlay.addEventListener('click', handleOverlayClick);

      focusInitialElement();
    }

    function closeModal() {
      if (!overlay || !isOpen) {
        return;
      }

      overlay.hidden = true;
      isOpen = false;

      document.removeEventListener('keydown', handleDocumentKeydown);
      overlay.removeEventListener('keydown', handleOverlayKeydown, true);
      overlay.removeEventListener('click', handleOverlayClick);

      if (lastInvoker && typeof lastInvoker.focus === 'function') {
        lastInvoker.focus();
      }
    }

    Array.prototype.forEach.call(openButtons, function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        openModal(/** @type {HTMLElement} */ (event.currentTarget));
      });
    });

    Array.prototype.forEach.call(closeButtons, function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    });

    Array.prototype.forEach.call(focusGuards, function (guard) {
      guard.addEventListener('focus', handleFocusGuard);
    });

    const tabs = root.querySelectorAll('[role="tab"]');
    const panels = root.querySelectorAll('[role="tabpanel"]');

    /**
     * @param {HTMLElement} tab
     * @param {{focus?: boolean}} [options]
     */
    function activateTab(tab, options) {
      if (!(tab instanceof HTMLElement)) {
        return;
      }

      const panelId = tab.getAttribute('aria-controls');
      const targetPanel = panelId ? document.getElementById(panelId) : null;

      Array.prototype.forEach.call(tabs, function (item) {
        const isActive = item === tab;
        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        item.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      if (!targetPanel || !root.contains(targetPanel)) {
        // TODO(doc §5 Navegación): panel faltante.
        return;
      }

      Array.prototype.forEach.call(panels, function (panel) {
        if (!(panel instanceof HTMLElement)) {
          return;
        }

        panel.hidden = panel !== targetPanel;
      });

      if (!options || options.focus !== false) {
        tab.focus();
      }
    }

    if (tabs.length && panels.length) {
      let initialTab = null;

      Array.prototype.forEach.call(tabs, function (tab) {
        const isSelected = tab.getAttribute('aria-selected') === 'true';

        if (!initialTab && isSelected) {
          initialTab = tab;
        }

        tab.addEventListener('click', function (event) {
          event.preventDefault();
          activateTab(/** @type {HTMLElement} */ (event.currentTarget));
        });

        tab.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            activateTab(/** @type {HTMLElement} */ (event.currentTarget));

            return;
          }

          if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            // TODO(doc §5 Navegación): flechas izq/der.
          }
        });
      });

      if (!initialTab) {
        initialTab = /** @type {HTMLElement} */ (tabs[0]);
      }

      activateTab(initialTab, { focus: false });
    }

    const dataset = /** @type {HTMLElement} */ (root).dataset || {};
    const endpoints = {
      rules: dataset.g3dEndpointRules || DEFAULT_ENDPOINTS.rules,
      validate: dataset.g3dEndpointValidate || DEFAULT_ENDPOINTS.validate,
      verify: dataset.g3dEndpointVerify || DEFAULT_ENDPOINTS.verify,
    };

    function fetchRules(params) {
      return getJSON(endpoints.rules, params);
    }

    function validateAndSign(payload) {
      return postJSON(endpoints.validate, payload);
    }

    function verifySignature(payload) {
      return postJSON(endpoints.verify, payload);
    }

    const api = {
      getJSON: getJSON,
      postJSON: postJSON,
      fetchRules: fetchRules,
      validateAndSign: validateAndSign,
      verifySignature: verifySignature,
    };

    /** @type {HTMLElement & {g3dWizardApi?: unknown}} */ (root).g3dWizardApi = api;

    const summaryElement = root.querySelector('.g3d-wizard-modal__summary');
    const ctaButton = root.querySelector('[data-g3d-wizard-modal-cta]');

    if (!ctaButton || !summaryElement) {
      return;
    }

    function updateSummary(message) {
      summaryElement.textContent = message;
    }

    function buildMockPayload() {
      return {
        schema_version: '1.0.0',
        snapshot_id: 'snap:2025-09-27T18:45:00Z',
        producto_id: 'prod:base',
        locale: 'es-ES',
        state: {
          producto_id: 'prod:base',
          piezas: [
            {
              pieza_id: 'pieza:frame',
              material_id: 'mat:acetato',
              modelo_id: 'modelo:FR_A_R',
              color_id: 'col:black',
              textura_id: 'tex:acetato_base',
              acabado_id: 'fin:default', // TODO(doc §4): usar dato real del estado.
              slots: {
                MAT_BASE: {
                  material_id: 'mat:acetato',
                  color_id: 'col:black',
                  textura_id: 'tex:acetato_base',
                  acabado_id: 'fin:default',
                },
              },
            },
          ],
          morphs: {},
        },
        // TODO(doc §6.1): rellenar flags y summary desde el estado real.
      };
    }

    ctaButton.addEventListener('click', function (event) {
      event.preventDefault();

      const payload = buildMockPayload();
      updateSummary('Procesando…');
      ctaButton.disabled = true;
      ctaButton.setAttribute('aria-busy', 'true');

      validateAndSign(payload)
        .then(function (result) {
          const data = /** @type {Record<string, unknown>} */ (result);

          if (data && data.ok) {
            const expiresAt = typeof data.expires_at === 'string'
              ? data.expires_at
              : 'desconocida';
            updateSummary('✓ firmado (expira: ' + expiresAt + ')');

            return;
          }

          const detail = data && typeof data.detail === 'string'
            ? data.detail
            : null;
          const code = data && data.code ? String(data.code) : null;
          const message = detail || code || 'respuesta inesperada';
          updateSummary('✗ error: ' + message);
        })
        .catch(function (error) {
          const body = error && error.body ? error.body : null;
          const detail = body && typeof body.detail === 'string' ? body.detail : null;
          const code = body && body.code ? String(body.code) : null;
          const fallback = typeof error.message === 'string' ? error.message : 'Error desconocido';
          const message = detail || code || fallback;
          updateSummary('✗ error: ' + message);
        })
        .finally(function () {
          ctaButton.disabled = false;
          ctaButton.removeAttribute('aria-busy');
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
}());
