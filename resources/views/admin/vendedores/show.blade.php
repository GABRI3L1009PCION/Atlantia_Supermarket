@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="{{ $vendor->business_name }}" subtitle="Detalle comercial, fiscal y operativo del vendedor." /><x-data-panel title="Vendedor" :items="$vendor" /></section>
@endsection

