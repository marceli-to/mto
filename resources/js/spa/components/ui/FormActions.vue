<script setup>
import { PhFloppyDisk, PhArrowLeft } from '@phosphor-icons/vue'
import BaseButton from './BaseButton.vue'

defineProps({
  loading: Boolean,
  backRoute: Object,
  submitLabel: {
    type: String,
    default: 'Save'
  }
})

const emit = defineEmits(['cancel'])
</script>

<template>
  <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
    <router-link
      v-if="backRoute"
      :to="backRoute"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900"
    >
      <PhArrowLeft class="w-4 h-4" />
      Back
    </router-link>
    <button
      v-else
      type="button"
      @click="emit('cancel')"
      class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900"
    >
      <PhArrowLeft class="w-4 h-4" />
      Cancel
    </button>

    <BaseButton type="submit" :loading="loading">
      <PhFloppyDisk class="w-4 h-4" />
      {{ submitLabel }}
    </BaseButton>
  </div>
</template>
