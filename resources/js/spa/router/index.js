import { createRouter, createWebHistory } from 'vue-router'

// Client components
import ClientList from '@/components/clients/ClientList.vue'
import ClientForm from '@/components/clients/ClientForm.vue'

// Contact components
import ContactList from '@/components/contacts/ContactList.vue'
import ContactForm from '@/components/contacts/ContactForm.vue'

// Project components
import ProjectList from '@/components/projects/ProjectList.vue'
import ProjectForm from '@/components/projects/ProjectForm.vue'

// Invoice components
import InvoiceList from '@/components/invoices/InvoiceList.vue'
import InvoiceForm from '@/components/invoices/InvoiceForm.vue'

// Expense components
import ExpenseList from '@/components/expenses/ExpenseList.vue'
import ExpenseForm from '@/components/expenses/ExpenseForm.vue'

const routes = [
  {
    path: '/',
    redirect: { name: 'projects' }
  },
  {
    path: '/dashboard',
    redirect: { name: 'projects' }
  },

  // Clients
  {
    path: '/clients',
    name: 'clients',
    component: ClientList
  },
  {
    path: '/client/create',
    name: 'client-create',
    component: ClientForm
  },
  {
    path: '/client/edit/:id',
    name: 'client-edit',
    component: ClientForm
  },

  // Contacts
  {
    path: '/contacts/:clientId',
    name: 'contacts',
    component: ContactList
  },
  {
    path: '/contact/create/:clientId',
    name: 'contact-create',
    component: ContactForm
  },
  {
    path: '/contact/edit/:id',
    name: 'contact-edit',
    component: ContactForm
  },

  // Projects
  {
    path: '/projects',
    name: 'projects',
    component: ProjectList
  },
  {
    path: '/project/create',
    name: 'project-create',
    component: ProjectForm
  },
  {
    path: '/project/edit/:id',
    name: 'project-edit',
    component: ProjectForm
  },

  // Invoices
  {
    path: '/invoices',
    name: 'invoices',
    component: InvoiceList
  },
  {
    path: '/invoice/create',
    name: 'invoice-create',
    component: InvoiceForm
  },
  {
    path: '/invoice/edit/:id',
    name: 'invoice-edit',
    component: InvoiceForm
  },

  // Expenses
  {
    path: '/expenses',
    name: 'expenses',
    component: ExpenseList
  },
  {
    path: '/expense/create',
    name: 'expense-create',
    component: ExpenseForm
  },
  {
    path: '/expense/edit/:id',
    name: 'expense-edit',
    component: ExpenseForm
  },

  // Catch all - redirect to projects
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'projects' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
