<div class="form-wrapper">
    <div class="form-group">
        <label class="control-label col-lg-3">
            Fuente de las categorías
        </label>
        <div class="col-lg-9">
            <div class="radio t">
                <label><input type="radio" name="CENTRY_CATEGORY_SOURCE" id="active_on" value="CATEGORY_SOURCE_PRIMARY"{if $centry_category_source == "CATEGORY_SOURCE_PRIMARY"} checked="checked"{/if}>Categoría principal</label>
            </div>
            <div class="radio t">
                <label><input type="radio" name="CENTRY_CATEGORY_SOURCE" id="active_off" value="CATEGORY_SOURCE_LOWER_SECONDARY"{if $centry_category_source == "CATEGORY_SOURCE_LOWER_SECONDARY"} checked="checked"{/if}>Categoría secundaria de más bajo nivel</label>
            </div>
        </div>
    </div>
</div>

<div class="cleafix"></div>

<div class="panel-heading">
    Category Translation
</div>

<div class="form-wrapper" id="centry_categories"></div>


<script>
    $(function () {
        var categoriesPrestashop = {$centry_categories_prestashop|json_encode};
        var categoriesCentry = {$centry_categories_centry|json_encode};
        var selectCategoiesOptions = generateSelectOptions();
        $("#centry_categories").html(listCategories());
        selectCategories();

        function generateSelectOptions() {
            var resp = "";
            for (var i = 0; i < categoriesCentry.length; ++i) {
                resp += '<option value="' + categoriesCentry[i].id + '">' + categoriesCentry[i].name + '</option>';
            }
            return resp;
        }

        function listCategories() {
            var resp = "";
            for (var i = 0; i < categoriesPrestashop.length; ++i) {
                resp += '\
    <div class="form-group">\
        <label class="control-label col-lg-3">\
            ' + categoriesPrestashop[i].label + '\
        </label>\
        <div class="col-lg-9">\
            <select name="categories[' + categoriesPrestashop[i].id + ']" class="fixed-width-xxl fixed-width-xl" id="prestashop_category_' + categoriesPrestashop[i].id + '">\
                <option value="" selected="selected">--- Selecciona una categoría ---</option>\
                ' + selectCategoiesOptions + '\
            </select>\
        </div>\
    </div>';
            }
            return resp;
        }

        function selectCategories() {
            for (var i = 0; i < categoriesPrestashop.length; ++i) {
                if (categoriesPrestashop[i].centry !== null) {
                    $("#prestashop_category_" + categoriesPrestashop[i].id).val(categoriesPrestashop[i].centry);
                }
            }
        }
    });
</script>