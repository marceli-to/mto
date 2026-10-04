<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhArchive, PhArrowCounterClockwise, PhClock } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import Flyout from '@/components/ui/Flyout.vue'
import ProjectForm from './ProjectForm.vue'
import ProjectTimeEntries from './ProjectTimeEntries.vue'

const { get, post, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const projects = ref([])
const search = ref('')
const loading = ref(true)
const activeFilters = ref(['active'])
const deleteDialog = ref({ show: false, id: null, loading: false })
const archiveDialog = ref({ show: false, project: null, loading: false, invoiceId: '' })
const invoices = ref(null) // loaded on first need, for settling unbilled time on archive
const flyout = ref({ show: false, projectId: null })
const timeFlyout = ref({ show: false, projectId: null, title: '' })

const flyoutTitle = computed(() => flyout.value.projectId ? 'Edit Project' : 'New Project')

function openCreate() {
  flyout.value = { show: true, projectId: null }
}

function openEdit(id) {
  flyout.value = { show: true, projectId: id }
}

function openTimeEntries(project) {
  timeFlyout.value = { show: true, projectId: project.id, title: project.name }
}

function closeTimeFlyout() {
  timeFlyout.value = { show: false, projectId: null, title: '' }
}

function closeFlyout() {
  flyout.value = { show: false, projectId: null }
}

function onProjectSaved(savedProject) {
  if (flyout.value.projectId) {
    const index = projects.value.findIndex(p => p.id === flyout.value.projectId)
    if (index !== -1) {
      projects.value[index] = { ...projects.value[index], ...savedProject }
    }
  } else {
    projects.value.unshift(savedProject)
  }
  closeFlyout()
}

const stateFilters = ['active', 'archived']

const stateColors = {
  active: 'bg-blue-100 text-blue-800',
  archived: 'bg-gray-100 text-gray-800'
}

const projectState = (project) => project.is_archive ? 'archived' : 'active'

function toggleFilter(state) {
  const index = activeFilters.value.indexOf(state)
  if (index === -1) {
    activeFilters.value.push(state)
  } else {
    activeFilters.value.splice(index, 1)
  }
}

const filteredProjects = computed(() => {
  let result = projects.value

  if (activeFilters.value.length > 0) {
    result = result.filter(p => activeFilters.value.includes(projectState(p)))
  }

  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(p =>
      p.name?.toLowerCase().includes(q) ||
      p.client?.name?.toLowerCase().includes(q)
    )
  }

  return result
})

// What the projects in view have on the books but not on an invoice yet. Collection
// work is money owed — it is what feeds the Open figure on the invoice list — while
// fixed-price projects are billed by hand, so the two are totalled apart rather than
// folded into one misleading number.
const unbilledTotals = computed(() => {
  const totals = {
    collections: { count: 0, value: 0 },
    projects: { count: 0, value: 0 },
    all: { count: 0, value: 0 },
  }

  for (const project of filteredProjects.value) {
    if (!project.unbilled_count) continue
    const bucket = project.is_collection ? totals.collections : totals.projects
    bucket.count += project.unbilled_count
    bucket.value += Number(project.unbilled_value) || 0
    totals.all.count += project.unbilled_count
    totals.all.value += Number(project.unbilled_value) || 0
  }

  return totals
})

// One pill, up to three sections — a kind that has nothing unbilled is left out, and
// the neutral grand total only earns its place when both kinds are present.
const unbilledSections = computed(() => {
  const { collections, projects, all } = unbilledTotals.value
  const sections = []

  if (collections.count > 0) {
    sections.push({
      key: 'collections',
      value: collections.value,
      title: `Collections: ${entryCount(collections.count)} unbilled, counts towards Open`,
      class: 'bg-amber-50 text-amber-800 inset-ring-amber-600/20',
    })
  }
  if (projects.count > 0) {
    sections.push({
      key: 'projects',
      value: projects.value,
      title: `Fixed-price projects: ${entryCount(projects.count)} unbilled, billed by hand`,
      class: 'bg-blue-50 text-blue-700 inset-ring-blue-600/20',
    })
  }
  if (sections.length > 1) {
    sections.push({
      key: 'all',
      value: all.value,
      title: `Total unbilled: ${entryCount(all.count)}`,
      class: 'bg-gray-50 text-gray-600 inset-ring-gray-500/20',
    })
  }

  return sections
})

function entryCount(count) {
  return `${count} ${count === 1 ? 'entry' : 'entries'}`
}

async function fetchProjects() {
  loading.value = true
  try {
    const data = await get('/api/projects/get')
    projects.value = data.data || []
  } catch (e) {
    error('Failed to load projects')
  } finally {
    loading.value = false
  }
}

async function cloneProject(id) {
  try {
    const data = await get(`/api/project/duplicate/${id}`)
    projects.value.unshift(data)
    success('Project cloned')
  } catch (e) {
    error('Failed to clone project')
  }
}

// Archiving takes unbilled work off the open figure, so warn before it silently
// disappears. Restoring puts it back and needs no warning.
function toggleArchive(project) {
  if (!project.is_archive && project.unbilled_count > 0) {
    archiveDialog.value = { show: true, project, loading: false, invoiceId: '' }
    fetchInvoices()
    return
  }
  return runArchive(project)
}

async function fetchInvoices() {
  if (invoices.value) return
  try {
    const data = await get('/api/invoices/get')
    invoices.value = data.data || []
  } catch (e) {
    error('Failed to load invoices')
  }
}

// The client's invoices, newest first, to pick the one that already covered this work.
const invoiceOptions = computed(() => {
  const clientId = archiveDialog.value.project?.client_id
  const options = (invoices.value || [])
    .filter(i => i.client_id === clientId)
    .sort((a, b) => String(b.number).localeCompare(String(a.number)))
    .map(i => ({ value: String(i.id), label: `${i.number} – ${i.title}` }))
  return [{ value: '', label: 'Leave unbilled' }, ...options]
})

function unbilledLabel(project) {
  const entries = project.unbilled_count === 1 ? 'entry' : 'entries'
  return `${project.unbilled_count} unbilled time ${entries} `
    + `worth ${formatCurrency(project.unbilled_value)}`
}

const archiveMessage = computed(() => {
  const project = archiveDialog.value.project
  if (!project) return ''
  return `This project has ${unbilledLabel(project)}. `
    + 'Mark them as billed by an existing invoice, or leave them unbilled — '
    + 'archiving removes them from the Open total either way.'
})

async function confirmArchive() {
  archiveDialog.value.loading = true
  await runArchive(archiveDialog.value.project, archiveDialog.value.invoiceId || null)
  archiveDialog.value = { show: false, project: null, loading: false, invoiceId: '' }
}

async function runArchive(project, invoiceId = null) {
  try {
    const saved = await post(`/api/project/archive/${project.id}`, invoiceId ? { invoice_id: invoiceId } : {})
    const index = projects.value.findIndex(p => p.id === project.id)
    if (index !== -1) {
      projects.value[index] = { ...projects.value[index], ...saved }
    }
    if (invoiceId) {
      success('Project archived, time entries marked as billed')
    } else {
      success(saved.is_archive ? 'Project archived' : 'Project restored')
    }
  } catch (e) {
    error('Failed to change the project state')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteProject() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/project/destroy/${deleteDialog.value.id}`)
    projects.value = projects.value.filter(p => p.id !== deleteDialog.value.id)
    success('Project deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete project')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchProjects)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">

      <div class="flex items-center gap-1">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Project"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Projects
        </h1>
      </div>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <template v-else>
      <!-- State Filters + unbilled roll-up for whatever they leave in view -->
      <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2">
          <button
            v-for="state in stateFilters"
            :key="state"
            @click="toggleFilter(state)"
            :class="[
              activeFilters.includes(state) ? stateColors[state] : 'bg-gray-100 text-gray-400',
              'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors'
            ]"
          >
            {{ state }}
          </button>
        </div>

        <div v-if="unbilledSections.length" class="flex items-center gap-2">
          <span class="text-xs text-gray-400">
            {{ entryCount(unbilledTotals.all.count) }} unbilled
          </span>
          <!-- Sections overlap by a pixel so neighbouring rings collapse into one divider line. -->
          <div class="inline-flex shrink-0 items-stretch text-xs font-medium tabular-nums">
            <span
              v-for="(section, index) in unbilledSections"
              :key="section.key"
              :title="section.title"
              :class="[
                section.class,
                index === 0 ? 'rounded-l-md' : '-ml-px',
                index === unbilledSections.length - 1 ? 'rounded-r-md' : '',
              ]"
              class="px-2 py-1 inset-ring-1"
            >{{ formatCurrency(section.value) }}</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredProjects.length === 0" class="text-center py-16">
        <div class="text-gray-400 mb-2">No projects found</div>
        <p class="text-sm text-gray-400">Create your first project to get started</p>
      </div>

      <!-- Projects List -->
      <div v-else class="overflow-hidden border-t border-gray-100">
        <ul class="divide-y divide-gray-100">
          <li
            v-for="project in filteredProjects"
            :key="project.id"
            class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors"
          >
            <div class="flex items-center gap-x-6">
              <span :class="[stateColors[projectState(project)], 'px-2 py-1 rounded-md text-xs font-medium capitalize']">
                {{ projectState(project) }}
              </span>
              <div class="flex items-center gap-x-8">
                {{ project.name }}
                <span v-if="project.client" class="font-bold">{{ project.client.acronym }}</span>
              </div>
            </div>
            <div class="flex items-center gap-6">
              <!-- Archiving takes unbilled work off the books, so flag what was left behind. -->
              <span
                v-if="project.is_archive && project.unbilled_count > 0"
                :title="unbilledLabel(project)"
                class="bg-amber-100 text-amber-800 px-2 py-1 rounded-md text-xs font-medium tabular-nums"
              >
                {{ project.unbilled_count }} unbilled &bull; {{ formatCurrency(project.unbilled_value) }}
              </span>
              <!-- What the project has consumed: revenue for collection work, budget burn for fixed. -->
              <span
                v-if="project.hours_spent > 0"
                class="bg-green-100 text-green-800 px-2 py-1 rounded-md text-xs font-medium tabular-nums"
              >
                {{ project.hours_spent }} h &bull;
                <template v-if="project.is_collection">{{ formatCurrency(project.revenue) }}</template>
                <template v-else><template v-if="project.budget_used !== null">{{ project.budget_used }}% &bull; </template>{{ formatCurrency(project.value_spent) }}</template>
              </span>
              <div class="flex items-center gap-1">
                <button
                  @click="openTimeEntries(project)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  title="Time entries"
                >
                  <PhClock class="w-5 h-5" />
                </button>
                <button
                  @click="openEdit(project.id)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  title="Edit"
                >
                  <PhPencil class="w-5 h-5" />
                </button>
                <button
                  @click="cloneProject(project.id)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  title="Clone"
                >
                  <PhCopy class="w-5 h-5" />
                </button>
                <button
                  @click="toggleArchive(project)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  :title="project.is_archive ? 'Restore' : 'Archive'"
                >
                  <component :is="project.is_archive ? PhArrowCounterClockwise : PhArchive" class="w-5 h-5" />
                </button>
                <button
                  @click="confirmDelete(project.id)"
                  class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
                  title="Delete"
                >
                  <PhTrash class="w-5 h-5" />
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </template>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Project"
      message="Are you sure you want to delete this project?"
      :loading="deleteDialog.loading"
      @confirm="deleteProject"
      @cancel="deleteDialog.show = false"
    />

    <ConfirmDialog
      :show="archiveDialog.show"
      :title="`Archive ${archiveDialog.project?.name ?? ''}?`"
      :message="archiveMessage"
      :confirm-label="archiveDialog.invoiceId ? 'Mark billed & archive' : 'Archive'"
      :loading="archiveDialog.loading"
      @confirm="confirmArchive"
      @cancel="archiveDialog.show = false"
    >
      <div class="mt-6">
        <BaseSelect
          v-model="archiveDialog.invoiceId"
          label="Billed by invoice"
          :options="invoiceOptions"
          :disabled="!invoices"
        />
      </div>
    </ConfirmDialog>

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="md"
      @close="closeFlyout"
    >
      <ProjectForm
        :project-id="flyout.projectId"
        @saved="onProjectSaved"
        @cancel="closeFlyout"
      />
    </Flyout>

    <Flyout
      :show="timeFlyout.show"
      :title="timeFlyout.title"
      size="xl"
      @close="closeTimeFlyout"
    >
      <ProjectTimeEntries :project-id="timeFlyout.projectId" @moved="fetchProjects" />
    </Flyout>
  </div>
</template>
