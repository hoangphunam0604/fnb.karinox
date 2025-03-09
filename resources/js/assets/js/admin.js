$(document).ready(function () {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf_token"]').attr("content"),
    },
  });
  tinymce.init({
    height: 800,
    selector: '.tinymce-editor',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight |  numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    mergetags_list: [
      { value: 'First.Name', title: 'First Name' },
      { value: 'Email', title: 'Email' },
    ]
  });
  $(".delete-confirm").click(function () {
    const deleteForm = $(this).next('form');
    swal(
      {
        title: "Chắc chưa cưng?",
        text: $(this).data('text'),
        type: "warning",
        showCancelButton: !0,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Vầng, em chắc!!!",
        cancelButtonText: "Không! Em nhầm!!!",
        closeOnConfirm: !1,
        backdrop: `
    rgba(0,0,123,0.4)
    url("https://sweetalert2.github.io/images/nyan-cat.gif")
    left top
    no-repeat
  `
      }
    ).then(function (isConfirm) {
      if (typeof isConfirm.value !== 'undefined' && isConfirm.value == true) {
        deleteForm.submit();
      }
    });
  });

  $("input[type='file']").on("change", function (e) {
    const field_name = $(this).attr('name');
    const preview = $(`#${field_name}_preview`);
    if (e.target.files.length == 0) {
      preview.html('');
    }
    var file = e.target.files[0];
    const name = file.name;
    const lastDot = name.lastIndexOf('.');
    let ext = name.substring(lastDot + 1).toLowerCase();
    switch (ext) {
      case "png":
      case "jpg":
      case "jpeg":
        const src = URL.createObjectURL(file);
        preview.html(`<img src="${src}">`)
        break;
      default:
        preview.html('');
        break;
    }
  });

  $(".select2-box").select2();
});
