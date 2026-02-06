<script setup lang="ts">
import type { FormError, FormState } from '~/types/models/form'

const props = defineProps<{
  state: FormState
  schema?: unknown
  validate?: (state: FormState) => Promise<FormError[]> | FormError[]
}>()

const emit = defineEmits<{
  (e: 'submit' | 'error', event: unknown): void
}>()
</script>

<template>
  <UForm
    v-slot="form"
    v-bind="$attrs"
    :state="props.state"
    :schema="props.schema"
    :validate="props.validate"
    class="space-y-6"
    @submit="emit('submit', $event)"
    @error="emit('error', $event)"
  >
    <slot v-bind="form" />
  </UForm>
</template>
