function v3d_handle_uploads(app_id) {
    var input = document.getElementById("appfiles");
    var progress = document.getElementById("upload_progress");
    var progressCounter = 0;

    function updateProgress() {
        progressCounter++;
        progress.innerHTML = progressCounter + '/' + input.files.length;
    }

    for (var i = 0; i < input.files.length; i++) {
        var file = input.files[i];
        var path = file.webkitRelativePath || file.name;
        var ext = path.split('.').pop();

        // prevent upload of Blender and Max files
        if (ext == 'blend' || ext == 'max') {
            updateProgress();
            continue;
        }

        console.log("Uploading " + path);

        var formData = new FormData();
        formData.append("action", "v3d_upload_app_file");
        formData.append("app", app_id);
        formData.append("apppath", path);
        formData.append("appfile", file);
        var req = new XMLHttpRequest();
        req.open("POST", ajaxurl);
        req.send(formData);
        req.addEventListener('load', function() {
            updateProgress();
        });
    }
}
