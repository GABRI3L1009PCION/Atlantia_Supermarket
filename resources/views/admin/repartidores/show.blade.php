@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8"><x-page-header title="{{ $repartidor->name }}" subtitle="Actividad del repartidor." /><x-data-panel title="Repartidor" :items="$repartidor" /></section>
@endsection

