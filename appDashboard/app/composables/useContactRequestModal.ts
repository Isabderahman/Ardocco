import type { Ref } from 'vue'
import { contactRequestService } from '~/services/contactRequestService'
import type { ContactRequestCreatePayload } from '~/types/models/contact-request'

export function useContactRequestModal(listingId: Ref<string>) {
  const { token, user } = useAuth()

  const open = ref(false)
  const pending = ref(false)
  const success = ref(false)

  const form = reactive<Omit<ContactRequestCreatePayload, 'listing_id'>>({
    name: user.value?.first_name ? `${user.value.first_name} ${user.value.last_name || ''}` : '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
    message: ''
  })

  async function submit() {
    if (!token.value) {
      navigateTo('/login')
      return
    }

    const payload: ContactRequestCreatePayload = {
      listing_id: listingId.value,
      name: form.name,
      email: form.email,
      phone: form.phone,
      message: form.message
    }

    pending.value = true
    try {
      await contactRequestService.create(payload, token.value)
      success.value = true

      setTimeout(() => {
        open.value = false
        success.value = false
      }, 2000)
    } catch (err) {
      console.error('Contact error:', err)
    } finally {
      pending.value = false
    }
  }

  return {
    open,
    pending,
    success,
    form,
    submit
  }
}
