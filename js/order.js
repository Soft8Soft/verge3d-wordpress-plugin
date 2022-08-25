/**
 * We store order items state in the form hidden input
 */
function set_form_order_items(items) {
    const form = document.querySelector('form');

    let order_items = form.querySelector('input[name="order_items"');
    if (order_items) {
        order_items.value = JSON.stringify(items);
    } else {
        order_items = document.createElement('input');
        order_items.type = 'hidden';
        order_items.name = 'order_items';
        order_items.value = JSON.stringify(items);
        form.appendChild(order_items);
    }
}

function get_form_order_items() {
    const form = document.querySelector('form');

    const order_items = form.querySelector('input[name="order_items"');
    if (order_items) {
        return JSON.parse(order_items.value);
    } else {
        return [];
    }
}

function set_form_order_item(id, item) {
    const order_items = get_form_order_items();
    order_items[id] = item;
    set_form_order_items(order_items);
}

function append_form_order_item(item) {
    const order_items = get_form_order_items();
    order_items.push(item);
    set_form_order_items(order_items);
}

function get_form_order_item(id) {
    const order_items = get_form_order_items();
    return order_items[id];
}

/**
 * Requesting server just to get proper HTML, no database modifications here
 */
function fetch_order_items_html() {

    const order_items = get_form_order_items();

    const form_data = new FormData();
    form_data.append('action', 'v3d_ajax_fetch_order_items');
    form_data.append('order_items', JSON.stringify(order_items));

    const req = new XMLHttpRequest();
    req.open('POST', ajax_object.ajax_url);
    req.send(form_data);
    req.addEventListener('load', function() {
        const response = JSON.parse(req.response);
        document.getElementById('the-list').innerHTML = response.rows;
        document.querySelectorAll('.tablenav-pages > .displaying-num').forEach(elem => {
            elem.innerHTML = response.total_items_i18n;
        });
    });
}

function fetch_product_info(callback) {

    const form_data = new FormData();
    form_data.append('action', 'v3d_ajax_fetch_product_info');

    const req = new XMLHttpRequest();
    req.open('POST', ajax_object.ajax_url);
    req.send(form_data);
    req.addEventListener('load', function() {
        const response = JSON.parse(req.response);
        callback(response);
    });
}

function add_custom_item_cb() {
    append_form_order_item({
        'title': 'Some product',
        'sku': '',
        'price': 0,
        'quantity': 1
    });
    fetch_order_items_html();
}


function delete_item_cb(id) {
    const order_items = get_form_order_items();
    order_items.splice(Number(id), 1);
    set_form_order_items(order_items);
    fetch_order_items_html();
};

function delete_bulk_items_cb(e) {
    const select = document.getElementById('bulk-action-selector-top');
    const option = select.selectedOptions[0].value;
    if (option == 'delete_order_item') {
        const order_items = get_form_order_items();
        const checkers = document.querySelectorAll('input[name="order_item[]"]');

        for (let i = checkers.length-1; i >=0; i--) {
            const checker = checkers[i];

            if (checker.checked)
                order_items.splice(Number(checker.value), 1);
        }

        set_form_order_items(order_items);
        fetch_order_items_html();
    }
}

/**
 * Add product item dialog callbacks
 */
function add_product_item_cb() {
    fetch_product_info(function(products) {

        let optionsHTML = '';

        products.forEach(p => {
            optionsHTML += `<option value="${p['sku']}" data-price="${p['price']}" >${p['title']}</option>`;
        });

        document.getElementById('add_product_item_select').innerHTML = optionsHTML;
        document.getElementById('add_product_item_quantity').value = 1;

        const add_product_item_dia = document.getElementById('add_product_item');
        add_product_item_dia.style.display = 'flex';
    });
}

function add_product_item_save_cb() {

    const select = document.getElementById('add_product_item_select');
    const option = select.selectedOptions[0];

    append_form_order_item({
        'title': option.innerText,
        'sku': option.value,
        'price': option.dataset.price,
        'quantity': document.getElementById('add_product_item_quantity').value
    });

    document.getElementById('add_product_item').style.display = 'none';

    fetch_order_items_html();
}

function add_product_item_cancel_cb() {
    document.getElementById('add_product_item').style.display = 'none';
}

/**
 * Edit order item callbacks
 */
function edit_order_item_cb(id) {

    const order_item = get_form_order_item(id);

    document.getElementById('edit_order_item_title').value = order_item['title'];
    document.getElementById('edit_order_item_sku').value = order_item['sku'];
    document.getElementById('edit_order_item_price').value = order_item['price'];
    document.getElementById('edit_order_item_quantity').value = order_item['quantity'];

    const edit_order_item_dia = document.getElementById('edit_order_item');
    edit_order_item_dia.style.display = 'flex';
    edit_order_item_dia.dataset.order_item_id = id;
}

function edit_order_item_save_cb() {

    const edit_order_item_dia = document.getElementById('edit_order_item');

    const id = edit_order_item_dia.dataset.order_item_id;

    set_form_order_item(id, {
        'title': document.getElementById('edit_order_item_title').value,
        'sku': document.getElementById('edit_order_item_sku').value,
        'price': document.getElementById('edit_order_item_price').value,
        'quantity': document.getElementById('edit_order_item_quantity').value
    });

    edit_order_item_dia.style.display = 'none';

    fetch_order_items_html();
}

function edit_order_item_cancel_cb() {
    document.getElementById('edit_order_item').style.display = 'none';
}

function send_pdf_cb(pdftype) {
    const form_data = new FormData();
    form_data.append('action', 'v3d_ajax_send_pdf');
    form_data.append('order', ajax_object.order_id);
    form_data.append('pdftype', pdftype);

    const req = new XMLHttpRequest();
    req.open('POST', ajax_object.ajax_url);
    req.send(form_data);
    req.addEventListener('load', function() {
        const response = JSON.parse(req.response);
        document.getElementById(`${pdftype}_sent`).style.display = 'flex';
    });
}

function quote_sent_close_cb() {
    document.getElementById('quote_sent').style.display = 'none';
}

function invoice_sent_close_cb() {
    document.getElementById('invoice_sent').style.display = 'none';
}

window.addEventListener('load', function() {

    // NOTE: prevent internal form submit
    const doaction_btn = document.getElementById('doaction');
    const doaction2_btn = document.getElementById('doaction2');

    if (doaction_btn) {
        doaction_btn.type = 'button';
        doaction2_btn.type = 'button';
        document.getElementById('doaction').addEventListener('click', delete_bulk_items_cb);
    }
});

