{extends file='page.tpl'}
{block name='page_content'}


{$html nofilter}

{foreach from=$products item=product}
    <h5>test{$product['name']}</h5>
    <p id="order_id" style="display: none">{$order_id}</p>
    <input id="ticket_identifier" style="display: none" value="{base64_encode($order_id)}"/>
    <div
        name="tariff" 
        class="box"
        id="field_place{$product['id']}"
        id_product="{$product['id']}"
        qty="{$product['qty']}"
        price="{$product['price']}">
    </div>
    <div><br/><br/></div>
{/foreach}

{/block}
