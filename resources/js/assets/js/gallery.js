
function sendFileToServer(formData, status, obj) {

  var jqXHR = $.ajax({
    xhr: function () {
      var xhrobj = $.ajaxSettings.xhr();
      if (xhrobj.upload) {
        xhrobj.upload.addEventListener('progress', function (event) {
          var percent = 0;
          var position = event.loaded || event.position;
          var total = event.total;
          if (event.lengthComputable) {
            percent = Math.ceil(position / total * 100);
          }
          //Set progress
          status.setProgress(percent);
        }, false);
      }
      return xhrobj;
    },
    dataType: "json",
    url: gallery_upload_url,
    type: "POST",
    contentType: false,
    processData: false,
    cache: false,
    data: formData,
    success: function (res) {
      status.setProgress(100);
      if (res.status == true) {
        const photo = res.photo;
        const thumbnail_url = photo.thumbnail;
        const input_id = obj.data('input');
        const input = $(`#${input_id}`);
        console.log(input);
        status.preview_img.attr('src', `${thumbnail_url}`);
        let oldValue = JSON.parse(input.val() || "[]");
        oldValue.push(photo)
        input.val(JSON.stringify(oldValue))
        this.remove = $(`<button class="remove" type="button"><i class="fa fa-trash"></i></button>`).appendTo(status.action);
        $(`<input type="hidden" class="thumbnail" value="${thumbnail_url}">`).insertAfter(status.action);
        // status.statusbar.remove();
      } else {
        status.progressBar.after("<div class='error'>Không up được nha</div>");
        status.progressBar.remove();
      }
    }
  });
  status.setAbort(jqXHR);
}

var rowCount = 0;
function createStatusbar(obj, file) {
  // Kiểm tra input chỉ có thể chọn 1 file thì xóa bớt status bar
  const input_file = obj.find('input[type="file"]');
  const multiple = input_file.attr('multiple')
  if (!multiple) {
    $(obj).next('.media_statusbar').remove();
  }
  media_preview = obj.next('.media_preview')
  let preview_img = `${base_url}/admin/img/preview_upload.png`;
  const name = file.name;
  const lastDot = name.lastIndexOf('.');
  let ext = name.substring(lastDot + 1).toLowerCase();
  switch (ext) {
    case "png":
    case "jpg":
    case "jpeg":
      preview_img = URL.createObjectURL(file);
      break;
    case "pdf":
      preview_img = `${base_url}/admin/img/preview_upload_pdf.jpg`;
      break;
    default:
      preview_img = `${base_url}/admin/img/preview_upload.png`;
      break;
  }

  rowCount++;
  var row = "odd";
  if (rowCount % 2 == 0) row = "even";
  this.statusbar = $("<div class='media_statusbar " + row + "'></div>");

  this.preview_img = $(`<img class="image" src="${preview_img}">`);

  if (!multiple) {
    this.preview = $(`<div class="preview"></div>`);
    media_preview.html(this.preview);
  } else {
    this.preview = $(`<div class="preview"></div>`).appendTo(media_preview)
  }
  this.preview.html(this.preview_img)
  this.progressBar = $("<div class='upload-progress'><div></div></div>").appendTo(this.preview);

  //action
  this.action = $("<div class='action'></div>").appendTo(this.preview);
  this.abort = $(`<button class="abort" type="button"><i class="fa fa-trash"></i></button>`).appendTo(this.action);

  media_preview.after(this.statusbar);

  this.setFileNameSize = function (name, size) {
    var sizeStr = "";
    var sizeKB = size / 1024;
    if (parseInt(sizeKB) > 1024) {
      var sizeMB = sizeKB / 1024;
      sizeStr = sizeMB.toFixed(2) + " MB";
    }
    else {
      sizeStr = sizeKB.toFixed(2) + " KB";
    }

    this.filename.html(name);
    this.size.html(sizeStr);
  }

  this.setProgress = function (progress) {
    var progressLeft = 100 - progress;
    this.progressBar.animate({ height: progressLeft }, 10);
    if (parseInt(progress) >= 100) {
      this.abort.remove();
    }
  }

  this.setAbort = function (jqxhr) {
    var sb = this.statusbar;
    this.abort.click(function () {
      jqxhr.abort();
      sb.remove();
    });
  }
}

function handleFileUpload(files, obj) {
  for (var i = 0; i < files.length; i++) {
    var fd = new FormData();
    fd.append('file', files[i]);

    var status = new createStatusbar(obj, files[i]); //Using this we can set progress.
    //status.setFileNameSize(files[i].name, files[i].size);
    sendFileToServer(fd, status, obj);
  }
}

$(document).ready(function () {
  var obj = $(".media_dragandrophandler");
  obj.on('dragenter', function (e) {
    e.stopPropagation();
    e.preventDefault();
    $(this).css('border', '2px solid #0B85A1');
  });

  obj.on('dragover', function (e) {
    e.stopPropagation();
    e.preventDefault();
  });

  obj.on('drop', function (e) {
    $(this).css('border', '2px dashed #0B85A1');
    e.preventDefault();
    var files = e.originalEvent.dataTransfer.files;

    //We need to send dropped files to Server
    handleFileUpload(files, $(this));
  });

  $(document).on('dragenter', '.media_dragandrophandler', function (e) {
    e.stopPropagation();
    e.preventDefault();
  });

  $(document).on('dragover', '.media_dragandrophandler', function (e) {
    e.stopPropagation();
    e.preventDefault();
    $(this).css('border', '2px dashed #0B85A1');
  });

  $(document).on('drop', '.media_dragandrophandler', function (e) {
    e.stopPropagation();
    e.preventDefault();
  });

  $('.gallery_files_select').on('change', function (e) {
    e.preventDefault();
    var files = e.target.files; // FileList object
    obj = $(this).closest('.media_dragandrophandler');
    //We need to send dropped files to Server
    handleFileUpload(files, obj);

    //reset input type file
    $(this).val('');
  });
  $(".media_preview").on("click", ".action .remove", function () {
    const preview = $(this).closest('.preview');
    const media_preview = $(this).closest('.media_preview');
    const previews = media_preview.find('.preview');
    const preview_number = previews.index(preview)

    const input_id = media_preview.data('input');
    const input = $(`#${input_id}`);
    let gallery = JSON.parse(input.val() || "[]");
    gallery.splice(preview_number, 1)
    input.val(JSON.stringify(gallery));
    preview.remove();
  });
  $(".media_preview").sortable({
    placeholder: "preview preview-holder",
    handle: ".image",
    update: function (event, ui) {
      const gallery_input = $(this).data('input')
      const items = $(this).children();
      let gallery = [];
      $.each(items, function (index, item) {
        const image = $(item).find('.image').attr('src');
        const thumbnail = $(item).find('.thumbnail').val();
        const gallery_item = {
          full: image,
          thumbnail: thumbnail
        }
        gallery.push(gallery_item);
      });
      $(`#${gallery_input}`).val(JSON.stringify(gallery));
    }
  });
});
