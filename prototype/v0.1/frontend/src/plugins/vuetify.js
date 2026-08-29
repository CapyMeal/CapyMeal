// vite-plugin-vuetify (autoImport: true, en vite.config.js) ya importa el
// CSS de cada componente de Vuetify que se usa -- importar 'vuetify/styles'
// acá además traía TODO el CSS del framework entero, duplicado con lo que
// ya autoimportaba cada componente.
import { createVuetify } from 'vuetify'
import { mdiAlertCircle, mdiCheckCircle, mdiCloseCircle } from '@mdi/js'
import { getInitialTheme } from '../utils/theme'

// Estos valores son copia de los tokens de ../styles/main.css (los mismos
// hex, en el mismo orden). Vuetify necesita colores concretos para sus
// utilidades internas (lighten/darken de estados hover/disabled), no
// puede consumir var(--x) directamente. Si se cambia un color de marca
// en main.css, hay que replicarlo acá también.
const capymealLight = {
  dark: false,
  colors: {
    primary:    '#96684A',
    secondary:  '#C9A66B', // --color-lavender (acento mostaza)
    tertiary:   '#A9805F', // --color-brown
    background: '#F2EAE0',
    surface:    '#FBF7F1',
    'on-primary':    '#FBF7F1',
    'on-secondary':  '#362B22',
    'on-background': '#362B22',
    'on-surface':    '#362B22',
    success: '#A3C79A',
    warning: '#F0C888',
    error:   '#E3968C',
    'on-error': '#362B22',
    outline: '#DED2C0',
  },
}

const capymealDark = {
  dark: true,
  colors: {
    primary:    '#D9A45C',
    secondary:  '#C9A66B',
    tertiary:   '#A9805F',
    background: '#22190F',
    surface:    '#2C2116',
    'on-primary':    '#22190F',
    'on-secondary':  '#22190F',
    'on-background': '#F1E4D3',
    'on-surface':    '#F1E4D3',
    success: '#A3C79A',
    warning: '#F0C888',
    error:   '#E3968C',
    'on-error': '#22190F',
    outline: '#3F3121',
  },
}

const savedTheme = getInitialTheme()

export default createVuetify({
  theme: {
    defaultTheme: savedTheme === 'dark' ? 'capymealDark' : 'capymealLight',
    themes: {
      capymealLight,
      capymealDark,
    },
  },
  // Vuetify solo usa íconos propios para los 3 estados de VAlert (success/
  // warning/error) en esta app -- nada de VSelect, VCheckbox, VMenu, etc.
  // Se pisan esos 3 alias con SVG puntual de @mdi/js (tree-shakeable) en vez
  // de cargar la fuente @mdi/font completa (~350 íconos que no se usan).
  icons: {
    aliases: {
      success: `svg:${mdiCheckCircle}`,
      warning: `svg:${mdiAlertCircle}`,
      error: `svg:${mdiCloseCircle}`,
    },
  },
  defaults: {
    VBtn: {
      rounded: 'lg',
      elevation: 0,
    },
    VCard: {
      rounded: 'lg',
    },
    VTextField: {
      variant: 'filled',
      rounded: 'lg',
      color: 'primary',
    },
    VAlert: {
      rounded: 'lg',
    },
  },
})
