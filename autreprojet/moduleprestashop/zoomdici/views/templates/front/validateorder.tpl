{extends file='page.tpl'}
{block name='page_content'}

<h2>Test</h2> 

<input id="id_order" value="{$id_order}"/>
<div id="main_container"></div>

{foreach from=$products item=product}
    <p>id = {$product.product_id} qty = {$product.product_quantity}</p>
{/foreach}

{/block}