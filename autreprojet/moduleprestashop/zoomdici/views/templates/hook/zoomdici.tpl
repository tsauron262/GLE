<h5>{$product['name']}</h5>
<p id="order_id" style="display: none">{$order_id}</p>
<input id="ticket_identifier" style="display: none" value="{base64_encode($order_id)}"/>
<div
    name="tariff" 
    id="field_place{$product['id']}"
    id_product="{$product['id']}"
    qty="{$product['qty']}"
    price="{$product['price']}">
</div>