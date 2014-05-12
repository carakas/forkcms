{include:{$BACKEND_CORE_PATH}/layout/templates/head.tpl}
{include:{$BACKEND_CORE_PATH}/layout/templates/structure_start_module.tpl}

<div class="pageTitle">
    <h2>{$lblPartner|ucfirst}: {$lblAdd}</h2>
</div>

{form:add}
    <p>
        <label for="name">{$lblName|ucfirst}<abbr>*</abbr></label>
        {$txtName} {$txtNameError}
    </p>
    <p>
        <label for="img">{$lblImage|ucfirst}<abbr>*</abbr></label>
        {$fileImg} {$fileImgError}
    </p>
    <p>
        <label for="url">{$lblWebsite|ucfirst}<abbr>*</abbr></label>
        {$txtUrl} {$txtUrlError}
    </p>
    <div class="fullwidthOptions">
        <div class="buttonHolderRight">
            <input id="addButton" class="inputButton button mainButton" type="submit" name="add" value="{$lblAdd|ucfirst}" />
        </div>
    </div>
{/form:add}

{include:{$BACKEND_CORE_PATH}/layout/templates/structure_end_module.tpl}
{include:{$BACKEND_CORE_PATH}/layout/templates/footer.tpl}