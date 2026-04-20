@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="Transferencias" subtitle="Validacion manual de pagos bancarios." /><x-data-panel title="Pagos" :items="$payments" /></section>
@endsection

