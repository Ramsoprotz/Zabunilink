import { createI18n } from 'vue-i18n'
import en from './en.js'
import sw from './sw.js'

const savedLocale = localStorage.getItem('locale') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, sw },
})

export default i18n
