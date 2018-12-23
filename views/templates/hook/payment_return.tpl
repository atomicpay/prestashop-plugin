{if $state == "1"}
<p>
<strong>{$title}</strong>
<br /><br /> {$message}
<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='atomicpay'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='atomicpay'}</a>
</p>
{else}
<p class="warning">
<strong>{$title}</strong>
<br /><br /> {$message}
<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='atomicpay'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='atomicpay'}</a>
</p>
{/if}
