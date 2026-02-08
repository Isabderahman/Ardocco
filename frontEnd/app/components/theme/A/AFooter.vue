<script setup lang="ts">
const newsletterEmail = ref('')
const newsletterLoading = ref(false)
const agreeToTerms = ref(false)

async function onNewsletterSubmit() {
  if (!agreeToTerms.value) {
    return
  }

  newsletterLoading.value = true
  try {
    // TODO: wire to backend when available
    console.log('Newsletter signup:', newsletterEmail.value)
    newsletterEmail.value = ''
    agreeToTerms.value = false
  } finally {
    newsletterLoading.value = false
  }
}

const quickLinks = [
  { label: 'Home', to: '/' },
  { label: 'About', to: '/about' },
  { label: 'Buy', to: '/terrains' },
  { label: 'Sell', to: '/sell' },
  { label: 'Blog', to: '/blog' },
  { label: 'Contact', to: '/contact' }
]

const companyLinks = [
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' }
]

const legalLinks = [
  { label: 'Terms of use', to: '/terms' },
  { label: 'Privacy policy', to: '/privacy' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' },
  { label: 'Lorem', to: '#' }
]

const socialLinks = [
  { icon: 'i-lucide-twitter', to: 'https://twitter.com', label: 'Twitter' },
  { icon: 'i-lucide-linkedin', to: 'https://linkedin.com', label: 'LinkedIn' },
  { icon: 'i-lucide-facebook', to: 'https://facebook.com', label: 'Facebook' },
  { icon: 'i-lucide-instagram', to: 'https://instagram.com', label: 'Instagram' }
]

const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="footer-dark">
    <UContainer class="py-12">
      <!-- Main Footer Content -->
      <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-5">
        <!-- Logo and Contact Section -->
        <div class="lg:col-span-1">
          <div class="mb-6 flex items-center gap-2">
            <AppLogo variant="full" class="h-8 w-auto" />
          </div>

          <div class="space-y-3">
            <div>
              <p class="text-sm text-white/60">
                Call us
              </p>
              <a href="tel:+212123456789" class="text-base text-white hover:text-white/80 transition-colors">
                +212 123456789
              </a>
            </div>

            <div>
              <p class="text-sm text-white/60">
                Need live help
              </p>
              <a href="mailto:ardocco@support.com" class="text-base text-white hover:text-white/80 transition-colors">
                ardocco@support.com
              </a>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h3 class="mb-4 text-base font-semibold text-white">
            Quick links
          </h3>
          <ul class="space-y-3">
            <li v-for="link in quickLinks" :key="link.to">
              <NuxtLink
                :to="link.to"
                class="text-sm text-white/70 hover:text-white transition-colors"
              >
                {{ link.label }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Company Links -->
        <div>
          <h3 class="mb-4 text-base font-semibold text-white">
            Lorem
          </h3>
          <ul class="space-y-3">
            <li v-for="(link, index) in companyLinks" :key="index">
              <NuxtLink
                :to="link.to"
                class="text-sm text-white/70 hover:text-white transition-colors"
              >
                {{ link.label }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Legal Links -->
        <div>
          <h3 class="mb-4 text-base font-semibold text-white">
            lorem
          </h3>
          <ul class="space-y-3">
            <li v-for="(link, index) in legalLinks" :key="index">
              <NuxtLink
                :to="link.to"
                class="text-sm text-white/70 hover:text-white transition-colors"
              >
                {{ link.label }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Newsletter Section -->
        <div>
          <h3 class="mb-4 text-base font-semibold text-white">
            Newsletter
          </h3>
          <p class="mb-4 text-sm text-white/60">
            Sign up to receive the latest articles.
          </p>

          <form @submit.prevent="onNewsletterSubmit" class="space-y-3">
            <UInput
              v-model="newsletterEmail"
              type="email"
              placeholder="Your email address"
              size="lg"
              required
              class="footer-input"
              :ui="{
                base: 'w-full placeholder:text-white/40'
              }"
            />

            <UButton
              type="submit"
              color="primary"
              size="lg"
              block
              :loading="newsletterLoading"
              :disabled="!agreeToTerms"
            >
              Subscribe
            </UButton>

            <div class="flex items-start gap-2">
              <UCheckbox
                v-model="agreeToTerms"
                :ui="{
                  base: 'mt-0.5'
                }"
              />
              <label class="text-xs text-white/60 cursor-pointer" @click="agreeToTerms = !agreeToTerms">
                I have read and agree to the terms & conditions
              </label>
            </div>
          </form>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
        <p class="text-sm text-white/50">
          Copyright © {{ currentYear }} ARDOCCO | Designed & Developed by
          <a href="#" class="text-white/70 hover:text-white transition-colors">ARTKOM DIGITAL</a>
        </p>

        <!-- Social Media Links -->
        <div class="flex items-center gap-1">
          <span class="mr-3 text-sm text-white/70">Follow us</span>
          <a
            v-for="social in socialLinks"
            :key="social.label"
            :href="social.to"
            target="_blank"
            rel="noopener noreferrer"
            class="flex h-9 w-9 items-center justify-center rounded hover:bg-white/10 transition-colors"
            :aria-label="social.label"
          >
            <UIcon :name="social.icon" class="h-5 w-5 text-white/70 hover:text-white" />
          </a>
        </div>
      </div>
    </UContainer>
  </footer>
</template>

<style scoped>
.footer-dark {
  background-color: #2C2E33;
  color: white;
}

/* Input styling for dark footer */
:deep(.footer-input input) {
  background-color: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
}

:deep(.footer-input input:focus) {
  background-color: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.25);
}

:deep(.footer-input input::placeholder) {
  color: rgba(255, 255, 255, 0.4);
}

/* Checkbox styling */
:deep(.footer-dark input[type="checkbox"]) {
  background-color: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.3);
}

:deep(.footer-dark input[type="checkbox"]:checked) {
  background-color: var(--color-primary-500);
  border-color: var(--color-primary-500);
}
</style>
