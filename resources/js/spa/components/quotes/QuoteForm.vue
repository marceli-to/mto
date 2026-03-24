<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhArrowUp, PhArrowDown } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import TiptapEditor from '@/components/ui/TiptapEditor.vue'
import QuotePositionForm from './QuotePositionForm.vue'

const props = defineProps({
  quoteId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const isEdit = computed(() => !!props.quoteId)
const title = computed(() => isEdit.value ? 'Edit Quote' : 'New Quote')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clients = ref([])
const positionDialog = ref({ show: false, sectionIndex: null, position: null })

const vatOptions = [
  { value: 0, label: 'None' },
  { value: 7.7, label: '7.7%' },
  { value: 8.1, label: '8.1%' }
]

const quote = ref({
  title: '',
  date: '',
  client_id: '',
  intro_greeting: '',
  intro_text: '',
  vat_rate: 8.1,
  daily_rate: '',
  hourly_rate: '',
  include_terms_page: true,
  sections: []
})

// Computed totals
const grandTotal = computed(() => {
  return quote.value.sections.reduce((sum, section) => {
    return sum + section.positions.reduce((sSum, p) => sSum + parseFloat(p.amount || 0), 0)
  }, 0)
})

function sectionTotal(section) {
  return section.positions.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
}

async function fetchData() {
  loading.value = true
  try {
    const clientsData = await get('/api/clients/get')
    clients.value = clientsData.data || []

    if (isEdit.value) {
      const data = await get(`/api/quote/edit/${props.quoteId}`)
      quote.value = {
        ...data,
        client_id: data.client_id || '',
        date: formatDateForInput(data.date),
        daily_rate: data.daily_rate || '',
        hourly_rate: data.hourly_rate || '',
        sections: (data.sections || []).map(s => ({
          ...s,
          positions: s.positions || []
        }))
      }
    } else {
      const today = new Date()
      quote.value.date = formatDateForInput(today)
    }
  } catch (e) {
    error('Failed to load data')
    if (isEdit.value) emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  const today = new Date()
  quote.value = {
    title: '',
    date: formatDateForInput(today),
    client_id: '',
    intro_greeting: '',
    intro_text: '',
    vat_rate: 8.1,
    daily_rate: '',
    hourly_rate: '',
    include_terms_page: true,
    sections: []
  }
  errors.value = {}
}

watch(() => props.quoteId, (newId) => {
  if (newId) {
    fetchData()
  } else {
    resetForm()
    fetchData()
  }
})

function formatDateForInput(date) {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  return d.toISOString().split('T')[0]
}

function validate() {
  errors.value = {}
  if (!quote.value.title?.trim()) {
    errors.value.title = 'Title is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  // Assign sort_order to sections and positions
  quote.value.sections.forEach((section, si) => {
    section.sort_order = si
    section.positions.forEach((position, pi) => {
      position.sort_order = pi
    })
  })

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/quote/update/${props.quoteId}`, quote.value)
      success('Quote updated')
    } else {
      await post('/api/quote/create', quote.value)
      success('Quote created')
    }
    emit('saved')
  } catch (e) {
    error('Failed to save quote')
  } finally {
    saving.value = false
  }
}

// Section management
function addSection() {
  quote.value.sections.push({
    id: null,
    title: '',
    total_label: 'Total',
    sort_order: quote.value.sections.length,
    positions: []
  })
}

async function deleteSection(index) {
  if (!confirm('Delete this section and all its positions?')) return

  const section = quote.value.sections[index]
  if (section.id) {
    try {
      await del(`/api/quote/section/destroy/${section.id}`)
    } catch (e) {
      error('Failed to delete section')
      return
    }
  }
  quote.value.sections.splice(index, 1)
}

function moveSectionUp(index) {
  if (index === 0) return
  const sections = quote.value.sections
  ;[sections[index - 1], sections[index]] = [sections[index], sections[index - 1]]
}

function moveSectionDown(index) {
  if (index >= quote.value.sections.length - 1) return
  const sections = quote.value.sections
  ;[sections[index], sections[index + 1]] = [sections[index + 1], sections[index]]
}

// Position management
function addPosition(sectionIndex) {
  positionDialog.value = { show: true, sectionIndex, position: null }
}

function editPosition(sectionIndex, positionIndex) {
  const position = quote.value.sections[sectionIndex].positions[positionIndex]
  positionDialog.value = {
    show: true,
    sectionIndex,
    position: { ...position, _index: positionIndex }
  }
}

async function deletePosition(sectionIndex, positionIndex) {
  if (!confirm('Delete this position?')) return

  const position = quote.value.sections[sectionIndex].positions[positionIndex]
  quote.value.sections[sectionIndex].positions.splice(positionIndex, 1)

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
    quote.value.sections[si].positions[position._index] = position
  } else {
    quote.value.sections[si].positions.push(position)
  }
  positionDialog.value.show = false
}

const clientOptions = computed(() =>
  clients.value.map(c => ({ value: c.id, label: `${c.name} - ${c.city || ''}` }))
)

onMounted(fetchData)
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="space-y-6">
      <!-- Basic Fields -->
      <div class="space-y-4">
        <BaseInput
          v-model="quote.title"
          label="Title"
          required
          :error="errors.title"
          @focus="errors.title = null"
        />

        <BaseSelect
          v-model="quote.client_id"
          label="Client"
          :options="clientOptions"
          placeholder="Select a client..."
        />

        <BaseInput
          v-model="quote.date"
          label="Date"
          type="date"
        />

        <BaseInput
          v-model="quote.intro_greeting"
          label="Greeting"
          placeholder="Hallo Laura"
        />

        <TiptapEditor
          v-model="quote.intro_text"
          label="Intro Text"
        />

        <div class="grid grid-cols-3 gap-4">
          <BaseSelect
            v-model="quote.vat_rate"
            label="VAT"
            :options="vatOptions"
          />
          <BaseInput
            v-model="quote.daily_rate"
            label="Daily Rate"
            type="number"
            step="0.01"
            placeholder="1120.00"
          />
          <BaseInput
            v-model="quote.hourly_rate"
            label="Hourly Rate"
            type="number"
            step="0.01"
            placeholder="140.00"
          />
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            v-model="quote.include_terms_page"
            class="w-4 h-4 rounded border-gray-300 text-gray-600 focus:ring-gray-200"
          />
          <span class="text-sm text-gray-600">Include terms page</span>
        </label>
      </div>

      <!-- Sections -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold">Sections</h2>
          <button
            type="button"
            @click="addSection"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
            title="Add Section"
          >
            <PhPlus class="w-5 h-5" />
          </button>
        </div>

        <div v-if="quote.sections.length === 0" class="text-center py-8 text-gray-500">
          No sections yet. Add one to get started.
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="(section, sectionIndex) in quote.sections"
            :key="sectionIndex"
            class="border border-gray-200 rounded-md p-4"
          >
            <!-- Section Header -->
            <div class="flex items-start gap-3 mb-4">
              <div class="flex-1 grid grid-cols-2 gap-3">
                <BaseInput
                  v-model="section.title"
                  placeholder="Section title (e.g., Phase 1: Landingpage)"
                />
                <BaseInput
                  v-model="section.total_label"
                  placeholder="Total label (e.g., Total)"
                />
              </div>
              <div class="flex items-center gap-1">
                <button
                  type="button"
                  @click="moveSectionUp(sectionIndex)"
                  :disabled="sectionIndex === 0"
                  class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors disabled:opacity-30 disabled:cursor-default"
                >
                  <PhArrowUp class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  @click="moveSectionDown(sectionIndex)"
                  :disabled="sectionIndex >= quote.sections.length - 1"
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

            <!-- Positions within section -->
            <div class="ml-0">
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
                      <td class="py-2 text-right font-medium w-28">{{ formatCurrency(position.amount) }}</td>
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

                <!-- Section Total -->
                <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-gray-200">
                  <span>{{ section.total_label || 'Total' }}</span>
                  <span>{{ formatCurrency(sectionTotal(section)) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grand Total -->
        <div v-if="quote.sections.length > 0" class="mt-6 bg-gray-50 rounded-sm px-4 py-3">
          <div class="flex justify-between font-bold">
            <span>Grand Total</span>
            <span>{{ formatCurrency(grandTotal) }}</span>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 mt-8">
        <BaseButton type="button" variant="secondary" @click="emit('cancel')">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="saving">
          {{ isEdit ? 'Update' : 'Create' }}
        </BaseButton>
      </div>
    </form>

    <QuotePositionForm
      v-if="positionDialog.show"
      :position="positionDialog.position"
      @close="positionDialog.show = false"
      @save="onPositionSaved"
    />
  </div>
</template>
