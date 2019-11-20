export default {
    url: "/api/media/upload/document",
    method: 'post',
    maxFilesize: 8,
    maxFiles: 1,
    createImageThumbnails: false,
    acceptedFiles: '.pdf',
    headers: {
      'Authorization': 'Bearer ' + localStorage.getItem('token')
    }
}