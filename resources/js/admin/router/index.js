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
    path: '/admin',
    redirect: { name: 'projects' }
  },
  {
    path: '/admin/dashboard',
    redirect: { name: 'projects' }
  },

  // Clients
  {
    path: '/admin/clients',
    name: 'clients',
    component: ClientList
  },
  {
    path: '/admin/client/create',
    name: 'client-create',
    component: ClientForm
  },
  {
    path: '/admin/client/edit/:id',
    name: 'client-edit',
    component: ClientForm
  },

  // Contacts
  {
    path: '/admin/contacts/:clientId',
    name: 'contacts',
    component: ContactList
  },
  {
    path: '/admin/contact/create/:clientId',
    name: 'contact-create',
    component: ContactForm
  },
  {
    path: '/admin/contact/edit/:id',
    name: 'contact-edit',
    component: ContactForm
  },

  // Projects
  {
    path: '/admin/projects',
    name: 'projects',
    component: ProjectList
  },
  {
    path: '/admin/project/create',
    name: 'project-create',
    component: ProjectForm
  },
  {
    path: '/admin/project/edit/:id',
    name: 'project-edit',
    component: ProjectForm
  },

  // Invoices
  {
    path: '/admin/invoices',
    name: 'invoices',
    component: InvoiceList
  },
  {
    path: '/admin/invoice/create',
    name: 'invoice-create',
    component: InvoiceForm
  },
  {
    path: '/admin/invoice/edit/:id',
    name: 'invoice-edit',
    component: InvoiceForm
  },

  // Expenses
  {
    path: '/admin/expenses',
    name: 'expenses',
    component: ExpenseList
  },
  {
    path: '/admin/expense/create',
    name: 'expense-create',
    component: ExpenseForm
  },
  {
    path: '/admin/expense/edit/:id',
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
