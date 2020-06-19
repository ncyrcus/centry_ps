<div class="form-wrapper">
    <div class="form-group">
        <label class="control-label col-lg-3">
            Mode
        </label>
        <div class="col-lg-9">
            <div class="radio t">
                <label><input type="radio" name="CENTRY_MAESTRO" id="active_on" value="1"{if $centry_maestro} checked="checked"{/if}>Maestro</label>
            </div>
            <div class="radio t">
                <label><input type="radio" name="CENTRY_MAESTRO" id="active_off" value="0"{if !$centry_maestro} checked="checked"{/if}>Esclavo</label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">
            De dónde leer las características del poducto
        </label>
        <div class="col-lg-9">
            <div class="radio t">
                <label><input type="radio" name="CENTRY_PRODUCT_CHARACTERISTICS" id="active_on" value="1"{if $centry_product_characteristics} checked="checked"{/if}>Descripción larga</label>
            </div>
            <div class="radio t">
                <label><input type="radio" name="CENTRY_PRODUCT_CHARACTERISTICS" id="active_off" value="0"{if !$centry_product_characteristics} checked="checked"{/if}>Características o funcionalidades</label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3 required">
            App id
        </label>
        <div class="col-lg-9">
            <input type="text" name="CENTRY_API_APPID" id="CENTRY_API_APPID" value="{$centry_api_appid}" class="" size="100" required="required">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3 required">
            Secret key
        </label>
        <div class="col-lg-9">
            <input type="text" name="CENTRY_API_SECRETKEY" id="CENTRY_API_SECRETKEY" value="{$centry_api_secretkey}" class="" size="100" required="required">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">
            Redirect URI
        </label>
        <div class="col-lg-9">
            <input type="text" name="CENTRY_REDIRECT_URI" id="CENTRY_REDIRECT_URI" value="{$centry_redirect_uri}" class="" size="100" readonly="readonly" style="cursor: text">
            <p class="help-block">
                No es editable. Sólo para referencia.
            </p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">
            Notification threads
        </label>
        <div class="col-lg-9">
            <input type="text" name="CENTRY_MAX_THREADS" id="CENTRY_MAX_THREADS" value="{$centry_max_threads}" class="" size="2" required="required">
            <p class="help-block">
                No es editable. Sólo para referencia.
            </p>
        </div>
    </div>
</div>
