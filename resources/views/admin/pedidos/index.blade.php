@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Pedidos" subtitle="Gestion y seguimiento de pedidos del marketplace." /><x-data-panel title="Pedidos" :items="$pedidos" /></section>
@endsection

