import { createRouter, createWebHistory } from 'vue-router'

// List components (forms are now in Flyouts)
import ClientList from '@/components/clients/ClientList.vue'
import ProjectList from '@/components/projects/ProjectList.vue'
import InvoiceList from '@/components/invoices/InvoiceList.vue'
import ExpenseList from '@/components/expenses/ExpenseList.vue'

const routes = [
  {
    path: '/',
    redirect: { name: 'invoices' }
  },
  {
    path: '/dashboard',
    redirect: { name: 'invoices' }
  },

  // Invoices
  {
    path: '/invoices',
    name: 'invoices',
    component: InvoiceList
  },

  // Expenses
  {
    path: '/expenses',
    name: 'expenses',
    component: ExpenseList
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

  // Catch all - redirect to invoices
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'invoices' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
