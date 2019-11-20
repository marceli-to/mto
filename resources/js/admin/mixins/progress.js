export default {
  methods: {
    progress(el) {
      el.classList.toggle('is-loading');
      return el;
    },
  }
};
