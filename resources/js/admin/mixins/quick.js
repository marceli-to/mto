export default {
  methods: {
    toggleForm(componentName) {
      this.hasForm = this.hasForm ? false : true;
      this.componentName = this.componentName == componentName
        ? this.componentName = ''
        : componentName;
    },
  }
};
