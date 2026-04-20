@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Pedidos recibidos" subtitle="Ordenes asignadas a tu tienda." /><x-data-panel title="Pedidos" :items="$pedidos" /></section>
@endsection

