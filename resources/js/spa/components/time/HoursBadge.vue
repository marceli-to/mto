<script setup>
import { computed } from 'vue'

const props = defineProps({
  billable: { type: Number, default: 0 },
  nonBillable: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
})

/**
 * One pill, up to three sections: billable (green), non-billable (amber), total (neutral).
 * A category that contributes nothing is left out so single-category days stay readable.
 */
const sections = computed(() => {
  const parts = []
  if (props.billable > 0) {
    parts.push({
      key: 'billable',
      value: props.billable,
      title: 'Billable hours',
      class: 'bg-green-50 text-green-700 inset-ring-green-600/20',
    })
  }
  if (props.nonBillable > 0) {
    parts.push({
      key: 'non_billable',
      value: props.nonBillable,
      title: 'Non-billable hours',
      class: 'bg-amber-50 text-amber-800 inset-ring-amber-600/20',
    })
  }
  if (parts.length > 1) {
    parts.push({
      key: 'total',
      value: props.total,
      title: 'Total hours',
      class: 'bg-gray-50 text-gray-600 inset-ring-gray-500/20',
    })
  }
  return parts
})
</script>

<template>
  <div v-if="sections.length" class="inline-flex shrink-0 items-stretch text-xs font-medium tabular-nums">
    <!-- Sections overlap by a pixel so neighbouring rings collapse into one divider line. -->
    <span
      v-for="(section, index) in sections"
      :key="section.key"
      :title="section.title"
      :class="[
        section.class,
        index === 0 ? 'rounded-l-md' : '-ml-px',
        index === sections.length - 1 ? 'rounded-r-md' : '',
      ]"
      class="px-2 py-1 inset-ring-1"
    >{{ section.value }}<template v-if="index === sections.length - 1"> h</template></span>
  </div>
</template>
