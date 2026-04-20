@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Pedido {{ $pedido->numero_pedido }}" subtitle="Detalle operativo y financiero del pedido." /><x-data-panel title="Pedido" :items="$pedido" /></section>
@endsection

