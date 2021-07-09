
// Auth 
import LoginComponent from '@/components/auth/LoginComponent.vue';
import LogoutComponent from '@/components/auth/LogoutComponent.vue';

// Page
import PageComponent from '@/layout/Page.vue';

// Clients
import ClientIndex from '@/components/client/Index.vue';
import ClientCreate from '@/components/client/Create.vue';
import ClientEdit from '@/components/client/Edit.vue';

// Contacts
import ContactIndex from '@/components/contact/Index.vue';
import ContactCreate from '@/components/contact/Create.vue';
import ContactEdit from '@/components/contact/Edit.vue';

// Projects
import ProjectIndex from '@/components/project/Index.vue';
import ProjectCreate from '@/components/project/Create.vue';
import ProjectEdit from '@/components/project/Edit.vue';

// Timer
import TimeIndex from '@/components/timer/Index.vue';
import TimeCreate from '@/components/timer/Create.vue';
import TimeEdit from '@/components/timer/Edit.vue';

// Invoices
import InvoiceIndex from '@/components/invoice/Index.vue';
import InvoiceCreate from '@/components/invoice/Create.vue';
import InvoiceEdit from '@/components/invoice/Edit.vue';

// Expenses
import ExpenseIndex from '@/components/expense/Index.vue';
import ExpenseCreate from '@/components/expense/Create.vue';
import ExpenseEdit from '@/components/expense/Edit.vue';

const routes = [
    {
        path: '/',
        redirect: { name: 'login' }
    },
    {
        path: '/admin',
        name: 'admin',
        component: PageComponent,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/dashboard',
        name: 'dashboard',
        component: PageComponent,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/login',
        name: 'login',
        component: LoginComponent
    },
    {
        path: '/admin/logout',
        name: 'logout',
        component: LogoutComponent
    },

    // Clients
    {
        name: 'clients',
        path: '/admin/clients',
        component: ClientIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'client-create',
        path: '/admin/client/create',
        component: ClientCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'client-edit',
        path: '/admin/client/edit/:id',
        component: ClientEdit,
        meta: { requiresAuth: true },
    },

    // Contacts
    {
        name: 'contacts',
        path: '/admin/contacts/:id',
        component: ContactIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'contact-create',
        path: '/admin/contact/create/:client_id',
        component: ContactCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'contact-edit',
        path: '/admin/contact/edit/:id',
        component: ContactEdit,
        meta: { requiresAuth: true },
    },

    // Projects
    {
        name: 'projects',
        path: '/admin/projects',
        component: ProjectIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'project-create',
        path: '/admin/project/create',
        component: ProjectCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'project-edit',
        path: '/admin/project/edit/:id',
        component: ProjectEdit,
        meta: { requiresAuth: true },
    },

    // Timer
    {
        name: 'timer',
        path: '/admin/timer',
        component: TimeIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'time-create',
        path: '/admin/time/create',
        component: TimeCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'time-edit',
        path: '/admin/time/edit/:id',
        component: TimeEdit,
        meta: { requiresAuth: true },
    },

    // Invoice
    {
        name: 'invoices',
        path: '/admin/invoices',
        component: InvoiceIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'invoice-create',
        path: '/admin/invoice/create',
        component: InvoiceCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'invoice-edit',
        path: '/admin/invoice/edit/:id',
        component: InvoiceEdit,
        meta: { requiresAuth: true },
    },
    {
        name: 'invoice-download',
        path: '/admin/invoice/pdf/:id',
        meta: { requiresAuth: true },
    },

    // Expense
    {
        name: 'expenses',
        path: '/admin/expenses',
        component: ExpenseIndex,
        meta: { requiresAuth: true },
    },
    {
        name: 'expense-create',
        path: '/admin/expense/create',
        component: ExpenseCreate,
        meta: { requiresAuth: true },
    },
    {
        name: 'expense-edit',
        path: '/admin/expense/edit/:id',
        component: ExpenseEdit,
        meta: { requiresAuth: true },
    },
    {
        name: 'expense-download',
        path: '/admin/expense/pdf/:id',
        meta: { requiresAuth: true },
    },
];

export default routes