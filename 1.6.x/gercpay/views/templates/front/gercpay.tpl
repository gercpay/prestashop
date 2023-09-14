<p class="payment_module">
  <a href="{$link->getModuleLink('gercpay', 'redirect', ['id_cart' => {$id}])|escape:'htmlall':'UTF-8'}" title="{l s='Payment Visa, Mastercard, Google Pay, Apple Pay' mod='gercpay'}">
    <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/gercpay.png" rel='gercpay'/>
      {$this_description|escape:'htmlall':'UTF-8'}
  </a>
</p>


