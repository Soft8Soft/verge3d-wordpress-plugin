(function() {

var v3d_woo_product_info_cb = null;

function v3d_woo_form_get_quantity(formData) {
    var qtyElems = document.body.querySelectorAll('input.qty');

    if (qtyElems.length == 0) {
        formData.append('quantity', 1);
    } else {
        for (var i = 0; i < qtyElems.length; i++) {
            var qtyElem = qtyElems[i];
            formData.append(qtyElem.name, qtyElem.value);
        }
    }
}

function v3d_woo_form_get_product_id(formData) {
    var varIdElem = document.body.querySelector('input[name=product_id]');
    formData.append('product_id', varIdElem ? varIdElem.value : -1);
}

function v3d_woo_form_get_variation_id(formData) {
    var varIdElem = document.body.querySelector('input[name=variation_id]');
    formData.append('variation_id', varIdElem ? varIdElem.value : -1);
}

function v3d_woo_form_get_attributes(formData) {
    var attElems = document.body.querySelectorAll('table.variations select');
    for (var i = 0; i < attElems.length; i++) {
        var attElem = attElems[i];
        formData.append(attElem.name, attElem.value);
    }
}

function v3d_woo_get_product_info(callback) {
    v3d_woo_product_info_cb = callback;
    v3d_woo_request_product_info();
}
window.v3d_woo_get_product_info = v3d_woo_get_product_info;


function v3d_woo_request_product_info() {
    var formData = new FormData();
    formData.append('action', 'v3d_woo_get_product_info');
    v3d_woo_form_get_quantity(formData);
    v3d_woo_form_get_product_id(formData);
    v3d_woo_form_get_variation_id(formData);
    v3d_woo_form_get_attributes(formData);

    var req = new XMLHttpRequest();
    // registered in php via v3d_load_woo_scripts
    req.open('POST', v3d_woo_ajax_url);
    req.send(formData);
    req.addEventListener('load', function() {
        var response = JSON.parse(req.response);

        if (v3d_woo_product_info_cb)
            v3d_woo_product_info_cb(response);
    });
}


window.addEventListener('load', function() {

    var qtyElems = document.body.querySelectorAll('input.qty');
    for (var i = 0; i < qtyElems.length; i++)
        qtyElems[i].onchange = v3d_woo_request_product_info;

    var varFormElem = document.body.querySelector('.variations_form');
    if (varFormElem)
        varFormElem.woocommerce_variation_has_changed = v3d_woo_request_product_info;

});


})();
