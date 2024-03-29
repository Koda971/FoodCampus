function searchProductsAndSuppliers(str) {

    $(".list-group-item.link-class").remove();

    $.post("/tecweb_project/FoodCampus/php/navbar/navbar_research.php", {request:"suppliers", string:str})
        .done(function(data) {

            var suppliers = JSON.parse(data);

            if (suppliers.status.localeCompare("error") == 0) {
                //Errore
            } else if (suppliers.status.localeCompare("ok") == 0) {

                if (suppliers.data.length === 0) {
                    //No results
                } else {
                    for (var i = 0; i < suppliers.data.length; i++) {
                        $("#result").append("<li class='list-group-item link-class'><div class='row'><div class='col search-item'><a class='a-searchSupplier' href=/tecweb_project/FoodCampus/php/user/suppliers/php/supplier.php?id=" + suppliers.data[i]["fid"] +"><span class='supplierSearchInfo'>" + suppliers.data[i]["fnome"] + "</span></a></div>"
                        + "<div class='col search-review'>"
                        + "<div id='starAverageRating" +  i  +"'><label for='voto" + i +"' class='hidden'>Stelle voto fornitore</label><input id='voto" + i +"' class='rating rating-loading' data-min='0' data-max='5' data-step='1' value='" + ((suppliers.data[i]["valutazione_media"] === null) ? 0.0 : suppliers.data[i]["valutazione_media"].toFixed(1)) + "' data-size='lg' data-showcaption=false disabled/></div>"
                        + "<p id='averageRating" + i + "'><span class='supplierSearchInfo'>" + ((suppliers.data[i]["valutazione_media"] === null) ? "/" : suppliers.data[i]["valutazione_media"].toFixed(1)) + "</span> su 5 stelle</p>"
                        + "</div></div></li>");
                    }
                    loadStars();
                }
            }
    })
    .fail(function(xhr, textStatus, errorThrown) {
        //xhr.responseText
    });

    $.post("/tecweb_project/FoodCampus/php/navbar/navbar_research.php", {request:"products", string:str})
        .done(function(data) {

            var products = JSON.parse(data);

            if (products.status.localeCompare("error") == 0) {
                //Errore
            } else if (products.status.localeCompare("ok") == 0) {

                if (products.data.length === 0) {
                    //No results
                } else {
                    for (var i = 0; i < products.data.length; i++){
                        $('#result').append('<li class="list-group-item link-class">'

                                                + '<div class="row">'
                                                + "<div class='col-2'>"
                                                + "</div>"
                                                + '<div class="col"><p class="p-result p-list list-group-item-heading">'
                                                + products.data[i]["pnome"] + "<br/>"
                                                + ((products.data[i]["vegano"] === 1) ? " (vegano) " : "")
                                                + ((products.data[i]["celiaco"] === 1) ? " (no glutine) " : "")
                                                + "</p>"
                                                + "<a href='/tecweb_project/FoodCampus/php/user/suppliers/php/supplier.php?id=" + products.data[i]["fid"] + "' class='list-group-item-text'>"
                                                + products.data[i]["fnome"] + " "
                                                + "</a></div>"
                                                + "<div class='col search-item'>" + "<span class='price-result'>€ " + products.data[i]["prezzo"] + "</span> "+"<span class='popInfo' data-toggle='popover' data-trigger='hover' data-content='I fornitori non possono acquistare'> <button type='button' value='" + products.data[i]["pid"] + "' class='add-cart btn btn-deafult btn-kart'><em class='fas fa-cart-plus'></em></button></span>"
                                                + "</div>"
                                                + "</div></li>");
                    }

                    if (products.isSupplier) {
                        //$("span").popover({ trigger: "hover" }).data("I Fornitori non possono comprare");
                        $(".btn-kart").prop("disabled",true);
                        $(".btn-kart").css("pointer-events", "none");
                    }
                }
            }
    })
    .fail(function(xhr, textStatus, errorThrown) {
        //xhr.responseText
    });
}

function checkFocus() {
    $(".list-group-item.link-class").remove();
}

$(document).ready(function() {
    $("#navbar-search").on("keyup", function() {
		searchProductsAndSuppliers(this.value);
	});

    $(document).click(function(e) {
        var container = $(".searchit");

        if (!container.is(e.target) && container.has(e.target).length === 0)
        {
            $(".list-group-item.link-class").remove();
        }
    });
});
