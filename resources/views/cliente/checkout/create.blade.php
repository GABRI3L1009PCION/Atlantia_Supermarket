@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Checkout" subtitle="Confirma direccion, pago y resumen multivendedor." /><livewire:checkout.selector-direccion /><livewire:checkout.formulario-pago /><livewire:checkout.resumen-multivendedor /></section>
@endsection

