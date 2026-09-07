import { createRouter, createWebHistory } from 'vue-router'

// List components (forms are now in Flyouts)
import Statistics from '@/components/statistics/Statistics.vue'
import ClientInvoices from '@/components/statistics/ClientInvoices.vue'
import YearInvoices from '@/components/statistics/YearInvoices.vue'
import ClientList from '@/components/clients/ClientList.vue'
import ProjectList from '@/components/projects/ProjectList.vue'
import InvoiceList from '@/components/invoices/InvoiceList.vue'
import QuoteList from '@/components/quotes/QuoteList.vue'
import ExpenseList from '@/components/expenses/ExpenseList.vue'
import TimeList from '@/components/time/TimeList.vue'

const routes = [
  {
    path: '/',
    redirect: { name: 'time' }
  },
  {
    path: '/statistics',
    name: 'statistics',
    component: Statistics
  },
  {
    path: '/statistics/invoices/:client',
    name: 'client-invoices',
    component: ClientInvoices
  },
  {
    path: '/statistics/year/:year',
    name: 'year-invoices',
    component: YearInvoices
  },

  // Invoices
  {
    path: '/invoices',
    name: 'invoices',
    component: InvoiceList
  },

  // Quotes
  {
    path: '/quotes',
    name: 'quotes',
    component: QuoteList
  },

  // Expenses
  {
    path: '/expenses',
    name: 'expenses',
    component: ExpenseList
  },

  // Time
  {
    path: '/time',
    name: 'time',
    component: TimeList
  },

  // Clients
  {
    path: '/clients',
    name: 'clients',
    component: ClientList
  },

  // Projects
  {
    path: '/projects',
    name: 'projects',
    component: ProjectList
  },

  // Catch all - redirect to time
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'time' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
