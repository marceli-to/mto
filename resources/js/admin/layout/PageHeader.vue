<template>
    <div>
        <header class="site-header">
            <div>
                <a href="/admin/" class="brand">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 480"><circle cx="240" cy="240" r="240"/><path fill="#fff" d="M206.5,138.3c15.1,0,27.3,5,36.5,15.1s13.9,23,13.9,39V296.3q0,5.7-5.4,5.7H225.6q-5.7,0-5.7-5.7V190.4c0-5.1-1.2-9.1-3.5-11.9a12.2,12.2,0,0,0-9.9-4.4c-4.5,0-7.9,1.5-10.2,4.4s-3.5,6.8-3.5,11.9V296.3c0,3.8-1.8,5.7-5.5,5.7H161.2q-5.4,0-5.4-5.7V190.4c0-5.1-1.2-9.1-3.5-11.9s-5.8-4.4-10.2-4.4-7.6,1.5-9.9,4.4-3.5,6.8-3.5,11.9V296.3c0,3.8-2,5.7-5.8,5.7H97.1q-5.4,0-5.4-5.7V192.4q0-24,13.8-39c9.3-10.1,21.5-15.1,36.6-15.1a42.2,42.2,0,0,1,32.2,14.1,42.1,42.1,0,0,1,32.2-14.1Zm70.2,173a5.7,5.7,0,0,1,1.6-4.2,5.8,5.8,0,0,1,4.1-1.6H382.9c3.6,0,5.4,2,5.4,5.8v24.9c0,3.6-1.8,5.5-5.4,5.5H282.4c-3.8,0-5.7-1.9-5.7-5.5V311.3Z"/></svg>                
                </a>
                <a href="javascript:;" @click="toggleMenu()" class="icon-menu"></a>
            </div>
        </header>
        <nav class="site-nav" :class="{'is-visible': menuVisible}">
            <div>
                <a href="javascript:;" @click="toggleMenu()" class="icon-close"></a>
            </div>
            <ul>
                <li>
                    <router-link :to="{ name: 'timer' }">Timer</router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'projects' }">Projects</router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'clients' }">Clients</router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'invoices' }">Invoices</router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'expenses' }">Expenses</router-link>
                </li>
            </ul>
            <router-link :to="{ name: 'logout' }" class="icon-logout">Logout</router-link>
        </nav>
    </div>
</template>
<script>
export default {
    data() {
        return {
            menuVisible: false
        }
    },
    methods: {
        toggleMenu() {
            this.menuVisible = !this.menuVisible;
        }
    }
}
</script>