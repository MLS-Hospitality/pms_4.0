(function ($) {
    "use strict";

    var state = {
        category: "",
        search: ""
    };

    function getUrl(id) {
        return $("#" + id).val() || "";
    }

    function parseNumber(value) {
        var num = parseFloat(String(value || "").replace(/[^0-9.\-]/g, ""));
        return isNaN(num) ? 0 : num;
    }

    function formatMoney(value) {
        return parseNumber(value).toLocaleString();
    }

    function getCsrfData() {
    var tokenName = $("#csrf_token_name").val();
    var tokenValue = $("#csrf_token_value").val();
    var data = {};

    if (tokenName && tokenValue) {
        data[tokenName] = tokenValue;
    }

    return data;
    }


    function syncSearchOptionsFromCards() {
        var $select = $("#product_name");
        if (!$select.length) return;

        var currentValue = $select.val();
        $select.empty();
        $select.append('<option value="">Search product...</option>');

        $(".select_product").each(function () {
            var $card = $(this);
            var id = $.trim($card.find(".select_product_id").val() || "");
            var name = $.trim($card.find(".select_product_name").val() || "");
            var variant = $.trim($card.find(".select_varient_name").val() || "");
            var label = name;

            if (variant) label += " (" + variant + ")";

            if (id && !$select.find('option[value="' + id + '"]').length) {
                $select.append($("<option>", { value: id, text: label }));
            }
        });

        if (currentValue) $select.val(currentValue);
    }

    function applyProductFilters() {
        $(".select_product").each(function () {
            var $card = $(this);
            var categoryId = String($card.find(".select_product_cat").val() || "");
            var productId = String($card.find(".select_product_id").val() || "");
            var productName = String($card.find(".select_product_name").val() || "").toLowerCase();
            var variantName = String($card.find(".select_varient_name").val() || "").toLowerCase();

            var categoryMatch = !state.category || categoryId === String(state.category);
            var searchMatch = !state.search ||
                productId === String(state.search) ||
                productName.indexOf(String(state.search).toLowerCase()) !== -1 ||
                variantName.indexOf(String(state.search).toLowerCase()) !== -1;

            $card.closest('[class*="col-"]').toggle(categoryMatch && searchMatch);
        });
    }

    function extractTotalsFromCartHtml(html) {
        var $tmp = $("<div>").html(html);
        var vatText = $.trim($tmp.find("#calvat").first().text());
        var totalText = $.trim($tmp.find("#caltotal").first().text());
        var vatInput = $tmp.find("#vat").first().val();
        var totalInput = $tmp.find("#grandtotal").first().val();

        if (vatText) {
            $("#calvat").text(vatText);
        } else if (vatInput !== undefined) {
            $("#calvat").text(formatMoney(vatInput));
        }

        if (totalText) {
            $("#caltotal").text(totalText);
        } else if (totalInput !== undefined) {
            $("#caltotal").text(formatMoney(totalInput));
        }
    }

    function refreshCart(html) {
        $("#addfoodlist").html(html);
        extractTotalsFromCartHtml(html);
    }

    function postCart(url, payload, onSuccess) {
    if (!url) return;

    payload = $.extend({}, payload || {}, getCsrfData());


    $.ajax({
        url: url,
        type: "POST",
        data: payload,
        cache: false,
        success: function (response) {
            if (typeof onSuccess === "function") onSuccess(response);
        },
        error: function (xhr) {
            console.error("Cart request failed:", xhr.status, xhr.responseText);
            alert("Request failed (" + xhr.status + "). Please refresh and try again.");
        }
    });
}


    function readProductPayload($card) {
        return {
            pid: $.trim($card.find(".select_product_id").val() || ""),
            catid: $.trim($card.find(".select_product_cat").val() || ""),
            sizeid: $.trim($card.find(".select_product_size").val() || ""),
            isgroup: $.trim($card.find(".select_product_isgroup").val() || "0"),
            totalvarient: parseInt($card.find(".select_totalvarient").val() || "0", 10),
            iscustomqty: parseInt($card.find(".select_iscustomeqty").val() || "0", 10),
            itemname: $.trim($card.find(".select_product_name").val() || ""),
            varientname: $.trim($card.find(".select_varient_name").val() || ""),
            price: $.trim($card.find(".select_product_price").val() || "0"),
            hasaddons: parseInt($card.find(".select_addons").val() || "0", 10)
        };
    }

    function isConfigurableProduct(product) {
        return product.hasaddons === 1 ||
               product.iscustomqty === 1 ||
               product.isgroup === "1";
    }

    function addProductToCart(product) {
        var payload = {
            catid: product.catid,
            pid: product.pid,
            sizeid: product.sizeid,
            isgroup: product.isgroup,
            itemname: product.itemname,
            varientname: product.varientname,
            qty: 1,
            price: product.price,
            addonsid: "",
            allprice: 0,
            adonsunitprice: "",
            adonsqty: "",
            adonsname: ""
        };

        postCart(getUrl("carturl"), payload, function (html) {
            refreshCart(html);
        });
    }

    function cartHasItems() {
        return $("#addfoodlist").find("table tbody tr").length > 0;
    }

    window.getslcategory = function (categoryId) {
        state.category = categoryId ? String(categoryId) : "";
        $(".posv2-cat").removeClass("active");

        $(".posv2-cat").each(function () {
            var $btn = $(this);
            var clickAttr = $btn.attr("onclick") || "";
            if (
                (!state.category && clickAttr.indexOf("getslcategory('')") !== -1) ||
                (state.category && clickAttr.indexOf("getslcategory(" + state.category + ")") !== -1)
            ) {
                $btn.addClass("active");
            }
        });

        applyProductFilters();
    };

    window.productsrcname = function () {
        state.search = $("#product_name").val() || "";
        applyProductFilters();
    };

    window.posupdatecart = function (cartId, pid, sizeId, qty, action) {
        postCart(getUrl("cartupdateturl"), {
            CartID: cartId,
            rowid: cartId,
            pid: pid,
            sizeid: sizeId,
            qty: qty,
            Udstatus: action
        }, function (html) {
            refreshCart(html);
        });
    };

    window.removecart = function (rowid) {
        postCart(getUrl("removeurl"), {
            rowid: rowid,
            CartID: rowid
        }, function (html) {
            refreshCart(html);
        });
    };

    window.placeorder = function () {
        if (!cartHasItems()) {
            alert("Please add at least one item.");
            return;
        }
        $("#onlineordersubmit").trigger("submit");
    };

    window.quickorder = function () {
        if (!cartHasItems()) {
            alert("Please add at least one item.");
            return;
        }

        if (!$("#quick_order_mode").length) {
            $("#onlineordersubmit").append('<input type="hidden" id="quick_order_mode" name="quick_order_mode" value="1">');
        } else {
            $("#quick_order_mode").val("1");
        }

        $("#onlineordersubmit").trigger("submit");
    };

    $(document).ready(function () {
        $(".main-content").css({ "background-color": "#f6f8fb" });
        $(".body-content").css({ "padding": "0px" });

        syncSearchOptionsFromCards();
        applyProductFilters();

        $(document).on("click", ".posv2-cat", function () {
            $(".posv2-cat").removeClass("active");
            $(this).addClass("active");
        });

        $(document).on("click", ".select_product", function (e) {
            if ($(e.target).is("input, button, a, select, textarea")) return;

            var product = readProductPayload($(this));
            if (!product.pid) return;

            if (isConfigurableProduct(product)) {
                alert("This item has extra options. We will wire its option flow in the next step.");
                return;
            }

            addProductToCart(product);
        });

        $(document).on("keydown", function (e) {
            if (e.shiftKey && (e.key === "S" || e.key === "s")) {
                e.preventDefault();
                $("#product_name").focus();
            }
        });
    });

})(jQuery);