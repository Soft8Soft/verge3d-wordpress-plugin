
var V3D_IGNORE_EXT = [
    'blend',
    'blend1',
    'max',
    'ma',
    'mb'
]

function v3d_handle_uploads(app_id) {
    var input = document.getElementById("appfiles");
    var progressElem = document.getElementById("upload_progress");
    var statusElem = document.getElementById("upload_status");
    var progressCounter = 0;
    var errorState = false;

    function updateProgress() {
        progressCounter++;
        progressElem.innerText = progressCounter + '/' + input.files.length;

        if (progressCounter == input.files.length) {

            if (errorState) {
                statusElem.className = 'v3d-red';
                statusElem.innerText = 'Error!';
                alert('Error occured during upload: broken connection or maximum file size exceeded.\nPlease check your connection or server upload size limits.');
            } else {
                statusElem.className = 'v3d-green';
                statusElem.innerText = 'Success!';
            }

        } else {
            statusElem.innerText = '';
        }
    }

    for (var i = 0; i < input.files.length; i++) {
        var file = input.files[i];
        var path = file.webkitRelativePath || file.name;
        var ext = path.split('.').pop();

        // prevent upload of some files
        if (ext in V3D_IGNORE_EXT || path.indexOf('v3d_app_data') > -1) {
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
            if (this.readyState == 4) {
                updateProgress();

                if (this.status != 200 || this.response != 'ok') {
                    errorState = true;
                }
            }
        });
    }
}
