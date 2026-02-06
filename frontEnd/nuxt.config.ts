// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  modules: [
    '@nuxt/eslint',
    '@nuxt/ui'
  ],
  ssr: true,

  devtools: {
    enabled: true
  },

  app: {
    head: {
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: 'anonymous' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap' }
      ],
      script: [
        {
          innerHTML: `
            (function () {
              try {
                localStorage.setItem('ardocco-color-mode', 'light');
                localStorage.removeItem('nuxt-color-mode');
              } catch (e) {}

              var root = document.documentElement;
              root.classList.remove('dark');
              root.classList.add('light');
              root.style.colorScheme = 'light';
            })();
          `,
          tagPosition: 'head'
        }
      ]
    }
  },

  css: ['~/assets/css/main.css', 'leaflet/dist/leaflet.css'],

  colorMode: {
    preference: 'light',
    fallback: 'light',
    storageKey: 'ardocco-color-mode'
  },

  ui: {
    fonts: false
  },

  runtimeConfig: {
    backendBaseUrl: 'http://localhost:8000',
    public: {
      googleMapsApiKey: '',
      apiBaseUrl: ''
    }
  },

  routeRules: {
    '/': { prerender: true }
  },

  compatibilityDate: '2025-01-15',

  vite: {
    optimizeDeps: {
      include: ['leaflet']
    }
  },

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  }
})
