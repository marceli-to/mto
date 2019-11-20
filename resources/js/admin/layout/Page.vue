<template>
    <div>
        <page-header />
        <div class="container">
            <main class="content" role="main">
                <div>
                    <h1>Dashboard</h1>
                </div>
            </main>
        </div>
    </div>
</template>
<script>
    import PageHeader from '@/layout/PageHeader.vue';
    export default {
        components: {
            pageHeader: PageHeader,
        },
    }
</script>