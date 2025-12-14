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
  'w-full px-3 py-2 border rounded-md transition-colors',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
  props.error
    ? 'border-red-300 bg-red-50'
    : 'border-gray-300 bg-white',
  props.disabled && 'bg-gray-100 cursor-not-allowed'
])
</script>

<template>
  <div class="space-y-1">
    <label v-if="label" class="block text-sm font-medium text-gray-700">
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
