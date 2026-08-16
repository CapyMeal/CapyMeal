import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'

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

const savedTheme = localStorage.getItem('capymeal-theme') || 'dark'

export default createVuetify({
  theme: {
    defaultTheme: savedTheme === 'dark' ? 'capymealDark' : 'capymealLight',
    themes: {
      capymealLight,
      capymealDark,
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
      variant: 'outlined',
      rounded: 'lg',
      color: 'primary',
    },
    VAlert: {
      rounded: 'lg',
    },
  },
})
