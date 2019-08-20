
function v3d_woocommerce_change_param(name, value) {
    var formData = new FormData();
    formData.append('action', 'v3d_woocommerce_change_param');
    formData.append('v3d_' + name, value);

    var req = new XMLHttpRequest();
    req.open('POST', wc_add_to_cart_params.ajax_url);
    req.send(formData);
    req.addEventListener('load', function() {
        var response = JSON.parse(req.response);

        switch (name) {
        case 'name':
            var name_elem = document.querySelector('.product_title.entry-title');
            name_elem.innerHTML = response.html;
            break;
        case 'price':
            var price_elem = document.querySelector('p.price');
            price_elem.innerHTML = response.html;
            break;
        case 'sku':
            var sku_elem = document.querySelector('.sku_wrapper .sku');
            if (sku_elem)
                sku_elem.innerHTML = response.html;
            break;
        case 'short_description':
            var desc_elem = document.querySelector('.woocommerce-product-details__short-description');
            if (desc_elem)
                desc_elem.innerHTML = value;//response.html;
            break;
        case 'debug':
            console.log(response.html);
            break;
        }
    });
}

function v3d_woocommerce_get_attribute(name) {
    var formData = new FormData();
    formData.append('action', 'v3d_woocommerce_get_attribute');
    formData.append('v3d_attribute', name);

    var req = new XMLHttpRequest();
    req.open('POST', wc_add_to_cart_params.ajax_url);
    req.send(formData);
    req.addEventListener('load', function() {
        var response = JSON.parse(req.response);
        console.log(response);
    });
}
