export default {

  methods: {

    // Change tabs
    changeTab(tab) {
      // set all tabs inactive and remove errors if any
      for (let prop in this.tabs) {
        this.tabs[prop].active = false;
        this.tabs[prop].error = false;
      }

      // set active tab
      this.tabs[tab].active = true;
    },

    // Show the validation errors
    validationError() {
      this.$notify({
        type: 'error',
        text: 'Please check marked fields!'
      });
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    },

    // Remove error classes
    removeError(field) {
      this.errors[field] = false;
    },
  }
};
