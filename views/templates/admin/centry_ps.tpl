<form id="configuration_form" class="defaultForm form-horizontal centry_ps" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="submitcentry_ps" value="1">
    <div class="panel" id="fieldset_0">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#general" aria-controls="general" role="tab" data-toggle="tab">General</a></li>
            <li role="presentation"><a href="#category" aria-controls="category" role="tab" data-toggle="tab">Categorias</a></li>
            <li role="presentation"><a href="#manufacturer" aria-controls="manufacturer" role="tab" data-toggle="tab">Fabricantes o Marcas</a></li>
            <li role="presentation"><a href="#feature" aria-controls="feature" role="tab" data-toggle="tab">Características o funcionalidades claves</a></li>
            <li role="presentation"><a href="#combination_attribute" aria-controls="combination_attribute" role="tab" data-toggle="tab">Artibutos de combinaciones</a></li>
            <li role="presentation"><a href="#order_state" aria-controls="order_state" role="tab" data-toggle="tab">Estados de órdenes</a></li>
            <li role="presentation"><a href="#massive_sync" aria-controls="massive_sync" role="tab" data-toggle="tab">Sincronización masiva</a></li>
            <li role="presentation"><a href="#advanced" aria-controls="advanced" role="tab" data-toggle="tab">Avanzado</a></li>
        </ul>
        <div class="panel-heading"></div>

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="general">
                {include file="$local_path/views/templates/admin/general.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="category">
                {include file="$local_path/views/templates/admin/category.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="manufacturer">
                {include file="$local_path/views/templates/admin/manufacturer.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="feature">
                {include file="$local_path/views/templates/admin/feature.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="combination_attribute">
                {include file="$local_path/views/templates/admin/combination_attribute.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="order_state">
                {include file="$local_path/views/templates/admin/order_state.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="massive_sync">
                {include file="$local_path/views/templates/admin/massive_sync.tpl"}
            </div>
            <div role="tabpanel" class="tab-pane fade" id="advanced">
                {include file="$local_path/views/templates/admin/advanced.tpl"}
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" value="1" id="configuration_form_submit_btn" name="submitcentry_ps" class="button">
                <i class="process-icon-save"></i> Guardar
            </button>
        </div>
    </div>
</form>