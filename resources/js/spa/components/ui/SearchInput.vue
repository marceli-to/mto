<script setup>
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue'

const props = defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Search...'
  }
})

const emit = defineEmits(['update:modelValue'])

function clear() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div class="relative">
    <PhMagnifyingGlass class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
    <input
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <button
      v-if="modelValue"
      type="button"
      @click="clear"
      class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600"
    >
      <PhX class="w-4 h-4" />
    </button>
  </div>
</template>
