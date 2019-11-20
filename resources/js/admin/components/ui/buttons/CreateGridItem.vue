<template>
    <div class="center">
        <div v-if="showArticle">
            <a href="javascript:;" class="btn-add-article" @click.prevent="createArticle(gridId,gridPosition);">News hinzufügen</a>
        </div>
        <div>
            <a href="javascript:;" class="btn-add-media" @click.prevent="createMedia(gridId,gridPosition);">Projekt hinzufügen</a>
        </div>
    </div>
</template>
<script>

export default {

    props: {
        gridId: Number,
        gridPosition: Number,
        showArticle: Boolean
    },

    methods: {
        createMedia(gridId,position) {
            this.$parent.createMedia(gridId, position);
        },
        createArticle(gridId,position) {
            this.$parent.createArticle(gridId, position);
        },
    }
}
</script>