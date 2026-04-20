@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Carrito" subtitle="Productos seleccionados para tu compra." /><x-data-panel title="Items" :items="$carrito->items" /></section>
@endsection

