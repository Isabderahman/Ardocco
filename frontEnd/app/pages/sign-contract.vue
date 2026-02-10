<script setup lang="ts">
import { authService } from '~/services/authService'

definePageMeta({
  title: 'Signer le contrat'
})

const route = useRoute()
const router = useRouter()

const token = computed(() => route.query.token as string)

const form = ref({
  signature: '',
  accept_terms: false,
})

const pending = ref(false)
const success = ref(false)
const error = ref('')

const contractTerms = {
  vendeur: [
    'Je m\'engage à fournir des informations exactes et vérifiables sur mes terrains',
    'J\'accepte que toutes mes annonces soient soumises à l\'approbation d\'un administrateur',
    'Je m\'engage à maintenir mes documents cadastraux à jour',
    'J\'accepte la commission de 5% sur les ventes réalisées via la plateforme',
    'Je respecterai les conditions générales d\'utilisation de la plateforme ARDOCCO',
  ],
  acheteur: [
    'Je m\'engage à utiliser les informations des terrains de manière confidentielle',
    'J\'accepte de ne pas partager les coordonnées des vendeurs/agents sans autorisation',
    'Je comprends que les analyses financières sont indicatives et ne constituent pas un conseil en investissement',
    'Je respecterai les conditions générales d\'utilisation de la plateforme ARDOCCO',
  ],
  promoteur: [
    'J\'accepte d\'accéder aux informations détaillées des terrains disponibles',
    'Je m\'engage à demander l\'accès aux annonces des vendeurs avant tout contact',
    'Je m\'engage à respecter la confidentialité des données partagées',
    'Je m\'engage à respecter les délais et procédures d\'investissement',
    'Je respecterai les conditions générales d\'utilisation de la plateforme ARDOCCO',
  ],
}

const submitContract = async () => {
  if (!token.value) {
    error.value = 'Token invalide. Veuillez utiliser le lien envoyé par email.'
    return
  }

  if (!form.value.signature) {
    error.value = 'Veuillez entrer votre signature (nom complet).'
    return
  }

  if (!form.value.accept_terms) {
    error.value = 'Veuillez accepter les conditions.'
    return
  }

  pending.value = true
  error.value = ''

  try {
    await authService.signContract({
      token: token.value,
      signature: form.value.signature,
      accept_terms: true
    })

    success.value = true
  } catch (e: any) {
    error.value = e?.data?.message || 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <main class="min-h-screen bg-gray-50 py-12">
    <UContainer class="max-w-2xl">
      <!-- No token -->
      <div v-if="!token" class="bg-white rounded-xl shadow-sm p-8 text-center">
        <UIcon name="i-lucide-alert-circle" class="w-16 h-16 mx-auto text-red-500 mb-4" />
        <h1 class="text-xl font-semibold text-gray-900 mb-2">Lien invalide</h1>
        <p class="text-gray-600 mb-6">
          Ce lien n'est pas valide. Veuillez utiliser le lien envoyé à votre adresse email.
        </p>
        <UButton label="Retour à l'accueil" to="/" />
      </div>

      <!-- Success -->
      <div v-else-if="success" class="bg-white rounded-xl shadow-sm p-8 text-center">
        <UIcon name="i-lucide-check-circle" class="w-16 h-16 mx-auto text-green-500 mb-4" />
        <h1 class="text-xl font-semibold text-gray-900 mb-2">Contrat signé avec succès</h1>
        <p class="text-gray-600 mb-6">
          Votre contrat a été signé. Votre compte est maintenant en attente d'approbation par un administrateur.
          Vous recevrez un email dès que votre compte sera activé.
        </p>
        <UButton label="Retour à l'accueil" to="/" />
      </div>

      <!-- Contract Form -->
      <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-primary-600 text-white p-6">
          <h1 class="text-2xl font-bold">Contrat d'utilisation ARDOCCO</h1>
          <p class="mt-2 text-primary-100">
            Veuillez lire et accepter les conditions ci-dessous pour activer votre compte.
          </p>
        </div>

        <div class="p-6">
          <!-- Error message -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
            {{ error }}
          </div>

          <!-- Terms -->
          <div class="space-y-6">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Conditions générales</h2>
              <div class="bg-gray-50 rounded-lg p-4 space-y-3 max-h-64 overflow-y-auto">
                <div
                  v-for="(term, index) in contractTerms.vendeur"
                  :key="index"
                  class="flex items-start gap-3"
                >
                  <UIcon name="i-lucide-check" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                  <p class="text-sm text-gray-700">{{ term }}</p>
                </div>
              </div>
            </div>

            <!-- Signature -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Signature (votre nom complet)
              </label>
              <UInput
                v-model="form.signature"
                placeholder="Prénom Nom"
                size="lg"
              />
              <p class="text-xs text-gray-500 mt-1">
                En signant, vous confirmez avoir lu et accepté les conditions ci-dessus.
              </p>
            </div>

            <!-- Accept checkbox -->
            <div class="flex items-start gap-3">
              <input
                id="accept_terms"
                v-model="form.accept_terms"
                type="checkbox"
                class="mt-1 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
              />
              <label for="accept_terms" class="text-sm text-gray-700">
                J'ai lu et j'accepte les conditions générales d'utilisation et le contrat de la plateforme ARDOCCO.
              </label>
            </div>

            <!-- Submit -->
            <div class="pt-4">
              <UButton
                label="Signer le contrat"
                color="primary"
                size="lg"
                block
                :loading="pending"
                :disabled="!form.signature || !form.accept_terms"
                @click="submitContract"
              />
            </div>
          </div>
        </div>
      </div>
    </UContainer>
  </main>
</template>
