// jsdom no implementa matchMedia ni ResizeObserver, y Vuetify los toca al
// montar cualquier componente (temas, layout) -- sin estos stubs, el primer
// test que monte algo relacionado con Vuetify falla por el entorno, no por
// la aserción real.
if (!window.matchMedia) {
  window.matchMedia = (query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  })
}

if (!window.ResizeObserver) {
  window.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
  }
}
