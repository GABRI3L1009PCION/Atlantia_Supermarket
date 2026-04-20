@extends('layouts.marketplace')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Mis pedidos" subtitle="Historial de compras realizadas." /><x-data-panel title="Pedidos" :items="$pedidos" /></section>
@endsection

