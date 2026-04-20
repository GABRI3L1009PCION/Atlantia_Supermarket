@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="DTE FEL" subtitle="Documentos tributarios emitidos por vendedores." /><x-data-panel title="Facturas" :items="$dtes" /></section>
@endsection

