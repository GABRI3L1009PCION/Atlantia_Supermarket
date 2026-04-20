@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Vendedores" subtitle="Aprobacion y supervision de vendedores locales." /><x-data-panel title="Listado" :items="$vendors" /></section>
@endsection

