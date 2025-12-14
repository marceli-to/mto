<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  error: String,
  required: Boolean,
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])

const inputClasses = computed(() => [
  'w-full px-3 py-3 border rounded-xs transition-all text-sm',
  'focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300',
  props.error
    ? 'border-red-300 bg-red-50'
    : 'border-gray-200 bg-white',
  props.disabled && 'bg-gray-50 cursor-not-allowed'
])
</script>

<template>
  <div class="space-y-1">
    <label v-if="label" class="block text-sm text-gray-500 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="inputClasses"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
  </div>
</template>
