{include:{$BACKEND_CORE_PATH}/layout/templates/head.tpl}
{include:{$BACKEND_CORE_PATH}/layout/templates/structure_start_module.tpl}

<div class="pageTitle">
	<h2>{$lblPartner|ucfirst}: {$lblEdit}</h2>
</div>

{form:edit}
    <p>
        <label for="name">{$lblName|ucfirst}</label>
        {$txtName} {$txtNameError}
    </p>
    <p>
        <label for="img">{$lblImage|ucfirst}</label>
        {$fileImg} {$fileImgError}
    </p>
    <p>
        <label for="url">{$lblWebsite|ucfirst}</label>
        {$txtUrl} {$txturlError}
    </p>

	<div class="fullwidthOptions">
		<div class="buttonHolderRight">
			<input id="addButton" class="inputButton button mainButton" type="submit" name="edit" value="{$lblEdit|ucfirst}" />
		</div>
	</div>
{/form:edit}

{include:{$BACKEND_CORE_PATH}/layout/templates/structure_end_module.tpl}
{include:{$BACKEND_CORE_PATH}/layout/templates/footer.tpl}