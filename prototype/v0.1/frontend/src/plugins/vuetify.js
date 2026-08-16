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
    primary:    '#F4B6D7',
    secondary:  '#DCCCF4', // --color-lavender
    tertiary:   '#A98274', // --color-brown
    background: '#F5F5F7',
    surface:    '#FFFFFF',
    'on-primary':    '#2B1A2A',
    'on-secondary':  '#3F3F46',
    'on-background': '#3F3F46',
    'on-surface':    '#3F3F46',
    success: '#A9D7B5',
    warning: '#F7D7A8',
    error:   '#F2A8A8',
    'on-error': '#3F3F46',
    outline: '#EDE9F2',
  },
}

const capymealDark = {
  dark: true,
  colors: {
    primary:    '#F4B6D7',
    secondary:  '#DCCCF4',
    tertiary:   '#A98274',
    background: '#2B2831',
    surface:    '#3A3642',
    'on-primary':    '#2B1A2A',
    'on-secondary':  '#2B1A2A',
    'on-background': '#E8E3EF',
    'on-surface':    '#E8E3EF',
    success: '#A9D7B5',
    warning: '#F7D7A8',
    error:   '#F2A8A8',
    'on-error': '#2B1A2A',
    outline: '#4A4654',
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
      rounded: 'xl',
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
