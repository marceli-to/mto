export default {
    skin_url: '/assets/admin/js/tinymce/skins/strut',
    branding: false,
    menubar: false,
    statusbar: false,
    external_plugins: {
        link: '/assets/admin/js/tinymce/plugins/link/plugin.min.js',
    },
    toolbar: 'undo redo | bold | link | superscript | removeformat | styleselect',
    paste_as_text: true,
    height : "240px",
    style_formats_merge: false,
    style_formats: [{
        title: 'Text',
        items: [
            { title: 'Worttrennung deaktivieren', inline: 'span', styles: { "white-space": 'nowrap' } },
            { title: 'Überschrift 1', block : 'h1'},
            { title: 'Überschrift 2', block : 'h2'},
        ],
    }]
}