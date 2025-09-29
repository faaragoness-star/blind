(function () {
  'use strict';

  // TODO(doc §5): focus-trap, ARIA updates, navegación de pasos.

  const ROOT_ID = 'gafas3d-wizard-modal-root';
  const ROOT_SELECTOR = '#' + ROOT_ID + ', [data-g3d-wizard-modal-root]';
  const DEFAULT_ENDPOINTS = {
    rules: '/wp-json/g3d/v1/catalog/rules', // TODO(doc §9): confirmar ruta pública.
    validate: '/wp-json/g3d/v1/validate-sign',
    verify: '/wp-json/g3d/v1/verify',
  };

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
