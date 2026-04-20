@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="{{ $producto->nombre }}" subtitle="Detalle administrativo del producto." /><x-data-panel title="Producto" :items="$producto" /></section>
@endsection

