<script setup>
import { ref, computed } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhArrowUp, PhArrowDown, PhCaretRight } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import QuotePositionForm from './QuotePositionForm.vue'

// The sections array belongs to the quote form; this component edits it in place.
const props = defineProps({
  sections: {
    type: Array,
    required: true
  }
})

const { del } = useApi()
const { error } = useToast()
const { confirm } = useConfirm()
const { formatCurrency } = useCurrency()

const positionDialog = ref({ show: false, sectionIndex: null, position: null })

const currencyOptions = [
  { value: 'CHF', label: 'CHF' },
  { value: 'EUR', label: 'EUR' },
  { value: 'USD', label: 'USD' }
]

function sectionTotal(section) {
  return section.positions
    .filter(p => !p.amount_text)
    .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
}

function formatAmount(section, amount) {
  const formatted = formatCurrency(amount)
  return section.currency && section.currency !== 'CHF' ? `${section.currency} ${formatted}` : formatted
}

const allCollapsed = computed(() => props.sections.every(s => s._collapsed))

function toggleAllSections() {
  const collapse = !allCollapsed.value
  props.sections.forEach(s => { s._collapsed = collapse })
}

function addSection() {
  props.sections.push({
    id: null,
    title: '',
    total_label: 'Total',
    show_total: true,
    show_vat_note: true,
    currency: 'CHF',
    positions: [],
    _collapsed: false
  })
}

async function deleteSection(index) {
  if (!(await confirm({ title: 'Delete section', message: 'Delete this section and all its positions?' }))) return

  const section = props.sections[index]
  if (section.id) {
    try {
      await del(`/api/quote/section/destroy/${section.id}`)
    } catch (e) {
      error('Failed to delete section')
      return
    }
  }
  props.sections.splice(index, 1)
}

function moveSection(index, direction) {
  const target = index + direction
  if (target < 0 || target >= props.sections.length) return
  const sections = props.sections
  ;[sections[index], sections[target]] = [sections[target], sections[index]]
}

function addPosition(sectionIndex) {
  positionDialog.value = { show: true, sectionIndex, position: null }
}

function editPosition(sectionIndex, positionIndex) {
  const position = props.sections[sectionIndex].positions[positionIndex]
  positionDialog.value = {
    show: true,
    sectionIndex,
    position: { ...position, _index: positionIndex }
  }
}

async function deletePosition(sectionIndex, positionIndex) {
  if (!(await confirm({ title: 'Delete position', message: 'Delete this position?' }))) return

  const position = props.sections[sectionIndex].positions[positionIndex]
  props.sections[sectionIndex].positions.splice(positionIndex, 1)

  if (position.id) {
    try {
      await del(`/api/quote/position/destroy/${position.id}`)
    } catch (e) {
      error('Failed to delete position')
    }
  }
}

function onPositionSaved(position) {
  const si = positionDialog.value.sectionIndex
  if (position._index !== undefined) {
    props.sections[si].positions[position._index] = position
  } else {
    props.sections[si].positions.push(position)
  }
  positionDialog.value.show = false
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <span class="text-sm text-gray-500">Sections</span>
      <div class="flex items-center gap-3">
        <button
          v-if="sections.length > 0"
          type="button"
          @click="toggleAllSections"
          class="text-sm text-gray-500 hover:text-black cursor-pointer transition-colors"
        >
          {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
        </button>
        <button
          type="button"
          @click="addSection"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Section"
        >
          <PhPlus class="w-4 h-4" />
        </button>
      </div>
    </div>

    <div v-if="sections.length === 0" class="text-center py-6 text-gray-400 text-sm">
      No sections yet. Add one to get started.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(section, sectionIndex) in sections"
        :key="section.id || `new-${sectionIndex}`"
        class="border border-gray-200 rounded-md"
      >
        <!-- Section Header -->
        <div
          :class="[section._collapsed ? 'rounded-md' : 'border-b border-gray-200 rounded-t-md', 'flex items-center gap-3 px-4 py-2 bg-gray-50']"
        >
          <button
            type="button"
            @click="section._collapsed = !section._collapsed"
            class="flex-1 min-w-0 flex items-center gap-3 text-left cursor-pointer"
            :title="section._collapsed ? 'Expand' : 'Collapse'"
          >
            <PhCaretRight
              :class="[section._collapsed ? '' : 'rotate-90', 'w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform']"
            />
            <span :class="[section.title ? 'text-gray-700' : 'text-gray-400', 'flex-1 text-sm truncate']">
              {{ section.title || 'Untitled section' }}
            </span>
            <span class="text-sm text-gray-500 whitespace-nowrap">
              {{ formatAmount(section, sectionTotal(section)) }}
            </span>
          </button>
          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="moveSection(sectionIndex, -1)"
              :disabled="sectionIndex === 0"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
            >
              <PhArrowUp class="w-4 h-4" />
            </button>
            <button
              type="button"
              @click="moveSection(sectionIndex, 1)"
              :disabled="sectionIndex >= sections.length - 1"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
            >
              <PhArrowDown class="w-4 h-4" />
            </button>
            <button
              type="button"
              @click="deleteSection(sectionIndex)"
              class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-100 cursor-pointer rounded-sm transition-colors"
            >
              <PhTrash class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div v-show="!section._collapsed" class="p-4">
          <div class="mb-3">
            <BaseInput
              v-model="section.title"
              placeholder="Section title (e.g., Grundleistungen)"
            />
          </div>

          <!-- Section Options -->
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mb-4">
            <BaseCheckbox v-model="section.show_total" label="Show total" />
            <BaseCheckbox v-model="section.show_vat_note" label="Show VAT note" />
            <div class="w-28">
              <BaseSelect v-model="section.currency" :options="currencyOptions" compact />
            </div>
            <div v-if="section.show_total" class="flex-1 min-w-40">
              <BaseInput v-model="section.total_label" placeholder="Total label" compact />
            </div>
          </div>

          <!-- Positions -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-gray-500">Positions</span>
            <button
              type="button"
              @click="addPosition(sectionIndex)"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Add Position"
            >
              <PhPlus class="w-4 h-4" />
            </button>
          </div>

          <div v-if="section.positions.length === 0" class="text-center py-4 text-gray-400 text-sm">
            No positions
          </div>

          <div v-else>
            <table class="w-full text-sm border-b border-gray-100">
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(position, posIndex) in section.positions" :key="posIndex">
                  <td class="py-2">{{ position.description }}</td>
                  <td class="py-2 text-right w-32">
                    {{ position.amount_text || formatAmount(section, position.amount) }}
                  </td>
                  <td class="py-2 w-20">
                    <div class="flex justify-end gap-1">
                      <button
                        type="button"
                        @click="editPosition(sectionIndex, posIndex)"
                        class="p-1 cursor-pointer text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-sm"
                      >
                        <PhPencil class="w-3.5 h-3.5" />
                      </button>
                      <button
                        type="button"
                        @click="deletePosition(sectionIndex, posIndex)"
                        class="p-1 cursor-pointer text-gray-400 hover:text-red-600 hover:bg-red-100 rounded-sm"
                      >
                        <PhTrash class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div
              v-if="section.show_total"
              class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-gray-200"
            >
              <span>{{ section.total_label || 'Total' }}</span>
              <span>{{ formatAmount(section, sectionTotal(section)) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <QuotePositionForm
      v-if="positionDialog.show"
      :position="positionDialog.position"
      :currency="sections[positionDialog.sectionIndex]?.currency || 'CHF'"
      @close="positionDialog.show = false"
      @save="onPositionSaved"
    />
  </div>
</template>
