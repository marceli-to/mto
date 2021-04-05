<template>
  <div>
    <div class="form-row">
      <label for="document">{{labelNew}}</label>
      <vue-dropzone
        ref="dropzone"
        id="dropzone"
        :options="dropzoneConfig"
        @vdropzone-complete="afterUpload"
      ></vue-dropzone>
      <span class="bubble is-restriction">{{labelRestrictions}}</span>
    </div>
    <div class="form-row" v-if="assets.length">
      <label>{{labelExisting}}</label>
      <div :class="[sortable ? 'has-sortable-images' : 'has-images', 'dropzone-existing-assets']">
        <div>
          <!-- <draggable 
            :disabled="true"
            @end="updateOrder"
            ghost-class="draggable-ghost"
            tag="div">        -->
            <figure
              :class="[asset.publish == 0 ? 'is-disabled' : '', 'dz-existing-asset is-image']"
              v-for="asset in assets"
              :key="asset.id"
            >
              <a :href="getAssetUri(asset.name)" target="_blank" class="dz-file-preview">
                <img :src="getAssetSource(asset.name)" height="300" width="300">
                <div class="dz-file-preview__caption" v-if="asset.caption">{{ asset.caption | truncate(20, '...') }}</div>
              </a>
              <div class="dz-toolbar">
                <a
                  href="javascript:;"
                  :class="[asset.publish == 1 ? 'icon-eye' : 'icon-eye-off', 'icon-mini']"
                  @click.prevent="toggleAsset(asset,$event)">
                </a>
                <a
                  href="javascript:;"
                  class="icon-edit icon-mini"
                  @click.prevent="showAssetEdit($event)"
                ></a>
                <a 
                  :href="getAssetUri(asset.name)" 
                  target="_blank" 
                  class="icon-external-link icon-mini">
                </a>
                <a
                  href="javascript:;"
                  class="icon-trash icon-mini"
                  @click.prevent="deleteUpload(asset.name,$event)">
                </a>
                <a v-if="hasStar"
                  href="javascript:;"
                  :class="[asset.is_preview == 1 ? 'icon-star' : 'icon-star-off', 'icon-mini']"
                  @click.prevent="togglePreview(asset,$event)">
                </a>
              </div>
              <div class="overlay-asset">
                <div>
                  <div class="overlay-grid">
                    <div>
                      <img :src="getAssetUri(asset.name)" height="300" width="300">
                      <figcaption>{{asset.caption}}</figcaption>
                    </div>
                    <div>
                      <div class="form-row">
                        <label>Name:</label>
                        <div>{{asset.name}}</div>
                      </div>
                      <div class="form-row">
                        <label>Legende:</label>
                        <input type="text" v-model="asset.caption" class="is-caption">
                      </div>
                      <div class="form-row" v-if="hasUrl">
                        <label>Link:</label>
                        <input type="text" v-model="asset.url" class="is-caption">
                      </div>
                      <div class="form-row" v-if="hasClients">
                        <label>Kunde</label>
                        <div class="select-wrapper is-wide">
                          <select
                            v-model="asset.client_id"
                            name="client_id"
                          >
                            <option value="NULL" selected="selected">Bitte wählen...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                          </select>
                        </div>
                      </div>
                      <div class="form-row-button">
                        <a
                          href="javascript:;"
                          class="btn-secondary"
                          @click.prevent="hideAssetEdit($event)"
                        >Speichern</a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </figure>
          <!-- </draggable>  -->
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import vue2Dropzone from "vue2-dropzone";
import draggable from 'vuedraggable';
import dropzoneConfig from "@/config/dropzoneconfig.js";

export default {

  components: {
    vueDropzone: vue2Dropzone,
    draggable: draggable,
  },

  props: {
    labelNew: String,
    labelExisting: String,
    labelRestrictions: String,
    assets: Array,
    assetType: String,
    acceptedFiles: String,
    maxFiles: Number,
    maxFilesize: Number,
    uploadUrl: String,
    hasStar: Boolean,
    hasCroppedPreview: Boolean,
    hasClients: Boolean,
    hasUrl: Boolean,
    sortable: Boolean,
    clients: Array
  },

  data() {
    return {
      dropzoneConfig: dropzoneConfig,
      css_classes: {
        visible: 'is-visible'
      }
    };
  },

  created() {
    this.dropzoneConfig.url = this.uploadUrl;
    this.dropzoneConfig.acceptedFiles = this.acceptedFiles;
    this.dropzoneConfig.maxFiles = this.maxFiles;
    this.dropzoneConfig.maxFilesize = this.maxFilesize;
  },

  methods: {

    afterUpload(asset) {
      this.$refs.dropzone.removeFile(asset);
      this.$parent.afterUpload(asset);
    },

    deleteUpload(asset,event) {
      this.$parent.deleteUpload(asset,event);
    },

    toggleAsset(asset,event) {
      this.$parent.toggleAsset(asset,event);
    },

    togglePreview(asset,event) {
      this.$parent.togglePreview(asset,event);
    },

    showAssetEdit(e) {
      let editForm = e.target.parentNode.nextElementSibling;
      editForm.classList.toggle(this.css_classes.visible);
    },

    hideAssetEdit(e) {
      let editForm= e.target.parentNode.parentNode.parentNode.parentNode.parentNode;
      editForm.classList.remove(this.css_classes.visible);
    },

    updateOrder() {
      let assets = this.assets.map(function(asset, index) {
          asset.order = index;
          return asset;
      });

      return;

      // if (this.debounce) return;

      // this.debounce = setTimeout(function(categories) {
      //   this.debounce = false 
      //   let uri = `/api/category/order`;
      //   this.axios.post(uri, {categories: categories}).then((response) => {
      //     this.$router.push({name: 'categories'});
      //   });
      // }.bind(this, categories), 1000);
      // this.$notify({type: 'success', text: 'Reihenfolge angepasst'});
    },

    getAssetUri(asset) {
      return `/media/${asset}/sm`;
    },

    getAssetCroppedUri(asset) {
      return `/media/preview/${asset}`;
    },

    getAssetSource(asset) {
      return `/media/thumbnail/${asset}`;
    }
  }
};
</script>