# Blade Code Block Test

Current locale: {{ app()->getLocale() }}

Use `{{ $order->order_number }}` inline.

```blade
<a href="{{ route('orders.show', ['order' => $order->getKey()]) }}" wire:navigate>
    @if ($order->is_locked)
        {{ $order->order_number }}
    @endif
</a>
```
